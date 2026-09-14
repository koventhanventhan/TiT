<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

trait HandlesDuplicateAccounts
{
    /**
     * Checks if a parent account exists for the given phone or email.
     * If found and the parent has completed initial registration, returns a JSON response 
     * to trigger the OTP merge flow. Returns null if no duplicate is found.
     */
    protected function checkAndHandleDuplicateParent(?string $phoneNumber, ?string $email, int $instituteId, int $excludeUserId = 0)
    {
        $matchedParent = null;
        if ($phoneNumber) {
            $matchedParent = User::where('phone_number', $phoneNumber)
                ->where('role', 'user')
                ->whereNull('parent_id')
                ->where('institute_id', $instituteId)
                ->where('id', '!=', $excludeUserId)
                ->first();
        }
        
        if (!$matchedParent && $email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $matchedParent = User::where('email', $email)
                ->where('role', 'user')
                ->whereNull('parent_id')
                ->where('institute_id', $instituteId)
                ->where('id', '!=', $excludeUserId)
                ->first();
        }

        if ($matchedParent) {
            // Only trigger merge if the parent account has completed initial registration
            // (i.e. full_name is not null). If it is null, we want to let the original 
            // logic handle it (which allows re-registration of incomplete accounts).
            if ($matchedParent->full_name === null) {
                return null;
            }

            $useWhatsApp = config('services.whatsapp.enabled', false);
            
            $maskedContact = ($matchedParent->phone_number && $useWhatsApp)
                ? '+' . substr($matchedParent->phone_number, 0, 4) . ' *** *** ' . substr($matchedParent->phone_number, -4)
                : substr($matchedParent->email, 0, 1) . '****@' . explode('@', $matchedParent->email)[1];
                
            $mergeToken = (string) Str::uuid();
            Cache::put('merge_token_' . $mergeToken, $matchedParent->id, now()->addMinutes(10));

            return response()->json([
                'status' => 'existing_account_found',
                'merge_token' => $mergeToken,
                'masked_contact' => $maskedContact,
                'message' => 'An account with this contact already exists. Please verify to add this student to that family account.'
            ], 409);
        }

        return null;
    }
}
