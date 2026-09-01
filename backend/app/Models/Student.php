<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'full_name',
        'first_name',
        'last_name',
        'date_of_birth',
        'gender',
        'school_name',
        'medium',
        'current_grade',
        'stream',
        'selected_subjects',
        'custom_fields',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'selected_subjects' => 'array',
            'custom_fields' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attendances()
    {
        return $this->hasMany(\App\Models\Attendance::class);
    }

    public function submissions()
    {
        return $this->hasMany(\App\Models\AssignmentSubmission::class, 'student_id');
    }

    public function getSubjectCategory()
    {
        $grade = $this->current_grade;
        $stream = strtolower($this->stream ?? '');
        $gradeNum = 0;

        if (preg_match('/Grade\s*(\d+)/i', $grade, $m)) {
            $gradeNum = (int)$m[1];
        } elseif (preg_match('/(\d+)/', $grade, $m)) {
            $gradeNum = (int)$m[1];
        }

        if ($gradeNum >= 1 && $gradeNum <= 2) return 'grade_1_to_2';
        if ($gradeNum == 3) return 'grade_3';
        if ($gradeNum == 4) return 'grade_4';
        if ($gradeNum == 5) return 'grade_5';
        if ($gradeNum >= 6 && $gradeNum <= 9) return 'grade_6_to_9';
        if ($gradeNum >= 10 && $gradeNum <= 11) return 'grade_10_to_11';

        if ($gradeNum >= 12 && $gradeNum <= 13) {
            if (str_contains($stream, 'art')) return 'arts_stream';
            if (str_contains($stream, 'bio') || str_contains($stream, 'math')) return 'bio_maths_stream';
            return 'grade_12_to_13'; // Fallback
        }

        return null;
    }

    public function calculateMonthlyFee(?float $fallbackAmount = null): float
    {
        $amount = 0;

        if ($this->selected_subjects) {
            $category = $this->getSubjectCategory();

            $selectedSubjects = is_array($this->selected_subjects) 
                ? $this->selected_subjects 
                : array_filter(array_map('trim', explode(',', (string) $this->selected_subjects)));

            if (!empty($selectedSubjects)) {
                $medium = strtolower($this->medium ?? '');
                $subjectData = \App\Models\Subject::when($category, function ($query) use ($category) {
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
                
                $package = \App\Models\Package::where('category', $category)
                    ->where(function ($q) use ($medium) {
                        $q->where('medium', $medium)->orWhere('medium', 'both');
                    })
                    ->first();
                
                if ($package) {
                    $totalSubjectsForCategory = \App\Models\Subject::where('category', $category)
                        ->where(function ($q) use ($medium) {
                            $q->where('medium', $medium)->orWhere('medium', 'both');
                        })
                        ->get()
                        ->unique('name')
                        ->count();
                    
                    if ($totalSubjectsForCategory > 0 && count($uniqueSubjects) >= $totalSubjectsForCategory) {
                        $sumAmount = (float) $package->package_price;
                    }
                }

                $amount = $sumAmount;
            }
        }

        if ($amount <= 0) {
            $amount = $fallbackAmount ?? config('payment.monthly_amount', 500.0);
        }

        // We assume admission fee logic is tied to the parent's first payment, 
        // but if they add a child, should we charge admission fee again?
        // Let's keep it simple and just use the same logic, checking if user has any payments.
        if ($this->user) {
            $isFirstPayment = !$this->user->payments()->where('status', 'paid')->exists();
            if ($isFirstPayment && $this->current_grade) {
                $gradeNum = 0;
                if (preg_match('/(\d+)/', $this->current_grade, $m)) {
                    $gradeNum = (int)$m[1];
                }
                if ($gradeNum > 0) {
                    $configStr = \App\Models\SiteSetting::get('admission_fees_config', '{}');
                    $config = json_decode($configStr, true) ?? [];
                    
                    if (isset($config[$gradeNum]) && $config[$gradeNum]['enabled']) {
                        $admissionFee = (float)$config[$gradeNum]['amount'];
                        $amount += $admissionFee;
                    }
                }
            }
        }

        return $amount;
    }
}
