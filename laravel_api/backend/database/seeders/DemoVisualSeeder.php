<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Institute;
use App\Models\SiteSetting;
use App\Models\Payment;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoVisualSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ensure Institute 1 has a realistic wide brand name
        $institute = Institute::find(1);
        if (!$institute) {
            $institute = Institute::create([
                'id' => 1,
                'name' => 'TiT International Educational Academy Jaffna',
                'slug' => 'tit',
                'status' => 'active',
            ]);
        } else {
            $institute->update([
                'name' => 'TiT International Educational Academy Jaffna',
            ]);
        }

        SiteSetting::setForInstitute('admin_company_name', 'TiT International Educational Academy Jaffna', 1, 'general');

        // 2. Ensure Admin User with realistic long name (40+ characters)
        $admin = User::withoutGlobalScopes()->where('role', 'admin')->where('institute_id', 1)->first();
        if (!$admin) {
            $admin = User::create([
                'name' => 'Dr. Kajanthan Sivaneswaranathan MBBS MSc',
                'first_name' => 'Kajanthan',
                'last_name' => 'Sivaneswaranathan',
                'email' => 'admin@tit.lk',
                'password' => 'Admin@12345678', // Hashed via cast
                'role' => 'admin',
                'institute_id' => 1,
                'email_verified_at' => now(),
            ]);
        } else {
            $admin->update([
                'name' => 'Dr. Kajanthan Sivaneswaranathan MBBS MSc',
                'first_name' => 'Kajanthan',
                'last_name' => 'Sivaneswaranathan',
            ]);
        }

        // 3. Clear existing demo notifications and seed 120 unread notifications for badge '99+' testing
        DB::table('notifications')->where('notifiable_id', $admin->id)->delete();
        $notifications = [];
        for ($n = 1; $n <= 120; $n++) {
            $notifications[] = [
                'id' => Str::uuid()->toString(),
                'type' => 'App\\Notifications\\NewStudentRegisteredNotification',
                'notifiable_type' => 'App\\Models\\User',
                'notifiable_id' => $admin->id,
                'data' => json_encode([
                    'message' => "Student registration request #{$n} pending approval",
                    'link' => '/admin/students',
                ]),
                'read_at' => null,
                'created_at' => now()->subMinutes($n),
                'updated_at' => now()->subMinutes($n),
            ];
        }
        foreach (array_chunk($notifications, 50) as $chunk) {
            DB::table('notifications')->insert($chunk);
        }

        // 4. Seed 200 Students with realistic English, Tamil, and Sinhala names
        $firstNamesEn = ['Karthigesu', 'Sivaneswaran', 'Ravichandran', 'Tharmalingam', 'Ketheeswaran', 'Balasubramaniam', 'Nithiyananthan', 'Gnanasekaram', 'Thirunavukkarasu', 'Selvakumar'];
        $lastNamesEn = ['Shanmuganathan', 'Vaidyanathan', 'Kandasamy', 'Manickavasagar', 'Gunaratnam', 'Paramasivam', 'Sivanandam', 'Arulananthan', 'Chandrasekaran', 'Muthukumarasamy'];
        
        $tamilNames = [
            'கார்த்திகேசு சண்முகநாதன்',
            'ரவிச்சந்திரன் சிவநேஸ்வரன்',
            'தர்மலிங்கம் சிவகுமார்',
            'பாலசுப்பிரமணியம் கணேசன்',
            'சுப்பிரமணியம் திருநாவுக்கரசு',
            'விஜயரத்தினம் கந்தசாமி',
            'செந்தில்வேலன் முத்துக்குமாரசாமி',
            'சங்கீதா பரமசிவம்',
            'கவிதா சிவநேஸ்வரன்',
            'பிரியதர்ஷினி ஆனந்தராஜா'
        ];

        $sinhalaNames = [
            'දිනුක ප්‍රනාන්දු',
            'චමින්ද බණ්ඩාර වික්‍රමසිංහ',
            'කසුන් සම්පත් ජයවර්ධන',
            'රුවන්ත ප්‍රියදර්ශන සිල්වා',
            'ලහිරු මධුශංක විජේසිංහ',
            'තරිඳු සඳරුවන් ගුණසේකර',
            'සචිත්‍ර නිමේෂ විතානගේ',
            'ඉනෝකා දමයන්ති මෙන්ඩිස්',
            'සඳලි මධුෂිකා පෙරේරා',
            'චතුරිකා ලක්මාලි සෙනෙවිරත්න'
        ];

        $existingStudentCount = User::withoutGlobalScopes()->where('role', 'user')->where('institute_id', 1)->count();
        $targetCount = 200;
        $needed = max(0, $targetCount - $existingStudentCount);

        if ($needed > 0) {
            $studentsData = [];
            for ($i = 1; $i <= $needed; $i++) {
                $idx = $existingStudentCount + $i;
                $nameType = $idx % 3;
                if ($nameType === 0) {
                    $fullName = $tamilNames[($idx) % count($tamilNames)] . ' - ' . sprintf('%03d', $idx);
                    $firstName = explode(' ', $fullName)[0];
                    $lastName = explode(' ', $fullName)[1] ?? 'Student';
                } elseif ($nameType === 1) {
                    $fullName = $sinhalaNames[($idx) % count($sinhalaNames)] . ' - ' . sprintf('%03d', $idx);
                    $firstName = explode(' ', $fullName)[0];
                    $lastName = explode(' ', $fullName)[1] ?? 'Student';
                } else {
                    $fn = $firstNamesEn[$idx % count($firstNamesEn)];
                    $ln = $lastNamesEn[$idx % count($lastNamesEn)];
                    $fullName = "{$fn} {$ln} (Candidate {$idx})";
                    $firstName = $fn;
                    $lastName = $ln;
                }

                $avatarType = $idx % 3;
                $avatar = null;
                if ($avatarType === 1) {
                    $avatar = 'admin-theme/images/avatar/1.jpg'; // Real local image
                } elseif ($avatarType === 2) {
                    $avatar = 'admin-theme/images/avatar/broken_url_404.jpg'; // Broken image to test onerror fallback
                }

                $grade = ($idx % 8) + 6; // Grade 6 to 13
                $medium = ['english', 'tamil'][$idx % 2];
                $hasIndex = ($idx % 2 === 0);
                $indexNumber = $hasIndex ? sprintf('TIT-2026-%04d', $idx) : null;

                $user = User::create([
                    'institute_id' => 1,
                    'name' => $fullName,
                    'full_name' => $fullName,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => "student{$idx}@tit.lk",
                    'password' => 'Student@12345',
                    'phone_number' => '077' . sprintf('%07d', 1000000 + $idx),
                    'role' => 'user',
                    'current_grade' => (string)$grade,
                    'batch_year' => 26,
                    'medium' => $medium,
                    'avatar' => $avatar,
                    'index_number' => $indexNumber,
                    'selected_subjects' => json_encode(['Mathematics', 'Science', 'English']),
                    'admin_confirmed_at' => ($idx % 5 === 0) ? null : now(),
                    'email_verified_at' => now(),
                ]);

                // Also seed payment for student
                if ($idx % 2 === 0) {
                    Payment::create([
                        'institute_id' => 1,
                        'user_id' => $user->id,
                        'amount' => 2500,
                        'status' => 'paid',
                        'payment_method' => 'cash',
                        'year_month' => now()->format('Y-m'),
                        'paid_at' => now(),
                    ]);
                } elseif ($idx % 3 === 0) {
                    Payment::create([
                        'institute_id' => 1,
                        'user_id' => $user->id,
                        'amount' => 2500,
                        'status' => 'pending',
                        'payment_method' => 'bank_transfer',
                        'year_month' => now()->format('Y-m'),
                    ]);
                }
            }
        }
    }
}
