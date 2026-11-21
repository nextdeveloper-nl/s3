<?php

namespace NextDeveloper\S3\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator as ConcreteLengthAwarePaginator;
use NextDeveloper\Commons\Exceptions\ModelNotFoundException;
use NextDeveloper\IAM\Database\Models\Accounts as IamAccounts;
use NextDeveloper\S3\Database\Filters\BucketsPerspectiveQueryFilter;
use NextDeveloper\S3\Database\Models\BucketsPerspective;
use NextDeveloper\S3\Services\AbstractServices\AbstractBucketsPerspectiveService;

/**
 * This class is responsible from managing the data for BucketsPerspective
 *
 * Class BucketsPerspectiveService.
 *
 * @package NextDeveloper\S3\Database\Models
 */
class BucketsPerspectiveService extends AbstractBucketsPerspectiveService
{

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE

    /**
     * Overrides the generated get() to resolve and apply the iamAccountId filter here
     * instead of leaving it to BucketsPerspectiveQueryFilter::iamAccountId().
     *
     * That generated filter method looks up the IAM account under the caller's own
     * AuthorizationScope; when a staff/admin caller can't see the target customer
     * account through that scope (e.g. the CRM S3 tab), the lookup silently returns
     * null, the method no-ops, and the query falls through completely unfiltered -
     * returning every account's buckets. See #146.
     *
     * We resolve the account with withoutGlobalScopes() (the same pattern already
     * used for internal UUID lookups elsewhere in this codebase, e.g.
     * BucketsService::create()) and apply the where clause directly, so filtering
     * doesn't depend on the caller's own visibility into iam_accounts.
     */
    public static function get(?BucketsPerspectiveQueryFilter $filter = null, array $params = []) : Collection|LengthAwarePaginator
    {
        $iamAccountId = $params['iamAccountId'] ?? $params['iam_account_id'] ?? null;

        if (!$iamAccountId) {
            return parent::get($filter, $params);
        }

        $iamAccount = IamAccounts::withoutGlobalScopes()->where('uuid', $iamAccountId)->first();

        if (!$iamAccount) {
            throw new ModelNotFoundException('Cannot find the IAM account for iamAccountId: ' . $iamAccountId);
        }

        $enablePaginate = array_key_exists('paginate', $params);

        if ($filter == null) {
            $filter = new BucketsPerspectiveQueryFilter(new Request());
        }

        //  Stop the generated filter from re-running its own (scope-restricted) lookup
        //  for the same param - we already resolved and will apply it below.
        $filter->except('iamAccountId', 'iam_account_id');

        if (array_key_exists('orderBy', $params)) {
            $filter->orderBy($params['orderBy']);
        }

        $model = BucketsPerspective::filter($filter)
            ->where('iam_account_id', $iamAccount->id);

        if ($enablePaginate) {
            $perPage = config('commons.pagination.per_page') ?: 20;

            if (array_key_exists('per_page', $params)) {
                $perPage = intval($params['per_page']) ?: 20;
            }

            $modelCount = $model->count();
            $page = array_key_exists('page', $params) ? $params['page'] : 1;
            $items = $model->skip(($page - 1) * $perPage)->take($perPage)->get();

            return new ConcreteLengthAwarePaginator($items, $modelCount, $perPage, $page);
        }

        return $model->get();
    }
}