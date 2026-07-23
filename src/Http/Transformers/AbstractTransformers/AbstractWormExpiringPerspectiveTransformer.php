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
use NextDeveloper\S3\Database\Models\WormExpiringPerspective;
use NextDeveloper\Commons\Http\Transformers\AbstractTransformer;
use NextDeveloper\IAM\Database\Scopes\AuthorizationScope;

/**
 * Class WormExpiringPerspectiveTransformer. This class is being used to manipulate the data we are serving to the customer
 *
 * @package NextDeveloper\S3\Http\Transformers
 */
class AbstractWormExpiringPerspectiveTransformer extends AbstractTransformer
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
     * @param WormExpiringPerspective $model
     *
     * @return array
     */
    public function transform(WormExpiringPerspective $model)
    {
                                                $s3WormCommitmentId = \NextDeveloper\S3\Database\Models\WormCommitments::where('id', $model->s3_worm_commitment_id)->first();
                                                            $s3BucketId = \NextDeveloper\S3\Database\Models\Buckets::where('id', $model->s3_bucket_id)->first();
                                                            $s3AccountId = \NextDeveloper\S3\Database\Models\Accounts::where('id', $model->s3_account_id)->first();
                                                            $iamAccountId = \NextDeveloper\IAM\Database\Models\Accounts::where('id', $model->iam_account_id)->first();

        return $this->buildPayload(
            [
            'id'  =>  $model->id,
            's3_worm_commitment_id'  =>  $s3WormCommitmentId ? $s3WormCommitmentId->uuid : null,
            's3_worm_commitment_uuid'  =>  $model->s3_worm_commitment_uuid,
            's3_bucket_id'  =>  $s3BucketId ? $s3BucketId->uuid : null,
            's3_account_id'  =>  $s3AccountId ? $s3AccountId->uuid : null,
            'iam_account_id'  =>  $iamAccountId ? $iamAccountId->uuid : null,
            'mode'  =>  $model->mode,
            'retention_days'  =>  $model->retention_days,
            'quota_bytes'  =>  $model->quota_bytes,
            'deposit_amount'  =>  $model->deposit_amount,
            'price_per_gb_mo'  =>  $model->price_per_gb_mo,
            'committed_at'  =>  $model->committed_at,
            'locks_until'  =>  $model->locks_until,
            'status'  =>  $model->status,
            'cancelled_at'  =>  $model->cancelled_at,
            's3_account_slug'  =>  $model->s3_account_slug,
            'bucket_name'  =>  $model->bucket_name,
            'days_until_expiry'  =>  $model->days_until_expiry,
            'is_expired'  =>  $model->is_expired,
            'deposit_refund_estimate'  =>  $model->deposit_refund_estimate,
            ]
        );
    }

    public function includeStates(WormExpiringPerspective $model)
    {
        $states = States::where('object_type', get_class($model))
            ->where('object_id', $model->id)
            ->get();

        return $this->collection($states, new StatesTransformer());
    }

    public function includeActions(WormExpiringPerspective $model)
    {
        $input = get_class($model);
        $input = str_replace('\\Database\\Models', '', $input);

        $actions = AvailableActions::withoutGlobalScope(AuthorizationScope::class)
            ->where('input', $input)
            ->get();

        return $this->collection($actions, new AvailableActionsTransformer());
    }

    public function includeMedia(WormExpiringPerspective $model)
    {
        $media = Media::where('object_type', get_class($model))
            ->where('object_id', $model->id)
            ->get();

        return $this->collection($media, new MediaTransformer());
    }

    public function includeSocialMedia(WormExpiringPerspective $model)
    {
        $socialMedia = SocialMedia::where('object_type', get_class($model))
            ->where('object_id', $model->id)
            ->get();

        return $this->collection($socialMedia, new SocialMediaTransformer());
    }

    public function includeComments(WormExpiringPerspective $model)
    {
        $comments = Comments::where('object_type', get_class($model))
            ->where('object_id', $model->id)
            ->get();

        return $this->collection($comments, new CommentsTransformer());
    }

    public function includeVotes(WormExpiringPerspective $model)
    {
        $votes = Votes::where('object_type', get_class($model))
            ->where('object_id', $model->id)
            ->get();

        return $this->collection($votes, new VotesTransformer());
    }

    public function includeMeta(WormExpiringPerspective $model)
    {
        $meta = Meta::where('object_type', get_class($model))
            ->where('object_id', $model->id)
            ->get();

        return $this->collection($meta, new MetaTransformer());
    }

    public function includePhoneNumbers(WormExpiringPerspective $model)
    {
        $phoneNumbers = PhoneNumbers::where('object_type', get_class($model))
            ->where('object_id', $model->id)
            ->get();

        return $this->collection($phoneNumbers, new PhoneNumbersTransformer());
    }

    public function includeAddresses(WormExpiringPerspective $model)
    {
        $addresses = Addresses::where('object_type', get_class($model))
            ->where('object_id', $model->id)
            ->get();

        return $this->collection($addresses, new AddressesTransformer());
    }
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}
