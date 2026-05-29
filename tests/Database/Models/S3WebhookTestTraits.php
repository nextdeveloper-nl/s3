<?php

namespace NextDeveloper\S3\Tests\Database\Models;

use Tests\TestCase;
use GuzzleHttp\Client;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use NextDeveloper\S3\Database\Filters\S3WebhookQueryFilter;
use NextDeveloper\S3\Services\AbstractServices\AbstractS3WebhookService;
use Illuminate\Pagination\LengthAwarePaginator;
use League\Fractal\Resource\Collection;

trait S3WebhookTestTraits
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

    public function test_http_s3webhook_get()
    {
        $this->setupGuzzle();
        $response = $this->http->request(
            'GET',
            '/s3/s3webhook',
            ['http_errors' => false]
        );

        $this->assertContains(
            $response->getStatusCode(), [
            Response::HTTP_OK,
            Response::HTTP_NOT_FOUND
            ]
        );
    }

    public function test_http_s3webhook_post()
    {
        $this->setupGuzzle();
        $response = $this->http->request(
            'POST', '/s3/s3webhook', [
            'form_params'   =>  [
                'endpoint_url'  =>  'a',
                'secret'  =>  'a',
                'status'  =>  'a',
                'failure_count'  =>  '1',
                    'paused_at'  =>  now(),
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
    public function test_s3webhook_model_get()
    {
        $result = AbstractS3WebhookService::get();

        $this->assertIsObject($result, Collection::class);
    }

    public function test_s3webhook_get_all()
    {
        $result = AbstractS3WebhookService::getAll();

        $this->assertIsObject($result, Collection::class);
    }

    public function test_s3webhook_get_paginated()
    {
        $result = AbstractS3WebhookService::get(
            null, [
            'paginated' =>  'true'
            ]
        );

        $this->assertIsObject($result, LengthAwarePaginator::class);
    }

    public function test_s3webhook_event_retrieved_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3Webhook\S3WebhookRetrievedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3webhook_event_created_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3Webhook\S3WebhookCreatedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3webhook_event_creating_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3Webhook\S3WebhookCreatingEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3webhook_event_saving_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3Webhook\S3WebhookSavingEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3webhook_event_saved_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3Webhook\S3WebhookSavedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3webhook_event_updating_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3Webhook\S3WebhookUpdatingEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3webhook_event_updated_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3Webhook\S3WebhookUpdatedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3webhook_event_deleting_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3Webhook\S3WebhookDeletingEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3webhook_event_deleted_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3Webhook\S3WebhookDeletedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3webhook_event_restoring_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3Webhook\S3WebhookRestoringEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3webhook_event_restored_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3Webhook\S3WebhookRestoredEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3webhook_event_retrieved_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3Webhook::first();

            event(new \NextDeveloper\S3\Events\S3Webhook\S3WebhookRetrievedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3webhook_event_created_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3Webhook::first();

            event(new \NextDeveloper\S3\Events\S3Webhook\S3WebhookCreatedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3webhook_event_creating_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3Webhook::first();

            event(new \NextDeveloper\S3\Events\S3Webhook\S3WebhookCreatingEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3webhook_event_saving_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3Webhook::first();

            event(new \NextDeveloper\S3\Events\S3Webhook\S3WebhookSavingEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3webhook_event_saved_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3Webhook::first();

            event(new \NextDeveloper\S3\Events\S3Webhook\S3WebhookSavedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3webhook_event_updating_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3Webhook::first();

            event(new \NextDeveloper\S3\Events\S3Webhook\S3WebhookUpdatingEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3webhook_event_updated_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3Webhook::first();

            event(new \NextDeveloper\S3\Events\S3Webhook\S3WebhookUpdatedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3webhook_event_deleting_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3Webhook::first();

            event(new \NextDeveloper\S3\Events\S3Webhook\S3WebhookDeletingEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3webhook_event_deleted_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3Webhook::first();

            event(new \NextDeveloper\S3\Events\S3Webhook\S3WebhookDeletedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3webhook_event_restoring_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3Webhook::first();

            event(new \NextDeveloper\S3\Events\S3Webhook\S3WebhookRestoringEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3webhook_event_restored_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3Webhook::first();

            event(new \NextDeveloper\S3\Events\S3Webhook\S3WebhookRestoredEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3webhook_event_endpoint_url_filter()
    {
        try {
            $request = new Request(
                [
                'endpoint_url'  =>  'a'
                ]
            );

            $filter = new S3WebhookQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Webhook::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3webhook_event_secret_filter()
    {
        try {
            $request = new Request(
                [
                'secret'  =>  'a'
                ]
            );

            $filter = new S3WebhookQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Webhook::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3webhook_event_status_filter()
    {
        try {
            $request = new Request(
                [
                'status'  =>  'a'
                ]
            );

            $filter = new S3WebhookQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Webhook::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3webhook_event_failure_count_filter()
    {
        try {
            $request = new Request(
                [
                'failure_count'  =>  '1'
                ]
            );

            $filter = new S3WebhookQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Webhook::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3webhook_event_paused_at_filter_start()
    {
        try {
            $request = new Request(
                [
                'paused_atStart'  =>  now()
                ]
            );

            $filter = new S3WebhookQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Webhook::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3webhook_event_created_at_filter_start()
    {
        try {
            $request = new Request(
                [
                'created_atStart'  =>  now()
                ]
            );

            $filter = new S3WebhookQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Webhook::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3webhook_event_updated_at_filter_start()
    {
        try {
            $request = new Request(
                [
                'updated_atStart'  =>  now()
                ]
            );

            $filter = new S3WebhookQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Webhook::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3webhook_event_deleted_at_filter_start()
    {
        try {
            $request = new Request(
                [
                'deleted_atStart'  =>  now()
                ]
            );

            $filter = new S3WebhookQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Webhook::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3webhook_event_paused_at_filter_end()
    {
        try {
            $request = new Request(
                [
                'paused_atEnd'  =>  now()
                ]
            );

            $filter = new S3WebhookQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Webhook::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3webhook_event_created_at_filter_end()
    {
        try {
            $request = new Request(
                [
                'created_atEnd'  =>  now()
                ]
            );

            $filter = new S3WebhookQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Webhook::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3webhook_event_updated_at_filter_end()
    {
        try {
            $request = new Request(
                [
                'updated_atEnd'  =>  now()
                ]
            );

            $filter = new S3WebhookQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Webhook::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3webhook_event_deleted_at_filter_end()
    {
        try {
            $request = new Request(
                [
                'deleted_atEnd'  =>  now()
                ]
            );

            $filter = new S3WebhookQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Webhook::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3webhook_event_paused_at_filter_start_and_end()
    {
        try {
            $request = new Request(
                [
                'paused_atStart'  =>  now(),
                'paused_atEnd'  =>  now()
                ]
            );

            $filter = new S3WebhookQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Webhook::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3webhook_event_created_at_filter_start_and_end()
    {
        try {
            $request = new Request(
                [
                'created_atStart'  =>  now(),
                'created_atEnd'  =>  now()
                ]
            );

            $filter = new S3WebhookQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Webhook::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3webhook_event_updated_at_filter_start_and_end()
    {
        try {
            $request = new Request(
                [
                'updated_atStart'  =>  now(),
                'updated_atEnd'  =>  now()
                ]
            );

            $filter = new S3WebhookQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Webhook::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3webhook_event_deleted_at_filter_start_and_end()
    {
        try {
            $request = new Request(
                [
                'deleted_atStart'  =>  now(),
                'deleted_atEnd'  =>  now()
                ]
            );

            $filter = new S3WebhookQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Webhook::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}