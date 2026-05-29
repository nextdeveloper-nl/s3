<?php

namespace NextDeveloper\S3\Tests\Database\Models;

use Tests\TestCase;
use GuzzleHttp\Client;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use NextDeveloper\S3\Database\Filters\S3WormCommitmentQueryFilter;
use NextDeveloper\S3\Services\AbstractServices\AbstractS3WormCommitmentService;
use Illuminate\Pagination\LengthAwarePaginator;
use League\Fractal\Resource\Collection;

trait S3WormCommitmentTestTraits
{
    public $http;

    /**
     *   Creating the Guzzle object
     */
    public function setupGuzzle()
    {
        $this->http = new Client(
            [
            'base_uri'  =>  '127.0.0.1:8000'
            ]
        );
    }

    /**
     *   Destroying the Guzzle object
     */
    public function destroyGuzzle()
    {
        $this->http = null;
    }

    public function test_http_s3wormcommitment_get()
    {
        $this->setupGuzzle();
        $response = $this->http->request(
            'GET',
            '/s3/s3wormcommitment',
            ['http_errors' => false]
        );

        $this->assertContains(
            $response->getStatusCode(), [
            Response::HTTP_OK,
            Response::HTTP_NOT_FOUND
            ]
        );
    }

    public function test_http_s3wormcommitment_post()
    {
        $this->setupGuzzle();
        $response = $this->http->request(
            'POST', '/s3/s3wormcommitment', [
            'form_params'   =>  [
                'mode'  =>  'a',
                'status'  =>  'a',
                'retention_days'  =>  '1',
                'quota_bytes'  =>  '1',
                    'committed_at'  =>  now(),
                    'locks_until'  =>  now(),
                    'cancelled_at'  =>  now(),
                    'expired_at'  =>  now(),
                    'purged_at'  =>  now(),
                        ],
                ['http_errors' => false]
            ]
        );

        $this->assertEquals($response->getStatusCode(), Response::HTTP_OK);
    }

    /**
     * Get test
     *
     * @return bool
     */
    public function test_s3wormcommitment_model_get()
    {
        $result = AbstractS3WormCommitmentService::get();

        $this->assertIsObject($result, Collection::class);
    }

    public function test_s3wormcommitment_get_all()
    {
        $result = AbstractS3WormCommitmentService::getAll();

        $this->assertIsObject($result, Collection::class);
    }

    public function test_s3wormcommitment_get_paginated()
    {
        $result = AbstractS3WormCommitmentService::get(
            null, [
            'paginated' =>  'true'
            ]
        );

        $this->assertIsObject($result, LengthAwarePaginator::class);
    }

    public function test_s3wormcommitment_event_retrieved_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3WormCommitment\S3WormCommitmentRetrievedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3wormcommitment_event_created_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3WormCommitment\S3WormCommitmentCreatedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3wormcommitment_event_creating_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3WormCommitment\S3WormCommitmentCreatingEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3wormcommitment_event_saving_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3WormCommitment\S3WormCommitmentSavingEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3wormcommitment_event_saved_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3WormCommitment\S3WormCommitmentSavedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3wormcommitment_event_updating_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3WormCommitment\S3WormCommitmentUpdatingEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3wormcommitment_event_updated_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3WormCommitment\S3WormCommitmentUpdatedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3wormcommitment_event_deleting_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3WormCommitment\S3WormCommitmentDeletingEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3wormcommitment_event_deleted_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3WormCommitment\S3WormCommitmentDeletedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3wormcommitment_event_restoring_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3WormCommitment\S3WormCommitmentRestoringEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3wormcommitment_event_restored_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3WormCommitment\S3WormCommitmentRestoredEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3wormcommitment_event_retrieved_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3WormCommitment::first();

            event(new \NextDeveloper\S3\Events\S3WormCommitment\S3WormCommitmentRetrievedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3wormcommitment_event_created_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3WormCommitment::first();

            event(new \NextDeveloper\S3\Events\S3WormCommitment\S3WormCommitmentCreatedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3wormcommitment_event_creating_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3WormCommitment::first();

            event(new \NextDeveloper\S3\Events\S3WormCommitment\S3WormCommitmentCreatingEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3wormcommitment_event_saving_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3WormCommitment::first();

            event(new \NextDeveloper\S3\Events\S3WormCommitment\S3WormCommitmentSavingEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3wormcommitment_event_saved_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3WormCommitment::first();

            event(new \NextDeveloper\S3\Events\S3WormCommitment\S3WormCommitmentSavedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3wormcommitment_event_updating_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3WormCommitment::first();

            event(new \NextDeveloper\S3\Events\S3WormCommitment\S3WormCommitmentUpdatingEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3wormcommitment_event_updated_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3WormCommitment::first();

            event(new \NextDeveloper\S3\Events\S3WormCommitment\S3WormCommitmentUpdatedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3wormcommitment_event_deleting_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3WormCommitment::first();

            event(new \NextDeveloper\S3\Events\S3WormCommitment\S3WormCommitmentDeletingEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3wormcommitment_event_deleted_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3WormCommitment::first();

            event(new \NextDeveloper\S3\Events\S3WormCommitment\S3WormCommitmentDeletedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3wormcommitment_event_restoring_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3WormCommitment::first();

            event(new \NextDeveloper\S3\Events\S3WormCommitment\S3WormCommitmentRestoringEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3wormcommitment_event_restored_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3WormCommitment::first();

            event(new \NextDeveloper\S3\Events\S3WormCommitment\S3WormCommitmentRestoredEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3wormcommitment_event_mode_filter()
    {
        try {
            $request = new Request(
                [
                'mode'  =>  'a'
                ]
            );

            $filter = new S3WormCommitmentQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3WormCommitment::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3wormcommitment_event_status_filter()
    {
        try {
            $request = new Request(
                [
                'status'  =>  'a'
                ]
            );

            $filter = new S3WormCommitmentQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3WormCommitment::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3wormcommitment_event_retention_days_filter()
    {
        try {
            $request = new Request(
                [
                'retention_days'  =>  '1'
                ]
            );

            $filter = new S3WormCommitmentQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3WormCommitment::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3wormcommitment_event_quota_bytes_filter()
    {
        try {
            $request = new Request(
                [
                'quota_bytes'  =>  '1'
                ]
            );

            $filter = new S3WormCommitmentQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3WormCommitment::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3wormcommitment_event_committed_at_filter_start()
    {
        try {
            $request = new Request(
                [
                'committed_atStart'  =>  now()
                ]
            );

            $filter = new S3WormCommitmentQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3WormCommitment::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3wormcommitment_event_locks_until_filter_start()
    {
        try {
            $request = new Request(
                [
                'locks_untilStart'  =>  now()
                ]
            );

            $filter = new S3WormCommitmentQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3WormCommitment::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3wormcommitment_event_cancelled_at_filter_start()
    {
        try {
            $request = new Request(
                [
                'cancelled_atStart'  =>  now()
                ]
            );

            $filter = new S3WormCommitmentQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3WormCommitment::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3wormcommitment_event_expired_at_filter_start()
    {
        try {
            $request = new Request(
                [
                'expired_atStart'  =>  now()
                ]
            );

            $filter = new S3WormCommitmentQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3WormCommitment::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3wormcommitment_event_purged_at_filter_start()
    {
        try {
            $request = new Request(
                [
                'purged_atStart'  =>  now()
                ]
            );

            $filter = new S3WormCommitmentQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3WormCommitment::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3wormcommitment_event_created_at_filter_start()
    {
        try {
            $request = new Request(
                [
                'created_atStart'  =>  now()
                ]
            );

            $filter = new S3WormCommitmentQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3WormCommitment::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3wormcommitment_event_updated_at_filter_start()
    {
        try {
            $request = new Request(
                [
                'updated_atStart'  =>  now()
                ]
            );

            $filter = new S3WormCommitmentQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3WormCommitment::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3wormcommitment_event_committed_at_filter_end()
    {
        try {
            $request = new Request(
                [
                'committed_atEnd'  =>  now()
                ]
            );

            $filter = new S3WormCommitmentQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3WormCommitment::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3wormcommitment_event_locks_until_filter_end()
    {
        try {
            $request = new Request(
                [
                'locks_untilEnd'  =>  now()
                ]
            );

            $filter = new S3WormCommitmentQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3WormCommitment::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3wormcommitment_event_cancelled_at_filter_end()
    {
        try {
            $request = new Request(
                [
                'cancelled_atEnd'  =>  now()
                ]
            );

            $filter = new S3WormCommitmentQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3WormCommitment::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3wormcommitment_event_expired_at_filter_end()
    {
        try {
            $request = new Request(
                [
                'expired_atEnd'  =>  now()
                ]
            );

            $filter = new S3WormCommitmentQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3WormCommitment::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3wormcommitment_event_purged_at_filter_end()
    {
        try {
            $request = new Request(
                [
                'purged_atEnd'  =>  now()
                ]
            );

            $filter = new S3WormCommitmentQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3WormCommitment::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3wormcommitment_event_created_at_filter_end()
    {
        try {
            $request = new Request(
                [
                'created_atEnd'  =>  now()
                ]
            );

            $filter = new S3WormCommitmentQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3WormCommitment::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3wormcommitment_event_updated_at_filter_end()
    {
        try {
            $request = new Request(
                [
                'updated_atEnd'  =>  now()
                ]
            );

            $filter = new S3WormCommitmentQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3WormCommitment::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3wormcommitment_event_committed_at_filter_start_and_end()
    {
        try {
            $request = new Request(
                [
                'committed_atStart'  =>  now(),
                'committed_atEnd'  =>  now()
                ]
            );

            $filter = new S3WormCommitmentQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3WormCommitment::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3wormcommitment_event_locks_until_filter_start_and_end()
    {
        try {
            $request = new Request(
                [
                'locks_untilStart'  =>  now(),
                'locks_untilEnd'  =>  now()
                ]
            );

            $filter = new S3WormCommitmentQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3WormCommitment::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3wormcommitment_event_cancelled_at_filter_start_and_end()
    {
        try {
            $request = new Request(
                [
                'cancelled_atStart'  =>  now(),
                'cancelled_atEnd'  =>  now()
                ]
            );

            $filter = new S3WormCommitmentQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3WormCommitment::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3wormcommitment_event_expired_at_filter_start_and_end()
    {
        try {
            $request = new Request(
                [
                'expired_atStart'  =>  now(),
                'expired_atEnd'  =>  now()
                ]
            );

            $filter = new S3WormCommitmentQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3WormCommitment::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3wormcommitment_event_purged_at_filter_start_and_end()
    {
        try {
            $request = new Request(
                [
                'purged_atStart'  =>  now(),
                'purged_atEnd'  =>  now()
                ]
            );

            $filter = new S3WormCommitmentQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3WormCommitment::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3wormcommitment_event_created_at_filter_start_and_end()
    {
        try {
            $request = new Request(
                [
                'created_atStart'  =>  now(),
                'created_atEnd'  =>  now()
                ]
            );

            $filter = new S3WormCommitmentQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3WormCommitment::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3wormcommitment_event_updated_at_filter_start_and_end()
    {
        try {
            $request = new Request(
                [
                'updated_atStart'  =>  now(),
                'updated_atEnd'  =>  now()
                ]
            );

            $filter = new S3WormCommitmentQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3WormCommitment::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}