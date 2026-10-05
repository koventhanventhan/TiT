<?php
echo "--- USER LIST ---\n";
\App\Models\User::whereIn('role', ['super_admin', 'admin', 'teacher'])
    ->orderBy('role')
    ->orderBy('created_at')
    ->get(['id', 'role', 'full_name', 'name', 'email', 'institute_id', 'created_at', 'deactivated_at'])
    ->each(function ($u) {
        echo "#{$u->id} | {$u->role} | {$u->full_name} | {$u->email} | institute:{$u->institute_id} | created:{$u->created_at} | deactivated:" . ($u->deactivated_at ?? 'no') . "\n";
    });

echo "\n--- TOKENABLE IDs ---\n";
$tokens = \Laravel\Sanctum\PersonalAccessToken::whereIn('tokenable_id', 
    \App\Models\User::whereIn('role', ['super_admin', 'admin', 'teacher'])->pluck('id')
)->select('tokenable_id')->distinct()->pluck('tokenable_id');
echo $tokens;
echo "\n";
