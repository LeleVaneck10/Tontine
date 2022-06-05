<?php

namespace Siak\Tontine\Service;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

use Siak\Tontine\Model\Fund;
use Siak\Tontine\Model\Session;
use Siak\Tontine\Model\Subscription;

use function array_diff;
use function array_merge;
use function in_array;

class SubscriptionService
{
    use Figures\TableTrait;
    use Events\DebtEventTrait;

    /**
     * @var TenantService
     */
    protected TenantService $tenantService;

    /**
     * @param TenantService $tenantService
     */
    public function __construct(TenantService $tenantService)
    {
        $this->tenantService = $tenantService;
    }

    /**
     * Get funds for the dropdown list.
     *
     * @return Collection
     */
    public function getFunds(): Collection
    {
        return $this->tenantService->round()->funds()->pluck('title', 'id');
    }

    /**
     * Get a single fund.
     *
     * @param int $fundId    The fund id
     *
     * @return Fund|null
     */
    public function getFund(int $fundId): ?Fund
    {
        return $this->tenantService->round()->funds()->find($fundId);
    }

    /**
     * Get the first fund.
     *
     * @return Fund|null
     */
    public function getFirstFund(): ?Fund
    {
        return $this->tenantService->round()->funds()->first();
    }

    /**
     * Get a paginated list of members.
     *
     * @param Fund $fund
     * @param bool $filter
     * @param int $page
     *
     * @return Collection
     */
    public function getMembers(Fund $fund, bool $filter, int $page = 0): Collection
    {
        $members = $this->tenantService->tontine()->members();
        if($filter)
        {
            // Return only members with subscription in this fund
            $members->whereHas('subscriptions', function(Builder $query) use($fund) {
                $query->where('subscriptions.fund_id', $fund->id);
            });
        }
        if($page > 0 )
        {
            $members->take($this->tenantService->getLimit());
            $members->skip($this->tenantService->getLimit() * ($page - 1));
        }
        $members = $members->get();
        foreach($members as &$member)
        {
            $member->subscriptionCount = $member->subscriptions()->where('fund_id', $fund->id)->count();
        }
        return $members;
    }

    /**
     * Get the number of members.
     *
     * @param Fund $fund
     * @param bool $filter
     *
     * @return int
     */
    public function getMemberCount(Fund $fund, bool $filter): int
    {
        $members = $this->tenantService->tontine()->members();
        if($filter)
        {
            // Return only members with subscription in this fund
            $members->whereHas('subscriptions', function(Builder $query) use($fund) {
                $query->where('subscriptions.fund_id', $fund->id);
            });
        }
        return $members->count();
    }

    /**
     * @param Fund $fund
     * @param int $memberId
     *
     * @return int
     */
    public function createSubscription(Fund $fund, int $memberId): int
    {
        $member = $this->tenantService->tontine()->members()->find($memberId);
        $subscription = new Subscription();
        $subscription->title = '';
        $subscription->fund()->associate($fund);
        $subscription->member()->associate($member);

        DB::transaction(function() use($fund, $subscription) {
            // Create the subscription
            $subscription->save();
            $this->subscriptionCreated($fund, $subscription);
        });

        return $subscription->id;
    }

    /**
     * @param Fund $fund
     * @param int $memberId
     *
     * @return int
     */
    public function deleteSubscription(Fund $fund, int $memberId): int
    {
        $subscription = $fund->subscriptions()->where('member_id', $memberId)->first();
        if(!$subscription)
        {
            return 0;
        }

        DB::transaction(function() use($subscription) {
            $this->subscriptionDeleted($subscription);
            // Delete the subscription
            $subscription->delete();
        });

        return $subscription->id;
    }

    /**
     * Enable or disable a session for a fund.
     *
     * @param Fund $fund
     * @param Session $session
     *
     * @return bool
     */
    public function toggleSession(Fund $fund, Session $session): bool
    {
        $sessionIds = $fund->session_ids;
        if(in_array($session->id, $sessionIds))
        {
            $fund->session_ids = array_diff($sessionIds, [$session->id]);
            DB::transaction(function() use($fund, $session) {
                $fund->save();
                $this->fundDetached($fund, $session);
            });
            return true;
        }

        $fund->session_ids = array_merge($sessionIds, [$session->id]);
        DB::transaction(function() use($fund, $session) {
            $fund->save();
            $this->fundAttached($fund, $session);
        });
        return true;
    }

    /**
     * @param Fund $fund
     * @param Session $session
     * @param int $subscriptionId
     *
     * @return void
     */
    private function setPayableSession(Fund $fund, Session $session, int $subscriptionId)
    {
        $subscription = $fund->subscriptions()->find($subscriptionId);
        $subscription->payable->session()->associate($session);
        $subscription->payable->save();
    }

    /**
     * @param Fund $fund
     * @param Session $session
     * @param int $subscriptionId
     *
     * @return void
     */
    private function unsetPayableSession(Fund $fund, Session $session, int $subscriptionId)
    {
        $subscription = $fund->subscriptions()->find($subscriptionId);
        if($subscription->payable->session_id === $session->id)
        {
            $subscription->payable->session()->dissociate();
            $subscription->payable->save();
        }
    }

    /**
     * Set or unset the beneficiary of a given fund.
     *
     * @param Fund $fund
     * @param Session $session
     * @param int $currSubscriptionId
     * @param int $nextSubscriptionId
     *
     * @return void
     */
    public function saveBeneficiary(Fund $fund, Session $session, int $currSubscriptionId, int $nextSubscriptionId)
    {
        DB::transaction(function() use($fund, $session, $currSubscriptionId, $nextSubscriptionId) {
            if($currSubscriptionId > 0)
            {
                $this->unsetPayableSession($fund, $session, $currSubscriptionId);
            }
            if($nextSubscriptionId > 0)
            {
                $this->setPayableSession($fund, $session, $nextSubscriptionId);
            }
        });
    }
}