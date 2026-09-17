<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;

// Find all parents (users who are referenced by a parent_id)
$parentIds = User::whereNotNull('parent_id')->pluck('parent_id')->unique();
$parents = User::whereIn('id', $parentIds)->get();

foreach ($parents as $parent) {
    echo "Parent ID: {$parent->id}, Name: {$parent->name}, Avatar: " . ($parent->avatar ?: 'None') . "\n";
    if ($parent->avatar) {
        $parent->avatar = null;
        $parent->save();
        echo " -> Cleared avatar for {$parent->name}\n";
    }
}
echo "Done.\n";
