<?php

namespace NextDeveloper\S3\Tests\Database\Models;

use Tests\TestCase;
use GuzzleHttp\Client;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use NextDeveloper\S3\Database\Filters\S3BandwidthMonthlyQueryFilter;
use NextDeveloper\S3\Services\AbstractServices\AbstractS3BandwidthMonthlyService;
use Illuminate\Pagination\LengthAwarePaginator;
use League\Fractal\Resource\Collection;

trait S3BandwidthMonthlyTestTraits
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

    public function test_http_s3bandwidthmonthly_get()
    {
        $this->setupGuzzle();
        $response = $this->http->request(
            'GET',
            '/s3/s3bandwidthmonthly',
            ['http_errors' => false]
        );

        $this->assertContains(
            $response->getStatusCode(), [
            Response::HTTP_OK,
            Response::HTTP_NOT_FOUND
            ]
        );
    }

    public function test_http_s3bandwidthmonthly_post()
    {
        $this->setupGuzzle();
        $response = $this->http->request(
            'POST', '/s3/s3bandwidthmonthly', [
            'form_params'   =>  [
                'egress_bytes'  =>  '1',
                'ingress_bytes'  =>  '1',
                    'month'  =>  now(),
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
    public function test_s3bandwidthmonthly_model_get()
    {
        $result = AbstractS3BandwidthMonthlyService::get();

        $this->assertIsObject($result, Collection::class);
    }

    public function test_s3bandwidthmonthly_get_all()
    {
        $result = AbstractS3BandwidthMonthlyService::getAll();

        $this->assertIsObject($result, Collection::class);
    }

    public function test_s3bandwidthmonthly_get_paginated()
    {
        $result = AbstractS3BandwidthMonthlyService::get(
            null, [
            'paginated' =>  'true'
            ]
        );

        $this->assertIsObject($result, LengthAwarePaginator::class);
    }

    public function test_s3bandwidthmonthly_event_retrieved_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3BandwidthMonthly\S3BandwidthMonthlyRetrievedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3bandwidthmonthly_event_created_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3BandwidthMonthly\S3BandwidthMonthlyCreatedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3bandwidthmonthly_event_creating_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3BandwidthMonthly\S3BandwidthMonthlyCreatingEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3bandwidthmonthly_event_saving_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3BandwidthMonthly\S3BandwidthMonthlySavingEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3bandwidthmonthly_event_saved_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3BandwidthMonthly\S3BandwidthMonthlySavedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3bandwidthmonthly_event_updating_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3BandwidthMonthly\S3BandwidthMonthlyUpdatingEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3bandwidthmonthly_event_updated_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3BandwidthMonthly\S3BandwidthMonthlyUpdatedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3bandwidthmonthly_event_deleting_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3BandwidthMonthly\S3BandwidthMonthlyDeletingEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3bandwidthmonthly_event_deleted_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3BandwidthMonthly\S3BandwidthMonthlyDeletedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3bandwidthmonthly_event_restoring_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3BandwidthMonthly\S3BandwidthMonthlyRestoringEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3bandwidthmonthly_event_restored_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3BandwidthMonthly\S3BandwidthMonthlyRestoredEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3bandwidthmonthly_event_retrieved_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3BandwidthMonthly::first();

            event(new \NextDeveloper\S3\Events\S3BandwidthMonthly\S3BandwidthMonthlyRetrievedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3bandwidthmonthly_event_created_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3BandwidthMonthly::first();

            event(new \NextDeveloper\S3\Events\S3BandwidthMonthly\S3BandwidthMonthlyCreatedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3bandwidthmonthly_event_creating_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3BandwidthMonthly::first();

            event(new \NextDeveloper\S3\Events\S3BandwidthMonthly\S3BandwidthMonthlyCreatingEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3bandwidthmonthly_event_saving_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3BandwidthMonthly::first();

            event(new \NextDeveloper\S3\Events\S3BandwidthMonthly\S3BandwidthMonthlySavingEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3bandwidthmonthly_event_saved_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3BandwidthMonthly::first();

            event(new \NextDeveloper\S3\Events\S3BandwidthMonthly\S3BandwidthMonthlySavedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3bandwidthmonthly_event_updating_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3BandwidthMonthly::first();

            event(new \NextDeveloper\S3\Events\S3BandwidthMonthly\S3BandwidthMonthlyUpdatingEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3bandwidthmonthly_event_updated_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3BandwidthMonthly::first();

            event(new \NextDeveloper\S3\Events\S3BandwidthMonthly\S3BandwidthMonthlyUpdatedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3bandwidthmonthly_event_deleting_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3BandwidthMonthly::first();

            event(new \NextDeveloper\S3\Events\S3BandwidthMonthly\S3BandwidthMonthlyDeletingEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3bandwidthmonthly_event_deleted_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3BandwidthMonthly::first();

            event(new \NextDeveloper\S3\Events\S3BandwidthMonthly\S3BandwidthMonthlyDeletedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3bandwidthmonthly_event_restoring_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3BandwidthMonthly::first();

            event(new \NextDeveloper\S3\Events\S3BandwidthMonthly\S3BandwidthMonthlyRestoringEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3bandwidthmonthly_event_restored_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3BandwidthMonthly::first();

            event(new \NextDeveloper\S3\Events\S3BandwidthMonthly\S3BandwidthMonthlyRestoredEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3bandwidthmonthly_event_egress_bytes_filter()
    {
        try {
            $request = new Request(
                [
                'egress_bytes'  =>  '1'
                ]
            );

            $filter = new S3BandwidthMonthlyQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3BandwidthMonthly::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3bandwidthmonthly_event_ingress_bytes_filter()
    {
        try {
            $request = new Request(
                [
                'ingress_bytes'  =>  '1'
                ]
            );

            $filter = new S3BandwidthMonthlyQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3BandwidthMonthly::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3bandwidthmonthly_event_month_filter_start()
    {
        try {
            $request = new Request(
                [
                'monthStart'  =>  now()
                ]
            );

            $filter = new S3BandwidthMonthlyQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3BandwidthMonthly::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3bandwidthmonthly_event_updated_at_filter_start()
    {
        try {
            $request = new Request(
                [
                'updated_atStart'  =>  now()
                ]
            );

            $filter = new S3BandwidthMonthlyQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3BandwidthMonthly::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3bandwidthmonthly_event_month_filter_end()
    {
        try {
            $request = new Request(
                [
                'monthEnd'  =>  now()
                ]
            );

            $filter = new S3BandwidthMonthlyQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3BandwidthMonthly::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3bandwidthmonthly_event_updated_at_filter_end()
    {
        try {
            $request = new Request(
                [
                'updated_atEnd'  =>  now()
                ]
            );

            $filter = new S3BandwidthMonthlyQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3BandwidthMonthly::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3bandwidthmonthly_event_month_filter_start_and_end()
    {
        try {
            $request = new Request(
                [
                'monthStart'  =>  now(),
                'monthEnd'  =>  now()
                ]
            );

            $filter = new S3BandwidthMonthlyQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3BandwidthMonthly::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3bandwidthmonthly_event_updated_at_filter_start_and_end()
    {
        try {
            $request = new Request(
                [
                'updated_atStart'  =>  now(),
                'updated_atEnd'  =>  now()
                ]
            );

            $filter = new S3BandwidthMonthlyQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3BandwidthMonthly::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}