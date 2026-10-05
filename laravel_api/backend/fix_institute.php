$users = \App\Models\User::whereNotNull('custom_fields')->get();
$count = 0;
foreach ($users as $user) {
    $fields = is_string($user->custom_fields) ? json_decode($user->custom_fields, true) : $user->custom_fields;
    if (!is_array($fields)) continue;
    if (array_key_exists('current_institute', $fields)) {
        unset($fields['current_institute']);
        $user->custom_fields = empty($fields) ? null : $fields;
        $user->save();
        $count++;
    }
}
echo 'Fixed ' . $count . " records.\n";
