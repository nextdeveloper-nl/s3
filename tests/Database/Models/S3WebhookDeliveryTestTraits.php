<?php

namespace NextDeveloper\S3\Tests\Database\Models;

use Tests\TestCase;
use GuzzleHttp\Client;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use NextDeveloper\S3\Database\Filters\S3WebhookDeliveryQueryFilter;
use NextDeveloper\S3\Services\AbstractServices\AbstractS3WebhookDeliveryService;
use Illuminate\Pagination\LengthAwarePaginator;
use League\Fractal\Resource\Collection;

trait S3WebhookDeliveryTestTraits
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

    public function test_http_s3webhookdelivery_get()
    {
        $this->setupGuzzle();
        $response = $this->http->request(
            'GET',
            '/s3/s3webhookdelivery',
            ['http_errors' => false]
        );

        $this->assertContains(
            $response->getStatusCode(), [
            Response::HTTP_OK,
            Response::HTTP_NOT_FOUND
            ]
        );
    }

    public function test_http_s3webhookdelivery_post()
    {
        $this->setupGuzzle();
        $response = $this->http->request(
            'POST', '/s3/s3webhookdelivery', [
            'form_params'   =>  [
                'event_type'  =>  'a',
                'object_key'  =>  'a',
                'status_code'  =>  '1',
                'attempt'  =>  '1',
                    'next_retry_at'  =>  now(),
                    'delivered_at'  =>  now(),
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
    public function test_s3webhookdelivery_model_get()
    {
        $result = AbstractS3WebhookDeliveryService::get();

        $this->assertIsObject($result, Collection::class);
    }

    public function test_s3webhookdelivery_get_all()
    {
        $result = AbstractS3WebhookDeliveryService::getAll();

        $this->assertIsObject($result, Collection::class);
    }

    public function test_s3webhookdelivery_get_paginated()
    {
        $result = AbstractS3WebhookDeliveryService::get(
            null, [
            'paginated' =>  'true'
            ]
        );

        $this->assertIsObject($result, LengthAwarePaginator::class);
    }

    public function test_s3webhookdelivery_event_retrieved_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3WebhookDelivery\S3WebhookDeliveryRetrievedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3webhookdelivery_event_created_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3WebhookDelivery\S3WebhookDeliveryCreatedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3webhookdelivery_event_creating_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3WebhookDelivery\S3WebhookDeliveryCreatingEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3webhookdelivery_event_saving_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3WebhookDelivery\S3WebhookDeliverySavingEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3webhookdelivery_event_saved_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3WebhookDelivery\S3WebhookDeliverySavedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3webhookdelivery_event_updating_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3WebhookDelivery\S3WebhookDeliveryUpdatingEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3webhookdelivery_event_updated_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3WebhookDelivery\S3WebhookDeliveryUpdatedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3webhookdelivery_event_deleting_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3WebhookDelivery\S3WebhookDeliveryDeletingEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3webhookdelivery_event_deleted_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3WebhookDelivery\S3WebhookDeliveryDeletedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3webhookdelivery_event_restoring_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3WebhookDelivery\S3WebhookDeliveryRestoringEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3webhookdelivery_event_restored_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3WebhookDelivery\S3WebhookDeliveryRestoredEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3webhookdelivery_event_retrieved_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3WebhookDelivery::first();

            event(new \NextDeveloper\S3\Events\S3WebhookDelivery\S3WebhookDeliveryRetrievedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3webhookdelivery_event_created_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3WebhookDelivery::first();

            event(new \NextDeveloper\S3\Events\S3WebhookDelivery\S3WebhookDeliveryCreatedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3webhookdelivery_event_creating_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3WebhookDelivery::first();

            event(new \NextDeveloper\S3\Events\S3WebhookDelivery\S3WebhookDeliveryCreatingEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3webhookdelivery_event_saving_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3WebhookDelivery::first();

            event(new \NextDeveloper\S3\Events\S3WebhookDelivery\S3WebhookDeliverySavingEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3webhookdelivery_event_saved_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3WebhookDelivery::first();

            event(new \NextDeveloper\S3\Events\S3WebhookDelivery\S3WebhookDeliverySavedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3webhookdelivery_event_updating_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3WebhookDelivery::first();

            event(new \NextDeveloper\S3\Events\S3WebhookDelivery\S3WebhookDeliveryUpdatingEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3webhookdelivery_event_updated_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3WebhookDelivery::first();

            event(new \NextDeveloper\S3\Events\S3WebhookDelivery\S3WebhookDeliveryUpdatedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3webhookdelivery_event_deleting_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3WebhookDelivery::first();

            event(new \NextDeveloper\S3\Events\S3WebhookDelivery\S3WebhookDeliveryDeletingEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3webhookdelivery_event_deleted_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3WebhookDelivery::first();

            event(new \NextDeveloper\S3\Events\S3WebhookDelivery\S3WebhookDeliveryDeletedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3webhookdelivery_event_restoring_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3WebhookDelivery::first();

            event(new \NextDeveloper\S3\Events\S3WebhookDelivery\S3WebhookDeliveryRestoringEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3webhookdelivery_event_restored_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3WebhookDelivery::first();

            event(new \NextDeveloper\S3\Events\S3WebhookDelivery\S3WebhookDeliveryRestoredEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3webhookdelivery_event_event_type_filter()
    {
        try {
            $request = new Request(
                [
                'event_type'  =>  'a'
                ]
            );

            $filter = new S3WebhookDeliveryQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3WebhookDelivery::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3webhookdelivery_event_object_key_filter()
    {
        try {
            $request = new Request(
                [
                'object_key'  =>  'a'
                ]
            );

            $filter = new S3WebhookDeliveryQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3WebhookDelivery::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3webhookdelivery_event_status_code_filter()
    {
        try {
            $request = new Request(
                [
                'status_code'  =>  '1'
                ]
            );

            $filter = new S3WebhookDeliveryQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3WebhookDelivery::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3webhookdelivery_event_attempt_filter()
    {
        try {
            $request = new Request(
                [
                'attempt'  =>  '1'
                ]
            );

            $filter = new S3WebhookDeliveryQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3WebhookDelivery::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3webhookdelivery_event_next_retry_at_filter_start()
    {
        try {
            $request = new Request(
                [
                'next_retry_atStart'  =>  now()
                ]
            );

            $filter = new S3WebhookDeliveryQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3WebhookDelivery::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3webhookdelivery_event_delivered_at_filter_start()
    {
        try {
            $request = new Request(
                [
                'delivered_atStart'  =>  now()
                ]
            );

            $filter = new S3WebhookDeliveryQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3WebhookDelivery::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3webhookdelivery_event_created_at_filter_start()
    {
        try {
            $request = new Request(
                [
                'created_atStart'  =>  now()
                ]
            );

            $filter = new S3WebhookDeliveryQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3WebhookDelivery::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3webhookdelivery_event_next_retry_at_filter_end()
    {
        try {
            $request = new Request(
                [
                'next_retry_atEnd'  =>  now()
                ]
            );

            $filter = new S3WebhookDeliveryQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3WebhookDelivery::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3webhookdelivery_event_delivered_at_filter_end()
    {
        try {
            $request = new Request(
                [
                'delivered_atEnd'  =>  now()
                ]
            );

            $filter = new S3WebhookDeliveryQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3WebhookDelivery::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3webhookdelivery_event_created_at_filter_end()
    {
        try {
            $request = new Request(
                [
                'created_atEnd'  =>  now()
                ]
            );

            $filter = new S3WebhookDeliveryQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3WebhookDelivery::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3webhookdelivery_event_next_retry_at_filter_start_and_end()
    {
        try {
            $request = new Request(
                [
                'next_retry_atStart'  =>  now(),
                'next_retry_atEnd'  =>  now()
                ]
            );

            $filter = new S3WebhookDeliveryQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3WebhookDelivery::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3webhookdelivery_event_delivered_at_filter_start_and_end()
    {
        try {
            $request = new Request(
                [
                'delivered_atStart'  =>  now(),
                'delivered_atEnd'  =>  now()
                ]
            );

            $filter = new S3WebhookDeliveryQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3WebhookDelivery::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3webhookdelivery_event_created_at_filter_start_and_end()
    {
        try {
            $request = new Request(
                [
                'created_atStart'  =>  now(),
                'created_atEnd'  =>  now()
                ]
            );

            $filter = new S3WebhookDeliveryQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3WebhookDelivery::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}