<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Subject;
use App\Models\ActivityLog;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminPromotionController extends Controller
{
    public function promoteStudents(Request $request)
    {
        $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'integer|exists:users,id',
            'force' => 'boolean', // To override the 6-month safeguard
        ]);

        $studentIds = array_unique($request->student_ids);
        $force = $request->input('force', false);

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
                    
                    ActivityLog::create([
                        'institute_id' => $student->institute_id,
                        'user_id' => auth()->id(),
                        'action' => 'student_graduated',
                        'description' => "Student marked as graduated from {$oldGrade}",
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'metadata' => ['student_id' => $student->id, 'old_grade' => $oldGrade],
                    ]);
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
                            app(NotificationService::class)->notifyUser(
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
                            \Illuminate\Support\Facades\Log::error('Failed to send promotion notification: ' . $e->getMessage());
                        }
                    }
                }

                $student->last_promoted_at = now();
                $student->save();

                ActivityLog::create([
                    'institute_id' => $student->institute_id,
                    'user_id' => auth()->id(),
                    'action' => 'student_promoted',
                    'description' => "Promoted student from {$oldGrade} to {$newGradeStr}",
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'metadata' => [
                        'student_id' => $student->id,
                        'old_grade' => $oldGrade,
                        'new_grade' => $newGradeStr,
                        'dropped_subjects' => $droppedSubjects,
                        'needs_review' => $needsReview
                    ],
                ]);

                $promotedCount++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Promotion complete.",
                'summary' => [
                    'promoted' => $promotedCount,
                    'flagged' => $flaggedCount,
                    'graduated' => $graduatedCount,
                    'skipped' => $skippedCount
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during promotion: ' . $e->getMessage(),
            ], 500);
        }
    }
}
