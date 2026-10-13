<?php

namespace App\Support;

use App\Models\User;
use App\Models\ZoomSchedule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class StudentClassVisibility
{
    /**
     * Determine if a single ZoomSchedule is visible to the student.
     */
    public static function isVisible(User $user, ZoomSchedule $schedule): bool
    {
        // 1. Medium check (English/Tamil) - also includes 'both'
        if ($user->medium) {
            $classMedium = strtolower(trim((string) $schedule->medium));
            $studentMedium = strtolower(trim((string) $user->medium));
            if ($classMedium !== 'both' && $classMedium !== $studentMedium) {
                return false;
            }
        }

        // 2. Grade check (Normalize strings like "Grade 10" or "O/L" or "A/L")
        $userGrade = $user->current_grade;
        $classGrade = $schedule->grade;

        if (!$userGrade || !$classGrade) {
            return false;
        }

        preg_match('/(\d+)/', (string) $userGrade, $userMatch);
        $userRef = isset($userMatch[1]) ? $userMatch[1] : strtoupper(trim((string) $userGrade));

        preg_match('/(\d+)/', (string) $classGrade, $classMatch);
        $classRef = isset($classMatch[1]) ? $classMatch[1] : strtoupper(trim((string) $classGrade));

        if ($userRef !== $classRef) {
            return false;
        }

        // 3. Filter by student's selected subjects (Robust substring match)
        $selected = $user->selected_subjects;
        if (!empty($selected)) {
            $selectedArr = is_array($selected) ? $selected : (json_decode($selected, true) ?: explode(',', (string) $selected));
            $selectedArr = array_map('trim', (array) $selectedArr);

            $classSub = trim((string) $schedule->subject);
            if (!empty($classSub)) {
                $matched = false;
                foreach ($selectedArr as $studentSub) {
                    $studentSub = trim((string) $studentSub);
                    if ($studentSub === '') {
                        continue;
                    }
                    if ($studentSub === $classSub || 
                        stripos($studentSub, $classSub) !== false || 
                        stripos($classSub, $studentSub) !== false) {
                        $matched = true;
                        break;
                    }
                }
                if (!$matched) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Filter a query or collection for a student.
     *
     * @param User $user
     * @param Builder|\Illuminate\Database\Query\Builder|Collection|iterable $queryOrCollection
     * @return Collection
     */
    public static function forStudent(User $user, $queryOrCollection): Collection
    {
        if ($queryOrCollection instanceof Builder || $queryOrCollection instanceof \Illuminate\Database\Query\Builder) {
            if ($user->medium) {
                $queryOrCollection->whereIn('medium', [$user->medium, 'both']);
            }
            $collection = $queryOrCollection->get();
        } else {
            $collection = collect($queryOrCollection);
            if ($user->medium) {
                $collection = $collection->filter(function ($s) use ($user) {
                    $m = strtolower(trim((string) ($s->medium ?? '')));
                    return $m === 'both' || $m === strtolower(trim((string) $user->medium));
                });
            }
        }

        return $collection->filter(function ($s) use ($user) {
            return self::isVisible($user, $s);
        })->values();
    }
}
