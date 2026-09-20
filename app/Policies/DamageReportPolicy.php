<?php

namespace App\Policies;

use App\Models\DamageReport;
use App\Models\User;

class DamageReportPolicy
{
    public function view(User $user, DamageReport $damageReport): bool
    {
        return $user->id === $damageReport->rental->renter_id
            || $user->id === $damageReport->rental->owner_id
            || $user->isAdmin();
    }

    public function respond(User $user, DamageReport $damageReport): bool
    {
        return $user->id === $damageReport->rental->renter_id && $damageReport->isPending();
    }
}
