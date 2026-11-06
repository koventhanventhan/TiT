<?php

namespace App\Policies;

use App\Models\InstituteIntegration;
use App\Models\User;

class InstituteIntegrationPolicy
{
    /**
     * Only an institute's own `admin` user may view or manage that institute's integrations.
     */
    public function manage(User $user, ?InstituteIntegration $integration = null): bool
    {
        if ($user->role !== 'admin' || empty($user->institute_id)) {
            return false;
        }

        if ($integration !== null && (int) $integration->institute_id !== (int) $user->institute_id) {
            return false;
        }

        return true;
    }
}

