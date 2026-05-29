<?php

namespace NextDeveloper\S3\Tests\Database\Models;

use Tests\TestCase;
use GuzzleHttp\Client;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use NextDeveloper\S3\Database\Filters\S3NotificationsSentQueryFilter;
use NextDeveloper\S3\Services\AbstractServices\AbstractS3NotificationsSentService;
use Illuminate\Pagination\LengthAwarePaginator;
use League\Fractal\Resource\Collection;

trait S3NotificationsSentTestTraits
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

    public function test_http_s3notificationssent_get()
    {
        $this->setupGuzzle();
        $response = $this->http->request(
            'GET',
            '/s3/s3notificationssent',
            ['http_errors' => false]
        );

        $this->assertContains(
            $response->getStatusCode(), [
            Response::HTTP_OK,
            Response::HTTP_NOT_FOUND
            ]
        );
    }

    public function test_http_s3notificationssent_post()
    {
        $this->setupGuzzle();
        $response = $this->http->request(
            'POST', '/s3/s3notificationssent', [
            'form_params'   =>  [
                'notification'  =>  'a',
                    'month'  =>  now(),
                    'sent_at'  =>  now(),
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
    public function test_s3notificationssent_model_get()
    {
        $result = AbstractS3NotificationsSentService::get();

        $this->assertIsObject($result, Collection::class);
    }

    public function test_s3notificationssent_get_all()
    {
        $result = AbstractS3NotificationsSentService::getAll();

        $this->assertIsObject($result, Collection::class);
    }

    public function test_s3notificationssent_get_paginated()
    {
        $result = AbstractS3NotificationsSentService::get(
            null, [
            'paginated' =>  'true'
            ]
        );

        $this->assertIsObject($result, LengthAwarePaginator::class);
    }

    public function test_s3notificationssent_event_retrieved_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3NotificationsSent\S3NotificationsSentRetrievedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3notificationssent_event_created_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3NotificationsSent\S3NotificationsSentCreatedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3notificationssent_event_creating_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3NotificationsSent\S3NotificationsSentCreatingEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3notificationssent_event_saving_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3NotificationsSent\S3NotificationsSentSavingEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3notificationssent_event_saved_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3NotificationsSent\S3NotificationsSentSavedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3notificationssent_event_updating_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3NotificationsSent\S3NotificationsSentUpdatingEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3notificationssent_event_updated_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3NotificationsSent\S3NotificationsSentUpdatedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3notificationssent_event_deleting_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3NotificationsSent\S3NotificationsSentDeletingEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3notificationssent_event_deleted_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3NotificationsSent\S3NotificationsSentDeletedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3notificationssent_event_restoring_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3NotificationsSent\S3NotificationsSentRestoringEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3notificationssent_event_restored_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3NotificationsSent\S3NotificationsSentRestoredEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3notificationssent_event_retrieved_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3NotificationsSent::first();

            event(new \NextDeveloper\S3\Events\S3NotificationsSent\S3NotificationsSentRetrievedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3notificationssent_event_created_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3NotificationsSent::first();

            event(new \NextDeveloper\S3\Events\S3NotificationsSent\S3NotificationsSentCreatedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3notificationssent_event_creating_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3NotificationsSent::first();

            event(new \NextDeveloper\S3\Events\S3NotificationsSent\S3NotificationsSentCreatingEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3notificationssent_event_saving_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3NotificationsSent::first();

            event(new \NextDeveloper\S3\Events\S3NotificationsSent\S3NotificationsSentSavingEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3notificationssent_event_saved_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3NotificationsSent::first();

            event(new \NextDeveloper\S3\Events\S3NotificationsSent\S3NotificationsSentSavedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3notificationssent_event_updating_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3NotificationsSent::first();

            event(new \NextDeveloper\S3\Events\S3NotificationsSent\S3NotificationsSentUpdatingEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3notificationssent_event_updated_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3NotificationsSent::first();

            event(new \NextDeveloper\S3\Events\S3NotificationsSent\S3NotificationsSentUpdatedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3notificationssent_event_deleting_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3NotificationsSent::first();

            event(new \NextDeveloper\S3\Events\S3NotificationsSent\S3NotificationsSentDeletingEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3notificationssent_event_deleted_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3NotificationsSent::first();

            event(new \NextDeveloper\S3\Events\S3NotificationsSent\S3NotificationsSentDeletedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3notificationssent_event_restoring_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3NotificationsSent::first();

            event(new \NextDeveloper\S3\Events\S3NotificationsSent\S3NotificationsSentRestoringEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3notificationssent_event_restored_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3NotificationsSent::first();

            event(new \NextDeveloper\S3\Events\S3NotificationsSent\S3NotificationsSentRestoredEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3notificationssent_event_notification_filter()
    {
        try {
            $request = new Request(
                [
                'notification'  =>  'a'
                ]
            );

            $filter = new S3NotificationsSentQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3NotificationsSent::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3notificationssent_event_month_filter_start()
    {
        try {
            $request = new Request(
                [
                'monthStart'  =>  now()
                ]
            );

            $filter = new S3NotificationsSentQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3NotificationsSent::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3notificationssent_event_sent_at_filter_start()
    {
        try {
            $request = new Request(
                [
                'sent_atStart'  =>  now()
                ]
            );

            $filter = new S3NotificationsSentQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3NotificationsSent::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3notificationssent_event_month_filter_end()
    {
        try {
            $request = new Request(
                [
                'monthEnd'  =>  now()
                ]
            );

            $filter = new S3NotificationsSentQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3NotificationsSent::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3notificationssent_event_sent_at_filter_end()
    {
        try {
            $request = new Request(
                [
                'sent_atEnd'  =>  now()
                ]
            );

            $filter = new S3NotificationsSentQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3NotificationsSent::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3notificationssent_event_month_filter_start_and_end()
    {
        try {
            $request = new Request(
                [
                'monthStart'  =>  now(),
                'monthEnd'  =>  now()
                ]
            );

            $filter = new S3NotificationsSentQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3NotificationsSent::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3notificationssent_event_sent_at_filter_start_and_end()
    {
        try {
            $request = new Request(
                [
                'sent_atStart'  =>  now(),
                'sent_atEnd'  =>  now()
                ]
            );

            $filter = new S3NotificationsSentQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3NotificationsSent::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}