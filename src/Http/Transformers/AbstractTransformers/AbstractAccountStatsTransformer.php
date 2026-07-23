<?php

namespace NextDeveloper\S3\Http\Transformers\AbstractTransformers;

use NextDeveloper\Commons\Database\Models\Addresses;
use NextDeveloper\Commons\Database\Models\Comments;
use NextDeveloper\Commons\Database\Models\Meta;
use NextDeveloper\Commons\Database\Models\PhoneNumbers;
use NextDeveloper\Commons\Database\Models\SocialMedia;
use NextDeveloper\Commons\Database\Models\Votes;
use NextDeveloper\Commons\Database\Models\Media;
use NextDeveloper\Commons\Http\Transformers\MediaTransformer;
use NextDeveloper\Commons\Database\Models\AvailableActions;
use NextDeveloper\Commons\Http\Transformers\AvailableActionsTransformer;
use NextDeveloper\Commons\Database\Models\States;
use NextDeveloper\Commons\Http\Transformers\StatesTransformer;
use NextDeveloper\Commons\Http\Transformers\CommentsTransformer;
use NextDeveloper\Commons\Http\Transformers\SocialMediaTransformer;
use NextDeveloper\Commons\Http\Transformers\MetaTransformer;
use NextDeveloper\Commons\Http\Transformers\VotesTransformer;
use NextDeveloper\Commons\Http\Transformers\AddressesTransformer;
use NextDeveloper\Commons\Http\Transformers\PhoneNumbersTransformer;
use NextDeveloper\S3\Database\Models\AccountStats;
use NextDeveloper\Commons\Http\Transformers\AbstractTransformer;
use NextDeveloper\IAM\Database\Scopes\AuthorizationScope;

/**
 * Class AccountStatsTransformer. This class is being used to manipulate the data we are serving to the customer
 *
 * @package NextDeveloper\S3\Http\Transformers
 */
class AbstractAccountStatsTransformer extends AbstractTransformer
{

    /**
     * @var array
     */
    protected array $availableIncludes = [
        'states',
        'actions',
        'media',
        'comments',
        'votes',
        'socialMedia',
        'phoneNumbers',
        'addresses',
        'meta'
    ];

    /**
     * @param AccountStats $model
     *
     * @return array
     */
    public function transform(AccountStats $model)
    {
                                                $s3AccountId = \NextDeveloper\S3\Database\Models\Accounts::where('id', $model->s3_account_id)->first();
                                                            $iamAccountId = \NextDeveloper\IAM\Database\Models\Accounts::where('id', $model->iam_account_id)->first();

        return $this->buildPayload(
            [
            'id'  =>  $model->id,
            's3_account_id'  =>  $s3AccountId ? $s3AccountId->uuid : null,
            's3_account_uuid'  =>  $model->s3_account_uuid,
            'iam_account_id'  =>  $iamAccountId ? $iamAccountId->uuid : null,
            'slug'  =>  $model->slug,
            'status'  =>  $model->status,
            'blocked_at'  =>  $model->blocked_at,
            'blocked_reason'  =>  $model->blocked_reason,
            'usage_checked_at'  =>  $model->usage_checked_at,
            'quota_storage_bytes'  =>  $model->quota_storage_bytes,
            'storage_bytes_used'  =>  $model->storage_bytes_used,
            'storage_pct'  =>  $model->storage_pct,
            'quota_egress_bytes_mo'  =>  $model->quota_egress_bytes_mo,
            'egress_bytes_mo_used'  =>  $model->egress_bytes_mo_used,
            'egress_pct'  =>  $model->egress_pct,
            'quota_max_objects'  =>  $model->quota_max_objects,
            'object_count'  =>  $model->object_count,
            'object_pct'  =>  $model->object_pct,
            'quota_max_buckets'  =>  $model->quota_max_buckets,
            'bucket_count'  =>  $model->bucket_count,
            'bucket_pct'  =>  $model->bucket_pct,
            'active_key_count'  =>  $model->active_key_count,
            'in_progress_upload_count'  =>  $model->in_progress_upload_count,
            'paused_webhook_count'  =>  $model->paused_webhook_count,
            'current_month_egress_bytes'  =>  $model->current_month_egress_bytes,
            'current_month_ingress_bytes'  =>  $model->current_month_ingress_bytes,
            ]
        );
    }

    public function includeStates(AccountStats $model)
    {
        $states = States::where('object_type', get_class($model))
            ->where('object_id', $model->id)
            ->get();

        return $this->collection($states, new StatesTransformer());
    }

    public function includeActions(AccountStats $model)
    {
        $input = get_class($model);
        $input = str_replace('\\Database\\Models', '', $input);

        $actions = AvailableActions::withoutGlobalScope(AuthorizationScope::class)
            ->where('input', $input)
            ->get();

        return $this->collection($actions, new AvailableActionsTransformer());
    }

    public function includeMedia(AccountStats $model)
    {
        $media = Media::where('object_type', get_class($model))
            ->where('object_id', $model->id)
            ->get();

        return $this->collection($media, new MediaTransformer());
    }

    public function includeSocialMedia(AccountStats $model)
    {
        $socialMedia = SocialMedia::where('object_type', get_class($model))
            ->where('object_id', $model->id)
            ->get();

        return $this->collection($socialMedia, new SocialMediaTransformer());
    }

    public function includeComments(AccountStats $model)
    {
        $comments = Comments::where('object_type', get_class($model))
            ->where('object_id', $model->id)
            ->get();

        return $this->collection($comments, new CommentsTransformer());
    }

    public function includeVotes(AccountStats $model)
    {
        $votes = Votes::where('object_type', get_class($model))
            ->where('object_id', $model->id)
            ->get();

        return $this->collection($votes, new VotesTransformer());
    }

    public function includeMeta(AccountStats $model)
    {
        $meta = Meta::where('object_type', get_class($model))
            ->where('object_id', $model->id)
            ->get();

        return $this->collection($meta, new MetaTransformer());
    }

    public function includePhoneNumbers(AccountStats $model)
    {
        $phoneNumbers = PhoneNumbers::where('object_type', get_class($model))
            ->where('object_id', $model->id)
            ->get();

        return $this->collection($phoneNumbers, new PhoneNumbersTransformer());
    }

    public function includeAddresses(AccountStats $model)
    {
        $addresses = Addresses::where('object_type', get_class($model))
            ->where('object_id', $model->id)
            ->get();

        return $this->collection($addresses, new AddressesTransformer());
    }
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}
