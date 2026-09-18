<?php

namespace App\Services;

use App\Models\User;
use App\Models\Subject;
use App\Models\ActivityLog;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StudentPromotionService
{
    protected NotificationService $notifier;

    public function __construct(NotificationService $notifier)
    {
        $this->notifier = $notifier;
    }

    /**
     * Promote a list of students to their next grade.
     *
     * @param array $studentIds List of student IDs to promote.
     * @param bool $force Override the 6-month safeguard.
     * @param int|null $actingAdminId The ID of the admin performing the action (for logs).
     * @param string|null $ipAddress IP address (for logs).
     * @param string|null $userAgent User agent (for logs).
     * @return array Summary counts of the operation.
     * @throws \Exception
     */
    public function promoteStudents(array $studentIds, bool $force = false, ?int $actingAdminId = null, ?string $ipAddress = null, ?string $userAgent = null): array
    {
        $students = User::whereIn('id', $studentIds)
            ->where('role', 'user')
            ->get();

        $promotedCount = 0;
        $flaggedCount = 0;
        $graduatedCount = 0;
        $skippedCount = 0;

        DB::beginTransaction();
        try {
            foreach ($students as $student) {
                // Safeguard against double promotion
                if (!$force && $student->last_promoted_at && Carbon::parse($student->last_promoted_at)->diffInMonths(now()) < 6) {
                    $skippedCount++;
                    continue;
                }

                if ($student->is_graduated) {
                    $skippedCount++;
                    continue;
                }

                $numericGrade = $student->getNumericGrade();
                if ($numericGrade === null) {
                    $skippedCount++;
                    continue;
                }

                $oldGrade = $student->current_grade;

                if ($numericGrade >= 13) {
                    $student->is_graduated = true;
                    $student->last_promoted_at = now();
                    $student->save();
                    
                    if ($actingAdminId) {
                        ActivityLog::create([
                            'institute_id' => $student->institute_id,
                            'user_id' => $actingAdminId,
                            'action' => 'student_graduated',
                            'description' => "Student marked as graduated from {$oldGrade}",
                            'ip_address' => $ipAddress,
                            'user_agent' => $userAgent,
                            'metadata' => ['student_id' => $student->id, 'old_grade' => $oldGrade],
                        ]);
                    }
                    $graduatedCount++;
                    continue;
                }

                // Promote logic
                $newGradeNum = $numericGrade + 1;
                // Preserve the string format, e.g. "7" -> "8"
                $newGradeStr = str_replace((string)$numericGrade, (string)$newGradeNum, $oldGrade);

                $student->current_grade = $newGradeStr;

                // Carry forward subjects
                $currentSubjects = $student->selected_subjects;
                if (is_string($currentSubjects)) {
                    $currentSubjects = json_decode($currentSubjects, true) ?? [];
                }
                if (!is_array($currentSubjects)) {
                    $currentSubjects = [];
                }

                $newCategory = $student->getSubjectCategory();
                $medium = strtolower($student->medium ?? '');

                // Fetch available subjects for new grade + medium
                $availableSubjects = Subject::when($newCategory, function ($query) use ($newCategory) {
                        return $query->where('category', $newCategory);
                    })
                    ->when($medium, function ($query) use ($medium) {
                        return $query->where(function ($q) use ($medium) {
                            $q->where('medium', $medium)->orWhere('medium', 'both');
                        });
                    })
                    ->pluck('name')
                    ->toArray();

                $carriedSubjects = [];
                $droppedSubjects = [];

                foreach ($currentSubjects as $subject) {
                    if (in_array($subject, $availableSubjects)) {
                        $carriedSubjects[] = $subject;
                    } else {
                        $droppedSubjects[] = $subject;
                    }
                }

                $student->selected_subjects = array_values(array_unique($carriedSubjects));
                $needsReview = false;

                if (count($droppedSubjects) > 0 || (count($currentSubjects) > 0 && empty($carriedSubjects))) {
                    $student->needs_subject_review = true;
                    $needsReview = true;
                    $flaggedCount++;

                    $recipient = $student->parent_id ? User::find($student->parent_id) : $student;
                    if ($recipient) {
                        try {
                            $this->notifier->notifyUser(
                                $recipient,
                                'subject_review_required',
                                'subject_review_required',
                                ['student_name' => $student->full_name, 'new_grade' => $newGradeStr],
                                [
                                    'subject' => 'Action Required: Update Subjects for New Academic Year',
                                    'body' => "{$student->full_name} has been promoted to {$newGradeStr}. Some previous subjects are not available in this grade. Please review and update the subjects."
                                ]
                            );
                        } catch (\Exception $e) {
                            Log::error('Failed to send promotion notification: ' . $e->getMessage());
                        }
                    }
                }

                $student->last_promoted_at = now();
                $student->save();

                if ($actingAdminId) {
                    ActivityLog::create([
                        'institute_id' => $student->institute_id,
                        'user_id' => $actingAdminId,
                        'action' => 'student_promoted',
                        'description' => "Promoted student from {$oldGrade} to {$newGradeStr}",
                        'ip_address' => $ipAddress,
                        'user_agent' => $userAgent,
                        'metadata' => [
                            'student_id' => $student->id,
                            'old_grade' => $oldGrade,
                            'new_grade' => $newGradeStr,
                            'dropped_subjects' => $droppedSubjects,
                            'needs_review' => $needsReview
                        ],
                    ]);
                }

                $promotedCount++;
            }

            DB::commit();

            return [
                'promoted' => $promotedCount,
                'flagged' => $flaggedCount,
                'graduated' => $graduatedCount,
                'skipped' => $skippedCount
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
