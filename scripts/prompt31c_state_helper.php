<?php

require __DIR__ . '/../laravel_api/backend/vendor/autoload.php';
$app = require_once __DIR__ . '/../laravel_api/backend/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Institute;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

$action = $argv[1] ?? 'status';

$admin = User::withoutGlobalScopes()->where('role', 'admin')->where('institute_id', 1)->first();
if (!$admin) {
    $admin = User::withoutGlobalScopes()->where('role', 'admin')->first();
}

switch ($action) {
    case 'status':
        $siteName = SiteSetting::getForInstitute('admin_company_name', 1, 'general');
        $notifCount = DB::table('notifications')->where('notifiable_id', $admin->id)->whereNull('read_at')->count();
        echo json_encode([
            'admin_id' => $admin ? $admin->id : null,
            'admin_name' => $admin ? $admin->name : null,
            'admin_avatar' => $admin ? $admin->avatar : null,
            'brand_name' => $siteName,
            'unread_notifications' => $notifCount,
        ], JSON_PRETTY_PRINT) . PHP_EOL;
        break;

    case 'set_avatar_valid':
        if ($admin) {
            $admin->update(['avatar' => 'admin-theme/images/avatar/1.jpg']);
            echo "Admin avatar set to valid image\n";
        }
        break;

    case 'set_avatar_broken':
        if ($admin) {
            $admin->update(['avatar' => 'admin-theme/images/avatar/broken_url_404.jpg']);
            echo "Admin avatar set to broken image\n";
        }
        break;

    case 'set_avatar_null':
        if ($admin) {
            $admin->update(['avatar' => null]);
            echo "Admin avatar set to null\n";
        }
        break;

    case 'set_notifications_99plus':
        if ($admin) {
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
            echo "Seeded 120 unread notifications\n";
        }
        break;

    default:
        echo "Unknown action: {$action}\n";
        break;
}
