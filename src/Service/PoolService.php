<?php

namespace Siak\Tontine\Service;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Siak\Tontine\Model\Pool;
use Siak\Tontine\Model\Payable;
use Siak\Tontine\Model\Receivable;

class PoolService
{
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
     * Get a paginated list of pools in the selected round.
     *
     * @param int $page
     *
     * @return array
     */
    public function getPools(int $page = 0)
    {
        $pools = $this->tenantService->round()->pools();
        if($page > 0 )
        {
            $pools->take($this->tenantService->getLimit());
            $pools->skip($this->tenantService->getLimit() * ($page - 1));
        }
        return $pools->get();
    }

    /**
     * Get the number of pools in the selected round.
     *
     * @return int
     */
    public function getPoolCount(): int
    {
        return $this->tenantService->round()->pools()->count();
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
        return $this->tenantService->getPool($poolId);
    }

    /**
     * Add a new pool.
     *
     * @param array $values
     *
     * @return bool
     */
    public function createPools(array $values): bool
    {
        DB::transaction(function() use($values) {
            $this->tenantService->round()->pools()->createMany($values);
        });

        return true;
    }

    /**
     * Update a pool.
     *
     * @param Pool $pool
     * @param array $values
     *
     * @return int
     */
    public function updatePool(Pool $pool, array $values): int
    {
        return $pool->update($values);
    }

    /**
     * Delete a pool.
     *
     * @param Pool $pool
     *
     * @return void
     */
    public function deletePool(Pool $pool)
    {
        // Todo: soft delete this model.
        DB::transaction(function() use($pool) {
            // Delete the payables
            Payable::join('subscriptions', 'subscriptions.id', '=', 'payables.subscription_id')
                ->where('subscriptions.pool_id', $pool->id)
                ->delete();
            // Delete the receivables
            Receivable::join('subscriptions', 'subscriptions.id', '=', 'receivables.subscription_id')
                ->where('subscriptions.pool_id', $pool->id)
                ->delete();
            // Delete the pool
            $pool->delete();
        });
    }

    /**
     * @param int $count
     *
     * @return Collection
     */
    public function getFakePools(int $count): Collection
    {
        return Pool::factory()->count($count)->make([
            'round_id' => $this->tenantService->round(),
        ]);
    }
}