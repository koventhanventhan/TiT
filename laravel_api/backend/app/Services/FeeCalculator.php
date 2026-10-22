<?php

namespace App\Services;

use App\Models\Package;
use App\Models\SiteSetting;
use App\Models\Subject;
use App\Models\User;

/**
 * Read-Only Fee Calculator for the Assistant.
 *
 * Provides pre-calculated fee tables for LLM prompt context and evaluates
 * student tuition fees without modifying core billing/payment models.
 */
class FeeCalculator
{
    /**
     * Map grade and optional stream to subject category (identical to User::getSubjectCategory).
     */
    public function getCategoryForGrade(?string $grade, ?string $stream = null): ?string
    {
        if (empty($grade)) {
            return null;
        }

        $gradeNum = 0;
        if (preg_match('/(\d+)/', $grade, $matches)) {
            $gradeNum = (int) $matches[1];
        }

        $stream = strtolower($stream ?? '');

        if ($gradeNum >= 1 && $gradeNum <= 2) return 'grade_1_to_5';
        if ($gradeNum == 3) return 'grade_3';
        if ($gradeNum == 4) return 'grade_4';
        if ($gradeNum == 5) return 'grade_5';
        if ($gradeNum >= 6 && $gradeNum <= 9) return 'grade_6_to_9';
        if ($gradeNum >= 10 && $gradeNum <= 11) return 'grade_10_to_11';

        if ($gradeNum >= 12 && $gradeNum <= 13) {
            if (str_contains($stream, 'commerce')) return 'commerce_stream';
            if (str_contains($stream, 'tech')) return 'tech_stream';
            if (str_contains($stream, 'art')) return 'arts_stream';
            if (str_contains($stream, 'bio') || str_contains($stream, 'math')) return 'bio_maths_stream';
            return 'grade_12_to_13';
        }

        return null;
    }

    /**
     * Calculate monthly fee for a User model (exact parity with User::calculateMonthlyFee).
     */
    public function calculateMonthlyFee(User $user, ?float $fallbackAmount = null): float
    {
        $amount = 0.0;

        if ($user->selected_subjects) {
            $category = $user->getSubjectCategory();

            $subjectsRaw = $user->selected_subjects;
            $selectedSubjects = [];
            if (is_array($subjectsRaw)) {
                $selectedSubjects = $subjectsRaw;
            } elseif (is_string($subjectsRaw)) {
                $decoded = json_decode($subjectsRaw, true);
                if (is_array($decoded)) {
                    $selectedSubjects = $decoded;
                } elseif (is_string($decoded)) {
                    $decoded2 = json_decode($decoded, true);
                    if (is_array($decoded2)) {
                        $selectedSubjects = $decoded2;
                    } else {
                        $selectedSubjects = array_map('trim', explode(',', $decoded));
                    }
                } else {
                    $selectedSubjects = array_map('trim', explode(',', $subjectsRaw));
                }
            }
            $selectedSubjects = array_filter($selectedSubjects);

            if (!empty($selectedSubjects)) {
                $medium = strtolower($user->medium ?? '');
                $subjectData = Subject::when($category, function ($query) use ($category) {
                    if ($category === 'grade_12_to_13') {
                        return $query->whereIn('category', ['arts_stream', 'commerce_stream', 'tech_stream', 'bio_maths_stream', 'grade_12_to_13']);
                    }
                    return $query->where('category', $category);
                })
                ->whereIn('name', $selectedSubjects)
                ->when($medium, function ($query) use ($medium) {
                    return $query->where(function ($q) use ($medium) {
                        $q->where('medium', $medium)->orWhere('medium', 'both');
                    });
                })
                ->get(['name', 'price']);

                $uniqueSubjects = $subjectData->unique('name');
                $sumAmount = (float) $uniqueSubjects->sum('price');

                $packages = Package::where('category', $category)
                    ->where(function ($q) use ($medium) {
                        $q->where('medium', $medium)->orWhere('medium', 'both');
                    })
                    ->get();

                if ($packages->isNotEmpty()) {
                    $allSubjPkg = $packages->firstWhere('type', 'all_subjects');
                    $mainSubjPkg = $packages->firstWhere('type', 'main_subjects');

                    $totalSubjectsForCategory = Subject::where('category', $category)
                        ->where(function ($q) use ($medium) {
                            $q->where('medium', $medium)->orWhere('medium', 'both');
                        })
                        ->get()
                        ->unique('name')
                        ->count();

                    if ($allSubjPkg && $totalSubjectsForCategory > 0 && count($uniqueSubjects) >= $totalSubjectsForCategory) {
                        $sumAmount = (float) $allSubjPkg->package_price;
                    } else {
                        $mainSubjectNames = ['tamil', 'maths', 'science', 'english', 'history'];
                        $hasAllMainSubjects = true;
                        $selectedNamesLower = $uniqueSubjects->pluck('name')->map(fn($n) => strtolower($n))->toArray();

                        foreach ($mainSubjectNames as $ms) {
                            if (!in_array($ms, $selectedNamesLower)) {
                                $hasAllMainSubjects = false;
                                break;
                            }
                        }

                        if ($mainSubjPkg && $hasAllMainSubjects) {
                            $pkgBasePrice = (float) $mainSubjPkg->package_price;
                            $price = $pkgBasePrice;

                            if ($mainSubjPkg->addon_price && (float) $mainSubjPkg->addon_price > 0) {
                                $addonPrice = (float) $mainSubjPkg->addon_price;
                                $baseCount = $mainSubjPkg->base_subject_count ?? count($mainSubjectNames);
                                if (count($uniqueSubjects) > $baseCount) {
                                    $extraCount = count($uniqueSubjects) - $baseCount;
                                    $price += $extraCount * $addonPrice;
                                }
                            }

                            if ($price < $sumAmount) {
                                $sumAmount = $price;
                            }
                        }
                    }
                }

                $amount = $sumAmount;
            }
        }

        if ($amount <= 0) {
            $amount = $fallbackAmount ?? config('payment.monthly_amount', 500.0);
        }

        $isFirstPayment = !$user->payments()->where('status', 'paid')->exists();
        if ($isFirstPayment && $user->current_grade) {
            $gradeNum = 0;
            if (preg_match('/(\d+)/', $user->current_grade, $m)) {
                $gradeNum = (int) $m[1];
            }
            if ($gradeNum > 0) {
                $configStr = SiteSetting::get('admission_fees_config', '{}');
                $config = json_decode($configStr, true) ?? [];

                if (isset($config[$gradeNum]) && !empty($config[$gradeNum]['enabled'])) {
                    $admissionFee = (float) $config[$gradeNum]['amount'];
                    $amount += $admissionFee;
                }
            }
        }

        return (float) $amount;
    }

    /**
     * Get admission fee for a grade.
     */
    public function getAdmissionFee(string|int $grade): float
    {
        $gradeNum = 0;
        if (is_numeric($grade)) {
            $gradeNum = (int) $grade;
        } elseif (preg_match('/(\d+)/', (string) $grade, $m)) {
            $gradeNum = (int) $m[1];
        }

        if ($gradeNum <= 0) {
            return 0.0;
        }

        $configStr = SiteSetting::get('admission_fees_config', '{}');
        $config = json_decode($configStr, true) ?? [];

        if (isset($config[$gradeNum]) && !empty($config[$gradeNum]['enabled'])) {
            return (float) $config[$gradeNum]['amount'];
        }

        return 0.0;
    }

    /**
     * Build pre-calculated fee breakdown per grade and medium.
     */
    public function getReadyMadeGradeFeeSummary(int $instituteId = 1): array
    {
        $categories = [
            'grade_1_to_5' => 'Grades 1 - 2',
            'grade_3' => 'Grade 3',
            'grade_4' => 'Grade 4',
            'grade_5' => 'Grade 5 (Scholarship)',
            'grade_6_to_9' => 'Grades 6 - 9',
            'grade_10_to_11' => 'Grades 10 - 11 (O/L)',
            'arts_stream' => 'A/L Arts Stream',
            'commerce_stream' => 'A/L Commerce Stream',
            'bio_maths_stream' => 'A/L Bio / Maths Stream',
            'tech_stream' => 'A/L Technology Stream',
        ];

        $summary = [];

        foreach ($categories as $catKey => $catLabel) {
            $subjects = Subject::where('category', $catKey)->get();
            $packages = Package::where('category', $catKey)->get();

            // Extract grade number for admission fee
            preg_match('/(\d+)/', $catLabel, $gm);
            $gNum = !empty($gm[1]) ? (int) $gm[1] : 0;
            $admissionFee = $this->getAdmissionFee($gNum);

            $subjectList = [];
            foreach ($subjects as $s) {
                $subjectList[] = [
                    'name' => $s->name,
                    'medium' => $s->medium,
                    'price' => (float) $s->price,
                ];
            }

            $packageList = [];
            foreach ($packages as $p) {
                $packageList[] = [
                    'name' => $p->package_name ?? $p->type,
                    'type' => $p->type,
                    'medium' => $p->medium,
                    'package_price' => (float) $p->package_price,
                    'addon_price' => (float) ($p->addon_price ?? 0),
                    'base_subject_count' => (int) ($p->base_subject_count ?? 0),
                ];
            }

            $allSubjTotal = $subjects->unique('name')->sum('price');
            $allSubjPkg = $packages->firstWhere('type', 'all_subjects');
            $effectiveAllTotal = $allSubjPkg ? (float) $allSubjPkg->package_price : (float) $allSubjTotal;

            $summary[$catKey] = [
                'label' => $catLabel,
                'admission_fee' => $admissionFee,
                'subjects' => $subjectList,
                'packages' => $packageList,
                'all_subjects_total' => $effectiveAllTotal,
            ];
        }

        return $summary;
    }

    /**
     * Format ready-made fee summary into clear markdown prompt text.
     */
    public function formatFeeContextForPrompt(int $instituteId = 1): string
    {
        $summary = $this->getReadyMadeGradeFeeSummary($instituteId);

        $out = "OFFICIAL FEE SCHEDULE (SOURCE OF TRUTH):\n";
        $out .= "- Rule: Quote ONLY the exact numbers listed below. Never do arithmetic, never guess totals.\n";
        $out .= "- For custom combinations not listed, state: \"The exact total for your selected subjects is calculated automatically on the registration page at /register.\"\n\n";

        foreach ($summary as $item) {
            $out .= "### {$item['label']}\n";
            $out .= "- One-time Admission Fee: LKR " . number_format($item['admission_fee'], 2) . "\n";

            if (!empty($item['packages'])) {
                $out .= "- Package Bundles:\n";
                foreach ($item['packages'] as $pkg) {
                    $pkgName = $pkg['name'];
                    $price = number_format($pkg['package_price'], 2);
                    $med = $pkg['medium'];
                    $addonStr = $pkg['addon_price'] > 0 ? " (Add-on subject: LKR " . number_format($pkg['addon_price'], 2) . " each)" : "";
                    $out .= "  * {$pkgName} ({$med}): LKR {$price}{$addonStr}\n";
                }
            }

            if (!empty($item['subjects'])) {
                $out .= "- Individual Subject Fees:\n";
                foreach ($item['subjects'] as $sub) {
                    $out .= "  * {$sub['name']} ({$sub['medium']}): LKR " . number_format($sub['price'], 2) . "\n";
                }
            }

            $out .= "- Complete All-Subjects Monthly Total: LKR " . number_format($item['all_subjects_total'], 2) . "\n\n";
        }

        return trim($out);
    }
}
