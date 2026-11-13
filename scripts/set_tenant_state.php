<?php

require __DIR__ . '/../laravel_api/backend/vendor/autoload.php';
$app = require_once __DIR__ . '/../laravel_api/backend/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Institute;
use App\Models\InstituteDomain;
use Illuminate\Support\Facades\Cache;

$state = $argv[1] ?? 'init';

switch ($state) {
    case 'init':
        $inst1 = Institute::firstOrCreate(['id' => 1], [
            'name' => 'TiT Primary Institute',
            'slug' => 'tit',
            'status' => 'active',
        ]);
        $inst1->update(['name' => 'TiT Primary Institute', 'status' => 'active', 'expires_at' => null]);

        InstituteDomain::updateOrCreate(
            ['domain' => 'tit.test'],
            ['institute_id' => 1, 'is_primary' => false, 'verified_at' => now()]
        );

        $inst2 = Institute::find(2);
        if (!$inst2) {
            $inst2 = Institute::create([
                'id' => 2,
                'name' => 'Apex High Academy',
                'slug' => 'beta',
                'status' => 'active',
            ]);
        } else {
            $inst2->update([
                'name' => 'Apex High Academy',
                'slug' => 'beta',
                'status' => 'active',
            ]);
        }

        InstituteDomain::updateOrCreate(
            ['domain' => 'beta.test'],
            ['institute_id' => 2, 'is_primary' => true, 'verified_at' => now()]
        );

        Cache::flush();
        echo "State 'init' applied: Institute 1 (tit.test) and Institute 2 (beta.test) configured.\n";
        break;

    case 'within_grace':
        $inst2 = Institute::findOrFail(2);
        $inst2->update([
            'status' => 'active',
            'expires_at' => now()->subDays(2),
        ]);
        Cache::flush();
        echo "State 'within_grace' applied: Institute 2 expires_at set to 2 days ago.\n";
        break;

    case 'past_grace':
        $inst2 = Institute::findOrFail(2);
        $inst2->update([
            'status' => 'active',
            'expires_at' => now()->subDays(30),
        ]);
        Cache::flush();
        echo "State 'past_grace' applied: Institute 2 expires_at set to 30 days ago (past grace).\n";
        break;

    case 'suspended':
        $inst2 = Institute::findOrFail(2);
        $inst2->update([
            'status' => 'suspended',
            'expires_at' => now()->addDays(30),
        ]);
        Cache::flush();
        echo "State 'suspended' applied: Institute 2 status set to suspended.\n";
        break;

    case 'renewed':
        $inst2 = Institute::findOrFail(2);
        $inst2->update(['status' => 'expired']);
        $inst2->renewSubscription(['duration' => '30_days']);
        Cache::flush();
        echo "State 'renewed' applied: Institute 2 renewed via renewSubscription.\n";
        break;

    case 'institute1_past_expires':
        $inst1 = Institute::findOrFail(1);
        $inst1->update([
            'status' => 'active',
            'expires_at' => now()->subDays(60),
        ]);
        Cache::flush();
        echo "State 'institute1_past_expires' applied: Institute 1 expires_at set to 60 days ago.\n";
        break;

    default:
        echo "Unknown state: {$state}\n";
        exit(1);
}
