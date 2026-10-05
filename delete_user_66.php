<?php
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

$user = \App\Models\User::find(66);
if (!$user) {
    echo "User 66 not found.\n";
    exit;
}

DB::beginTransaction();
try {
    $user->delete();
    DB::commit();
    echo "SUCCESS: User 66 was hard-deleted (no FK constraints violated).\n";
} catch (QueryException $e) {
    DB::rollBack();
    // 23000 is Integrity constraint violation (like foreign key)
    if ($e->getCode() == '23000' || strpos($e->getMessage(), 'foreign key') !== false) {
        echo "FK CONSTRAINT VIOLATION: Cannot delete user due to dependent records. Deactivating instead...\n";
        $user->role = 'user';
        $user->deactivated_at = now();
        $user->save();
        echo "SUCCESS: User 66 was deactivated (role set to 'user', deactivated_at set to now).\n";
    } else {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
}

$userCheck = \App\Models\User::find(66);
if (!$userCheck) {
    echo "VERIFICATION: User 66 is null (deleted).\n";
} else {
    echo "VERIFICATION: User 66 exists. Role: {$userCheck->role}, Deactivated at: " . ($userCheck->deactivated_at ?? 'null') . "\n";
}
