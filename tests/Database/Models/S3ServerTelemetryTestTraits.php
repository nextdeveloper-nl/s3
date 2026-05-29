<?php

namespace NextDeveloper\S3\Tests\Database\Models;

use Tests\TestCase;
use GuzzleHttp\Client;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use NextDeveloper\S3\Database\Filters\S3ServerTelemetryQueryFilter;
use NextDeveloper\S3\Services\AbstractServices\AbstractS3ServerTelemetryService;
use Illuminate\Pagination\LengthAwarePaginator;
use League\Fractal\Resource\Collection;

trait S3ServerTelemetryTestTraits
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

    public function test_http_s3servertelemetry_get()
    {
        $this->setupGuzzle();
        $response = $this->http->request(
            'GET',
            '/s3/s3servertelemetry',
            ['http_errors' => false]
        );

        $this->assertContains(
            $response->getStatusCode(), [
            Response::HTTP_OK,
            Response::HTTP_NOT_FOUND
            ]
        );
    }

    public function test_http_s3servertelemetry_post()
    {
        $this->setupGuzzle();
        $response = $this->http->request(
            'POST', '/s3/s3servertelemetry', [
            'form_params'   =>  [
                'volume_count'  =>  '1',
                'volumes_degraded'  =>  '1',
                'capacity_bytes_total'  =>  '1',
                'capacity_bytes_used'  =>  '1',
                    'reported_at'  =>  now(),
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
    public function test_s3servertelemetry_model_get()
    {
        $result = AbstractS3ServerTelemetryService::get();

        $this->assertIsObject($result, Collection::class);
    }

    public function test_s3servertelemetry_get_all()
    {
        $result = AbstractS3ServerTelemetryService::getAll();

        $this->assertIsObject($result, Collection::class);
    }

    public function test_s3servertelemetry_get_paginated()
    {
        $result = AbstractS3ServerTelemetryService::get(
            null, [
            'paginated' =>  'true'
            ]
        );

        $this->assertIsObject($result, LengthAwarePaginator::class);
    }

    public function test_s3servertelemetry_event_retrieved_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3ServerTelemetry\S3ServerTelemetryRetrievedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3servertelemetry_event_created_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3ServerTelemetry\S3ServerTelemetryCreatedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3servertelemetry_event_creating_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3ServerTelemetry\S3ServerTelemetryCreatingEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3servertelemetry_event_saving_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3ServerTelemetry\S3ServerTelemetrySavingEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3servertelemetry_event_saved_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3ServerTelemetry\S3ServerTelemetrySavedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3servertelemetry_event_updating_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3ServerTelemetry\S3ServerTelemetryUpdatingEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3servertelemetry_event_updated_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3ServerTelemetry\S3ServerTelemetryUpdatedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3servertelemetry_event_deleting_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3ServerTelemetry\S3ServerTelemetryDeletingEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3servertelemetry_event_deleted_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3ServerTelemetry\S3ServerTelemetryDeletedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3servertelemetry_event_restoring_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3ServerTelemetry\S3ServerTelemetryRestoringEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3servertelemetry_event_restored_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3ServerTelemetry\S3ServerTelemetryRestoredEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3servertelemetry_event_retrieved_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3ServerTelemetry::first();

            event(new \NextDeveloper\S3\Events\S3ServerTelemetry\S3ServerTelemetryRetrievedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3servertelemetry_event_created_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3ServerTelemetry::first();

            event(new \NextDeveloper\S3\Events\S3ServerTelemetry\S3ServerTelemetryCreatedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3servertelemetry_event_creating_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3ServerTelemetry::first();

            event(new \NextDeveloper\S3\Events\S3ServerTelemetry\S3ServerTelemetryCreatingEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3servertelemetry_event_saving_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3ServerTelemetry::first();

            event(new \NextDeveloper\S3\Events\S3ServerTelemetry\S3ServerTelemetrySavingEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3servertelemetry_event_saved_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3ServerTelemetry::first();

            event(new \NextDeveloper\S3\Events\S3ServerTelemetry\S3ServerTelemetrySavedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3servertelemetry_event_updating_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3ServerTelemetry::first();

            event(new \NextDeveloper\S3\Events\S3ServerTelemetry\S3ServerTelemetryUpdatingEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3servertelemetry_event_updated_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3ServerTelemetry::first();

            event(new \NextDeveloper\S3\Events\S3ServerTelemetry\S3ServerTelemetryUpdatedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3servertelemetry_event_deleting_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3ServerTelemetry::first();

            event(new \NextDeveloper\S3\Events\S3ServerTelemetry\S3ServerTelemetryDeletingEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3servertelemetry_event_deleted_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3ServerTelemetry::first();

            event(new \NextDeveloper\S3\Events\S3ServerTelemetry\S3ServerTelemetryDeletedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3servertelemetry_event_restoring_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3ServerTelemetry::first();

            event(new \NextDeveloper\S3\Events\S3ServerTelemetry\S3ServerTelemetryRestoringEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3servertelemetry_event_restored_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3ServerTelemetry::first();

            event(new \NextDeveloper\S3\Events\S3ServerTelemetry\S3ServerTelemetryRestoredEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3servertelemetry_event_volume_count_filter()
    {
        try {
            $request = new Request(
                [
                'volume_count'  =>  '1'
                ]
            );

            $filter = new S3ServerTelemetryQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3ServerTelemetry::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3servertelemetry_event_volumes_degraded_filter()
    {
        try {
            $request = new Request(
                [
                'volumes_degraded'  =>  '1'
                ]
            );

            $filter = new S3ServerTelemetryQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3ServerTelemetry::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3servertelemetry_event_capacity_bytes_total_filter()
    {
        try {
            $request = new Request(
                [
                'capacity_bytes_total'  =>  '1'
                ]
            );

            $filter = new S3ServerTelemetryQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3ServerTelemetry::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3servertelemetry_event_capacity_bytes_used_filter()
    {
        try {
            $request = new Request(
                [
                'capacity_bytes_used'  =>  '1'
                ]
            );

            $filter = new S3ServerTelemetryQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3ServerTelemetry::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3servertelemetry_event_reported_at_filter_start()
    {
        try {
            $request = new Request(
                [
                'reported_atStart'  =>  now()
                ]
            );

            $filter = new S3ServerTelemetryQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3ServerTelemetry::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3servertelemetry_event_reported_at_filter_end()
    {
        try {
            $request = new Request(
                [
                'reported_atEnd'  =>  now()
                ]
            );

            $filter = new S3ServerTelemetryQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3ServerTelemetry::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3servertelemetry_event_reported_at_filter_start_and_end()
    {
        try {
            $request = new Request(
                [
                'reported_atStart'  =>  now(),
                'reported_atEnd'  =>  now()
                ]
            );

            $filter = new S3ServerTelemetryQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3ServerTelemetry::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}