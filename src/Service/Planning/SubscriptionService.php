<?php

namespace Siak\Tontine\Service\Planning;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Siak\Tontine\Exception\MessageException;
use Siak\Tontine\Model\Pool;
use Siak\Tontine\Model\Session;
use Siak\Tontine\Model\Subscription;
use Siak\Tontine\Service\Meeting\SessionService;
use Siak\Tontine\Service\TenantService;

use function trans;

class SubscriptionService
{
    /**
     * @var TenantService
     */
    protected TenantService $tenantService;

    /**
     * @var SessionService
     */
    protected SessionService $sessionService;

    /**
     * @param TenantService $tenantService
     * @param SessionService $sessionService
     */
    public function __construct(TenantService $tenantService, SessionService $sessionService)
    {
        $this->tenantService = $tenantService;
        $this->sessionService = $sessionService;
    }

    /**
     * Get pools for the dropdown list.
     *
     * @return Collection
     */
    public function getPools(): Collection
    {
        return $this->tenantService->round()->pools()->whereHas('subscriptions')->pluck('title', 'id');
    }

    /**
     * Get a single pool.
     *
     * @param int $poolId    The pool id
     *
     * @return Pool|null
     */
    public function getPool(int $poolId): ?Pool
    {
        return $this->tenantService->round()->pools()->find($poolId);
    }

    /**
     * Get the first pool.
     *
     * @return Pool|null
     */
    public function getFirstPool(): ?Pool
    {
        return $this->tenantService->round()->pools()->first();
    }

    /**
     * Get a paginated list of members.
     *
     * @param Pool $pool
     * @param bool $filter
     *
     * @return mixed
     */
    public function getQuery(Pool $pool, bool $filter)
    {
        $query = $this->tenantService->tontine()->members();
        if($filter)
        {
            // Return only members with subscription in this pool
            $query = $query->whereHas('subscriptions', function(Builder $query) use($pool) {
                $query->where('subscriptions.pool_id', $pool->id);
            });
        }
        return $query;
    }

    /**
     * Get a paginated list of members.
     *
     * @param Pool $pool
     * @param bool $filter
     * @param int $page
     *
     * @return Collection
     */
    public function getMembers(Pool $pool, bool $filter, int $page = 0): Collection
    {
        $query = $this->getQuery($pool, $filter);
        if($page > 0 )
        {
            $query->take($this->tenantService->getLimit());
            $query->skip($this->tenantService->getLimit() * ($page - 1));
        }
        return $query->withCount([
            'subscriptions' => function(Builder $query) use($pool) {
                $query->where('pool_id', $pool->id);
            },
        ])->get();
    }

    /**
     * Get the number of members.
     *
     * @param Pool $pool
     * @param bool $filter
     *
     * @return int
     */
    public function getMemberCount(Pool $pool, bool $filter): int
    {
        return $this->getQuery($pool, $filter)->count();
    }

    /**
     * @param Pool $pool
     * @param int $memberId
     *
     * @return void
     */
    public function createSubscription(Pool $pool, int $memberId)
    {
        // Cannot modify subscriptions if a session is already opened.
        $this->sessionService->checkActiveSessions();

        $member = $this->tenantService->tontine()->members()->find($memberId);
        $subscription = new Subscription();
        $subscription->title = '';
        $subscription->pool()->associate($pool);
        $subscription->member()->associate($member);

        DB::transaction(function() use($subscription) {
            // Create the subscription
            $subscription->save();
            // Create the payable
            $subscription->payable()->create([]);
        });
    }

    /**
     * @param Pool $pool
     * @param int $memberId
     *
     * @return void
     */
    public function deleteSubscription(Pool $pool, int $memberId)
    {
        // Cannot modify subscriptions if a session is already opened.
        $this->sessionService->checkActiveSessions();

        $subscription = $pool->subscriptions()->where('member_id', $memberId)->first();
        if(!$subscription)
        {
            throw new MessageException(trans('tontine.subscription.errors.not_found'));
        }

        DB::transaction(function() use($subscription) {
            // Delete the payable
            $subscription->payable()->delete();
            // Delete the subscription
            $subscription->delete();
        });
    }

    /**
     * Get the number of subscriptions.
     *
     * @param Pool $pool
     *
     * @return int
     */
    public function getSubscriptionCount(Pool $pool): int
    {
        return $pool->subscriptions()->count();
    }

    /**
     * @param Subscription $subscription
     * @param Session $session
     *
     * @return void
     */
    public function setPayableSession(Subscription $subscription, Session $session)
    {
        $subscription->payable->session()->associate($session);
        $subscription->payable->save();
    }

    /**
     * @param Subscription $subscription
     *
     * @return void
     */
    public function unsetPayableSession(Subscription $subscription)
    {
        if(($subscription->payable->session_id))
        {
            $subscription->payable->session()->dissociate();
            $subscription->payable->save();
        }
    }

    /**
     * Set or unset the beneficiary of a given pool.
     *
     * @param Pool $pool
     * @param Session $session
     * @param int $currSubscriptionId
     * @param int $nextSubscriptionId
     *
     * @return void
     */
    public function saveBeneficiary(Pool $pool, Session $session, int $currSubscriptionId, int $nextSubscriptionId)
    {
        DB::transaction(function() use($pool, $session, $currSubscriptionId, $nextSubscriptionId) {
            // If the beneficiary already has a session assigned, first remove it.
            if($currSubscriptionId > 0)
            {
                $subscription = $pool->subscriptions()->find($currSubscriptionId);
                $this->unsetPayableSession($subscription);
            }
            // If there is a new session assigned to the beneficiary, then save it.
            if($nextSubscriptionId > 0)
            {
                $subscription = $pool->subscriptions()->find($nextSubscriptionId);
                $this->setPayableSession($subscription, $session);
            }
        });
    }
}
