<?php

namespace App\Policies;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Models\User;

class ReadingPlanPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ReadingPlan $readingPlan): bool
    {
        return $readingPlan->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, ReadingPlan $readingPlan): bool
    {
        return $readingPlan->user_id === $user->id
            && in_array($readingPlan->status, [
                ReadingPlanStatus::InProgress,
                ReadingPlanStatus::Expired,
            ], true);
    }

    public function complete(User $user, ReadingPlan $readingPlan): bool
    {
        return $readingPlan->user_id === $user->id
            && $readingPlan->status === ReadingPlanStatus::InProgress;
    }

    public function delete(User $user, ReadingPlan $readingPlan): bool
    {
        return $readingPlan->user_id === $user->id;
    }
}
