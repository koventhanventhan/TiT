<?php

namespace App\Services;

use App\Models\User;
use App\Traits\ValidatesEmail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ClassNotifier
{
    use ValidatesEmail;

    /**
     * Get active contactable students matching the class criteria (grade, medium, subject).
     */
    public function getMatchingStudents(?string $classGrade, ?string $classMedium, ?string $classSubject): Collection
    {
        $students = User::where('role', 'user')
            ->whereNull('deactivated_at')
            ->get();

        $matched = collect();

        // Parse class grade
        $classRef = null;
        if ($classGrade) {
            preg_match('/(\d+)/', $classGrade, $classMatch);
            $classRef = isset($classMatch[1]) ? $classMatch[1] : strtoupper(trim($classGrade));
        }

        $classSubject = trim((string)$classSubject);

        foreach ($students as $student) {
            // 1. Grade Match
            if ($classRef !== null) {
                $userGrade = $student->current_grade;
                if (!$userGrade) {
                    continue;
                }
                preg_match('/(\d+)/', $userGrade, $userMatch);
                $userRef = isset($userMatch[1]) ? $userMatch[1] : strtoupper(trim($userGrade));

                if ($userRef !== $classRef) {
                    continue;
                }
            }

            // 2. Medium filter
            if ($classMedium && $classMedium !== 'both' && $student->medium && $student->medium !== $classMedium) {
                continue;
            }

            // 3. Subject filter
            if ($classSubject !== '') {
                $selected = $student->selected_subjects;
                $selectedArr = is_array($selected) ? $selected : (json_decode($selected, true) ?: explode(',', (string)$selected));
                $selectedArr = array_filter(array_map('trim', (array)$selectedArr), fn($val) => $val !== '');

                if (empty($selectedArr)) {
                    continue;
                }

                $subjectMatch = false;
                foreach ($selectedArr as $studentSub) {
                    if ($studentSub === $classSubject || 
                        stripos($studentSub, $classSubject) !== false || 
                        stripos($classSubject, $studentSub) !== false) {
                        $subjectMatch = true;
                        break;
                    }
                }
                if (!$subjectMatch) {
                    continue;
                }
            }

            // 4. Contactable check
            if ($student->phone_number || ($student->email && $this->isValidEmailForSending($student->email))) {
                $matched->push($student);
            }
        }

        return $matched;
    }

    /**
     * Send email-only notification on class creation.
     */
    public function sendClassCreatedEmails(
        string $classTitle,
        string $classTime,
        ?string $subject,
        ?string $grade,
        ?string $joinUrl,
        array $teacherIds,
        array $studentIds
    ): void {
        @set_time_limit(300);

        try {
            $notifier = app(NotificationService::class);
        } catch (\Exception $e) {
            Log::error('NotificationService not available in ClassNotifier: ' . $e->getMessage());
            return;
        }

        $emailsSent = 0;
        
        $data = [
            'class_title' => $classTitle,
            'class_time' => $classTime,
            'subject' => $subject,
            'grade' => $grade,
            'join_url' => $joinUrl,
        ];

        // Notify Teachers
        $teachers = User::whereIn('id', $teacherIds)->get();
        foreach ($teachers as $teacher) {
            try {
                if ($notifier->notifyUserByEmail($teacher, 'class_created', $data)) {
                    $emailsSent++;
                    if ($emailsSent % 5 === 0) sleep(1);
                }
            } catch (\Exception $e) {
                Log::error('Teacher class_created notification failed: ' . $e->getMessage());
            }
        }

        // Notify Students
        $students = User::whereIn('id', $studentIds)->get();
        foreach ($students as $student) {
            try {
                if ($notifier->notifyUserByEmail($student, 'class_created', $data)) {
                    $emailsSent++;
                    if ($emailsSent % 5 === 0) sleep(1);
                }
            } catch (\Exception $e) {
                Log::error('Student class_created notification failed: ' . $e->getMessage());
            }
        }

        Log::info("Class created notification summary: Matched {$teachers->count()} teachers, {$students->count()} students. {$emailsSent} emails sent successfully.");
    }
}
