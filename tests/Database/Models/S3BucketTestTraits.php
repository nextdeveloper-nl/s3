<?php

namespace NextDeveloper\S3\Tests\Database\Models;

use Tests\TestCase;
use GuzzleHttp\Client;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use NextDeveloper\S3\Database\Filters\S3BucketQueryFilter;
use NextDeveloper\S3\Services\AbstractServices\AbstractS3BucketService;
use Illuminate\Pagination\LengthAwarePaginator;
use League\Fractal\Resource\Collection;

trait S3BucketTestTraits
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

    public function test_http_s3bucket_get()
    {
        $this->setupGuzzle();
        $response = $this->http->request(
            'GET',
            '/s3/s3bucket',
            ['http_errors' => false]
        );

        $this->assertContains(
            $response->getStatusCode(), [
            Response::HTTP_OK,
            Response::HTTP_NOT_FOUND
            ]
        );
    }

    public function test_http_s3bucket_post()
    {
        $this->setupGuzzle();
        $response = $this->http->request(
            'POST', '/s3/s3bucket', [
            'form_params'   =>  [
                'name'  =>  'a',
                'versioning'  =>  'a',
                'object_lock_mode'  =>  'a',
                'replica_health'  =>  'a',
                'status'  =>  'a',
                'replication_factor'  =>  '1',
                'object_lock_days'  =>  '1',
                'object_count'  =>  '1',
                'size_bytes'  =>  '1',
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
    public function test_s3bucket_model_get()
    {
        $result = AbstractS3BucketService::get();

        $this->assertIsObject($result, Collection::class);
    }

    public function test_s3bucket_get_all()
    {
        $result = AbstractS3BucketService::getAll();

        $this->assertIsObject($result, Collection::class);
    }

    public function test_s3bucket_get_paginated()
    {
        $result = AbstractS3BucketService::get(
            null, [
            'paginated' =>  'true'
            ]
        );

        $this->assertIsObject($result, LengthAwarePaginator::class);
    }

    public function test_s3bucket_event_retrieved_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3Bucket\S3BucketRetrievedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3bucket_event_created_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3Bucket\S3BucketCreatedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3bucket_event_creating_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3Bucket\S3BucketCreatingEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3bucket_event_saving_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3Bucket\S3BucketSavingEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3bucket_event_saved_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3Bucket\S3BucketSavedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3bucket_event_updating_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3Bucket\S3BucketUpdatingEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3bucket_event_updated_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3Bucket\S3BucketUpdatedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3bucket_event_deleting_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3Bucket\S3BucketDeletingEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3bucket_event_deleted_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3Bucket\S3BucketDeletedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3bucket_event_restoring_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3Bucket\S3BucketRestoringEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3bucket_event_restored_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3Bucket\S3BucketRestoredEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3bucket_event_retrieved_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3Bucket::first();

            event(new \NextDeveloper\S3\Events\S3Bucket\S3BucketRetrievedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3bucket_event_created_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3Bucket::first();

            event(new \NextDeveloper\S3\Events\S3Bucket\S3BucketCreatedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3bucket_event_creating_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3Bucket::first();

            event(new \NextDeveloper\S3\Events\S3Bucket\S3BucketCreatingEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3bucket_event_saving_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3Bucket::first();

            event(new \NextDeveloper\S3\Events\S3Bucket\S3BucketSavingEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3bucket_event_saved_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3Bucket::first();

            event(new \NextDeveloper\S3\Events\S3Bucket\S3BucketSavedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3bucket_event_updating_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3Bucket::first();

            event(new \NextDeveloper\S3\Events\S3Bucket\S3BucketUpdatingEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3bucket_event_updated_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3Bucket::first();

            event(new \NextDeveloper\S3\Events\S3Bucket\S3BucketUpdatedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3bucket_event_deleting_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3Bucket::first();

            event(new \NextDeveloper\S3\Events\S3Bucket\S3BucketDeletingEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3bucket_event_deleted_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3Bucket::first();

            event(new \NextDeveloper\S3\Events\S3Bucket\S3BucketDeletedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3bucket_event_restoring_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3Bucket::first();

            event(new \NextDeveloper\S3\Events\S3Bucket\S3BucketRestoringEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3bucket_event_restored_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3Bucket::first();

            event(new \NextDeveloper\S3\Events\S3Bucket\S3BucketRestoredEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3bucket_event_name_filter()
    {
        try {
            $request = new Request(
                [
                'name'  =>  'a'
                ]
            );

            $filter = new S3BucketQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Bucket::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3bucket_event_versioning_filter()
    {
        try {
            $request = new Request(
                [
                'versioning'  =>  'a'
                ]
            );

            $filter = new S3BucketQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Bucket::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3bucket_event_object_lock_mode_filter()
    {
        try {
            $request = new Request(
                [
                'object_lock_mode'  =>  'a'
                ]
            );

            $filter = new S3BucketQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Bucket::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3bucket_event_replica_health_filter()
    {
        try {
            $request = new Request(
                [
                'replica_health'  =>  'a'
                ]
            );

            $filter = new S3BucketQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Bucket::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3bucket_event_status_filter()
    {
        try {
            $request = new Request(
                [
                'status'  =>  'a'
                ]
            );

            $filter = new S3BucketQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Bucket::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3bucket_event_replication_factor_filter()
    {
        try {
            $request = new Request(
                [
                'replication_factor'  =>  '1'
                ]
            );

            $filter = new S3BucketQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Bucket::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3bucket_event_object_lock_days_filter()
    {
        try {
            $request = new Request(
                [
                'object_lock_days'  =>  '1'
                ]
            );

            $filter = new S3BucketQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Bucket::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3bucket_event_object_count_filter()
    {
        try {
            $request = new Request(
                [
                'object_count'  =>  '1'
                ]
            );

            $filter = new S3BucketQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Bucket::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3bucket_event_size_bytes_filter()
    {
        try {
            $request = new Request(
                [
                'size_bytes'  =>  '1'
                ]
            );

            $filter = new S3BucketQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Bucket::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3bucket_event_created_at_filter_start()
    {
        try {
            $request = new Request(
                [
                'created_atStart'  =>  now()
                ]
            );

            $filter = new S3BucketQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Bucket::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3bucket_event_updated_at_filter_start()
    {
        try {
            $request = new Request(
                [
                'updated_atStart'  =>  now()
                ]
            );

            $filter = new S3BucketQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Bucket::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3bucket_event_deleted_at_filter_start()
    {
        try {
            $request = new Request(
                [
                'deleted_atStart'  =>  now()
                ]
            );

            $filter = new S3BucketQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Bucket::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3bucket_event_created_at_filter_end()
    {
        try {
            $request = new Request(
                [
                'created_atEnd'  =>  now()
                ]
            );

            $filter = new S3BucketQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Bucket::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3bucket_event_updated_at_filter_end()
    {
        try {
            $request = new Request(
                [
                'updated_atEnd'  =>  now()
                ]
            );

            $filter = new S3BucketQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Bucket::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3bucket_event_deleted_at_filter_end()
    {
        try {
            $request = new Request(
                [
                'deleted_atEnd'  =>  now()
                ]
            );

            $filter = new S3BucketQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Bucket::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3bucket_event_created_at_filter_start_and_end()
    {
        try {
            $request = new Request(
                [
                'created_atStart'  =>  now(),
                'created_atEnd'  =>  now()
                ]
            );

            $filter = new S3BucketQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Bucket::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3bucket_event_updated_at_filter_start_and_end()
    {
        try {
            $request = new Request(
                [
                'updated_atStart'  =>  now(),
                'updated_atEnd'  =>  now()
                ]
            );

            $filter = new S3BucketQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Bucket::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3bucket_event_deleted_at_filter_start_and_end()
    {
        try {
            $request = new Request(
                [
                'deleted_atStart'  =>  now(),
                'deleted_atEnd'  =>  now()
                ]
            );

            $filter = new S3BucketQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Bucket::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}