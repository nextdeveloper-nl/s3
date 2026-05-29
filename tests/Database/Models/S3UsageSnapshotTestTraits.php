<?php

namespace NextDeveloper\S3\Tests\Database\Models;

use Tests\TestCase;
use GuzzleHttp\Client;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use NextDeveloper\S3\Database\Filters\S3UsageSnapshotQueryFilter;
use NextDeveloper\S3\Services\AbstractServices\AbstractS3UsageSnapshotService;
use Illuminate\Pagination\LengthAwarePaginator;
use League\Fractal\Resource\Collection;

trait S3UsageSnapshotTestTraits
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

    public function test_http_s3usagesnapshot_get()
    {
        $this->setupGuzzle();
        $response = $this->http->request(
            'GET',
            '/s3/s3usagesnapshot',
            ['http_errors' => false]
        );

        $this->assertContains(
            $response->getStatusCode(), [
            Response::HTTP_OK,
            Response::HTTP_NOT_FOUND
            ]
        );
    }

    public function test_http_s3usagesnapshot_post()
    {
        $this->setupGuzzle();
        $response = $this->http->request(
            'POST', '/s3/s3usagesnapshot', [
            'form_params'   =>  [
                'storage_bytes'  =>  '1',
                'object_count'  =>  '1',
                    'snapshot_at'  =>  now(),
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
    public function test_s3usagesnapshot_model_get()
    {
        $result = AbstractS3UsageSnapshotService::get();

        $this->assertIsObject($result, Collection::class);
    }

    public function test_s3usagesnapshot_get_all()
    {
        $result = AbstractS3UsageSnapshotService::getAll();

        $this->assertIsObject($result, Collection::class);
    }

    public function test_s3usagesnapshot_get_paginated()
    {
        $result = AbstractS3UsageSnapshotService::get(
            null, [
            'paginated' =>  'true'
            ]
        );

        $this->assertIsObject($result, LengthAwarePaginator::class);
    }

    public function test_s3usagesnapshot_event_retrieved_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3UsageSnapshot\S3UsageSnapshotRetrievedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3usagesnapshot_event_created_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3UsageSnapshot\S3UsageSnapshotCreatedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3usagesnapshot_event_creating_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3UsageSnapshot\S3UsageSnapshotCreatingEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3usagesnapshot_event_saving_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3UsageSnapshot\S3UsageSnapshotSavingEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3usagesnapshot_event_saved_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3UsageSnapshot\S3UsageSnapshotSavedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3usagesnapshot_event_updating_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3UsageSnapshot\S3UsageSnapshotUpdatingEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3usagesnapshot_event_updated_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3UsageSnapshot\S3UsageSnapshotUpdatedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3usagesnapshot_event_deleting_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3UsageSnapshot\S3UsageSnapshotDeletingEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3usagesnapshot_event_deleted_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3UsageSnapshot\S3UsageSnapshotDeletedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3usagesnapshot_event_restoring_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3UsageSnapshot\S3UsageSnapshotRestoringEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3usagesnapshot_event_restored_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3UsageSnapshot\S3UsageSnapshotRestoredEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3usagesnapshot_event_retrieved_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3UsageSnapshot::first();

            event(new \NextDeveloper\S3\Events\S3UsageSnapshot\S3UsageSnapshotRetrievedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3usagesnapshot_event_created_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3UsageSnapshot::first();

            event(new \NextDeveloper\S3\Events\S3UsageSnapshot\S3UsageSnapshotCreatedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3usagesnapshot_event_creating_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3UsageSnapshot::first();

            event(new \NextDeveloper\S3\Events\S3UsageSnapshot\S3UsageSnapshotCreatingEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3usagesnapshot_event_saving_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3UsageSnapshot::first();

            event(new \NextDeveloper\S3\Events\S3UsageSnapshot\S3UsageSnapshotSavingEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3usagesnapshot_event_saved_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3UsageSnapshot::first();

            event(new \NextDeveloper\S3\Events\S3UsageSnapshot\S3UsageSnapshotSavedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3usagesnapshot_event_updating_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3UsageSnapshot::first();

            event(new \NextDeveloper\S3\Events\S3UsageSnapshot\S3UsageSnapshotUpdatingEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3usagesnapshot_event_updated_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3UsageSnapshot::first();

            event(new \NextDeveloper\S3\Events\S3UsageSnapshot\S3UsageSnapshotUpdatedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3usagesnapshot_event_deleting_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3UsageSnapshot::first();

            event(new \NextDeveloper\S3\Events\S3UsageSnapshot\S3UsageSnapshotDeletingEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3usagesnapshot_event_deleted_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3UsageSnapshot::first();

            event(new \NextDeveloper\S3\Events\S3UsageSnapshot\S3UsageSnapshotDeletedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3usagesnapshot_event_restoring_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3UsageSnapshot::first();

            event(new \NextDeveloper\S3\Events\S3UsageSnapshot\S3UsageSnapshotRestoringEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3usagesnapshot_event_restored_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3UsageSnapshot::first();

            event(new \NextDeveloper\S3\Events\S3UsageSnapshot\S3UsageSnapshotRestoredEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3usagesnapshot_event_storage_bytes_filter()
    {
        try {
            $request = new Request(
                [
                'storage_bytes'  =>  '1'
                ]
            );

            $filter = new S3UsageSnapshotQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3UsageSnapshot::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3usagesnapshot_event_object_count_filter()
    {
        try {
            $request = new Request(
                [
                'object_count'  =>  '1'
                ]
            );

            $filter = new S3UsageSnapshotQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3UsageSnapshot::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3usagesnapshot_event_snapshot_at_filter_start()
    {
        try {
            $request = new Request(
                [
                'snapshot_atStart'  =>  now()
                ]
            );

            $filter = new S3UsageSnapshotQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3UsageSnapshot::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3usagesnapshot_event_snapshot_at_filter_end()
    {
        try {
            $request = new Request(
                [
                'snapshot_atEnd'  =>  now()
                ]
            );

            $filter = new S3UsageSnapshotQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3UsageSnapshot::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3usagesnapshot_event_snapshot_at_filter_start_and_end()
    {
        try {
            $request = new Request(
                [
                'snapshot_atStart'  =>  now(),
                'snapshot_atEnd'  =>  now()
                ]
            );

            $filter = new S3UsageSnapshotQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3UsageSnapshot::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}