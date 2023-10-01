<?php

namespace App\Jobs;

use App\Contracts\SubscriptionAble;
use App\Models\Subscription;
use App\Models\User;
use Ramsey\Uuid\Uuid;

final class SubscribeToSubscriptionAble
{
    public function __construct(private User $user, private SubscriptionAble $subscriptionAble)
    {
    }

    public function handle(): void
    {
        $subscription = new Subscription();
        $subscription->uuid = Uuid::uuid4()->toString();
        $subscription->userRelation()->associate($this->user);
        $this->subscriptionAble->subscriptionsRelation()->save($subscription);
    }
}
