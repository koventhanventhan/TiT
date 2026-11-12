<?php

namespace App\Services;

use App\Exceptions\PlanLimitReachedException;
use App\Models\Institute;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class StudentImportService
{
    /**
     * Import multiple students from array rows.
     * Checks the WHOLE set first: if rows would exceed the limit, rejects the entire import:
     * "This file has {$count} students but only {$remaining} slots are free" (0 inserted).
     *
     * @param Institute $institute
     * @param array $rows Array of student data arrays
     * @return array Summary of import ['inserted' => int, 'students' => array]
     * @throws PlanLimitReachedException
     */
    public function importStudents(Institute $institute, array $rows): array
    {
        $count = count($rows);
        if ($count === 0) {
            return ['inserted' => 0, 'students' => []];
        }

        $remaining = PlanLimits::remainingStudentSlots($institute);
        if ($remaining !== null && $count > $remaining) {
            throw new PlanLimitReachedException(
                "This file has {$count} students but only {$remaining} slots are free",
                $institute->plan?->limitFor('max_students'),
                PlanUsage::students($institute),
                $count,
                'student'
            );
        }

        return PlanLimits::runWithStudentLock($institute, $count, function () use ($institute, $rows) {
            $inserted = 0;
            $createdUsers = [];

            foreach ($rows as $index => $row) {
                $email = $row['email'] ?? ('import_' . $institute->id . '_' . time() . '_' . $index . '@student.local');
                $name = $row['name'] ?? ($row['full_name'] ?? ('student_' . $index));
                $phone = $row['phone_number'] ?? null;

                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'phone_number' => $phone,
                    'password' => Hash::make($row['password'] ?? 'Student@123'),
                    'role' => 'user',
                    'full_name' => $row['full_name'] ?? $name,
                    'institute_id' => $institute->id,
                    'registration_status' => $row['registration_status'] ?? 'approved',
                    'admin_confirmed_at' => now(),
                    'current_grade' => $row['current_grade'] ?? 'Grade 10',
                ]);

                $createdUsers[] = $user;
                $inserted++;
            }

            return [
                'inserted' => $inserted,
                'students' => $createdUsers,
            ];
        });
    }

    /**
     * Parse a CSV file into rows and import.
     */
    public function importCsv(Institute $institute, string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("CSV file not found: {$filePath}");
        }

        $rows = [];
        if (($handle = fopen($filePath, 'r')) !== false) {
            $headers = null;
            while (($data = fgetcsv($handle, 2000, ',')) !== false) {
                if (!$headers) {
                    $headers = array_map(fn($h) => strtolower(trim(preg_replace('/[\xEF\xBB\xBF]/', '', $h))), $data);
                    continue;
                }
                $row = [];
                foreach ($headers as $i => $h) {
                    $row[$h] = $data[$i] ?? null;
                }
                $rows[] = $row;
            }
            fclose($handle);
        }

        return $this->importStudents($institute, $rows);
    }
}

