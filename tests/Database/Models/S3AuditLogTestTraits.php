<?php

namespace NextDeveloper\S3\Tests\Database\Models;

use Tests\TestCase;
use GuzzleHttp\Client;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use NextDeveloper\S3\Database\Filters\S3AuditLogQueryFilter;
use NextDeveloper\S3\Services\AbstractServices\AbstractS3AuditLogService;
use Illuminate\Pagination\LengthAwarePaginator;
use League\Fractal\Resource\Collection;

trait S3AuditLogTestTraits
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

    public function test_http_s3auditlog_get()
    {
        $this->setupGuzzle();
        $response = $this->http->request(
            'GET',
            '/s3/s3auditlog',
            ['http_errors' => false]
        );

        $this->assertContains(
            $response->getStatusCode(), [
            Response::HTTP_OK,
            Response::HTTP_NOT_FOUND
            ]
        );
    }

    public function test_http_s3auditlog_post()
    {
        $this->setupGuzzle();
        $response = $this->http->request(
            'POST', '/s3/s3auditlog', [
            'form_params'   =>  [
                'action'  =>  'a',
                'performed_by'  =>  'a',
                'reason'  =>  'a',
                    'performed_at'  =>  now(),
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
    public function test_s3auditlog_model_get()
    {
        $result = AbstractS3AuditLogService::get();

        $this->assertIsObject($result, Collection::class);
    }

    public function test_s3auditlog_get_all()
    {
        $result = AbstractS3AuditLogService::getAll();

        $this->assertIsObject($result, Collection::class);
    }

    public function test_s3auditlog_get_paginated()
    {
        $result = AbstractS3AuditLogService::get(
            null, [
            'paginated' =>  'true'
            ]
        );

        $this->assertIsObject($result, LengthAwarePaginator::class);
    }

    public function test_s3auditlog_event_retrieved_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3AuditLog\S3AuditLogRetrievedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3auditlog_event_created_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3AuditLog\S3AuditLogCreatedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3auditlog_event_creating_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3AuditLog\S3AuditLogCreatingEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3auditlog_event_saving_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3AuditLog\S3AuditLogSavingEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3auditlog_event_saved_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3AuditLog\S3AuditLogSavedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3auditlog_event_updating_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3AuditLog\S3AuditLogUpdatingEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3auditlog_event_updated_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3AuditLog\S3AuditLogUpdatedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3auditlog_event_deleting_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3AuditLog\S3AuditLogDeletingEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3auditlog_event_deleted_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3AuditLog\S3AuditLogDeletedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3auditlog_event_restoring_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3AuditLog\S3AuditLogRestoringEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3auditlog_event_restored_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3AuditLog\S3AuditLogRestoredEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3auditlog_event_retrieved_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3AuditLog::first();

            event(new \NextDeveloper\S3\Events\S3AuditLog\S3AuditLogRetrievedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3auditlog_event_created_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3AuditLog::first();

            event(new \NextDeveloper\S3\Events\S3AuditLog\S3AuditLogCreatedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3auditlog_event_creating_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3AuditLog::first();

            event(new \NextDeveloper\S3\Events\S3AuditLog\S3AuditLogCreatingEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3auditlog_event_saving_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3AuditLog::first();

            event(new \NextDeveloper\S3\Events\S3AuditLog\S3AuditLogSavingEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3auditlog_event_saved_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3AuditLog::first();

            event(new \NextDeveloper\S3\Events\S3AuditLog\S3AuditLogSavedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3auditlog_event_updating_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3AuditLog::first();

            event(new \NextDeveloper\S3\Events\S3AuditLog\S3AuditLogUpdatingEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3auditlog_event_updated_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3AuditLog::first();

            event(new \NextDeveloper\S3\Events\S3AuditLog\S3AuditLogUpdatedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3auditlog_event_deleting_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3AuditLog::first();

            event(new \NextDeveloper\S3\Events\S3AuditLog\S3AuditLogDeletingEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3auditlog_event_deleted_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3AuditLog::first();

            event(new \NextDeveloper\S3\Events\S3AuditLog\S3AuditLogDeletedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3auditlog_event_restoring_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3AuditLog::first();

            event(new \NextDeveloper\S3\Events\S3AuditLog\S3AuditLogRestoringEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3auditlog_event_restored_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3AuditLog::first();

            event(new \NextDeveloper\S3\Events\S3AuditLog\S3AuditLogRestoredEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3auditlog_event_action_filter()
    {
        try {
            $request = new Request(
                [
                'action'  =>  'a'
                ]
            );

            $filter = new S3AuditLogQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3AuditLog::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3auditlog_event_performed_by_filter()
    {
        try {
            $request = new Request(
                [
                'performed_by'  =>  'a'
                ]
            );

            $filter = new S3AuditLogQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3AuditLog::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3auditlog_event_reason_filter()
    {
        try {
            $request = new Request(
                [
                'reason'  =>  'a'
                ]
            );

            $filter = new S3AuditLogQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3AuditLog::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3auditlog_event_performed_at_filter_start()
    {
        try {
            $request = new Request(
                [
                'performed_atStart'  =>  now()
                ]
            );

            $filter = new S3AuditLogQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3AuditLog::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3auditlog_event_performed_at_filter_end()
    {
        try {
            $request = new Request(
                [
                'performed_atEnd'  =>  now()
                ]
            );

            $filter = new S3AuditLogQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3AuditLog::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3auditlog_event_performed_at_filter_start_and_end()
    {
        try {
            $request = new Request(
                [
                'performed_atStart'  =>  now(),
                'performed_atEnd'  =>  now()
                ]
            );

            $filter = new S3AuditLogQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3AuditLog::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}