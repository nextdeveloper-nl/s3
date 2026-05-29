<?php

namespace NextDeveloper\S3\Tests\Database\Models;

use Tests\TestCase;
use GuzzleHttp\Client;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use NextDeveloper\S3\Database\Filters\S3AccountQueryFilter;
use NextDeveloper\S3\Services\AbstractServices\AbstractS3AccountService;
use Illuminate\Pagination\LengthAwarePaginator;
use League\Fractal\Resource\Collection;

trait S3AccountTestTraits
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

    public function test_http_s3account_get()
    {
        $this->setupGuzzle();
        $response = $this->http->request(
            'GET',
            '/s3/s3account',
            ['http_errors' => false]
        );

        $this->assertContains(
            $response->getStatusCode(), [
            Response::HTTP_OK,
            Response::HTTP_NOT_FOUND
            ]
        );
    }

    public function test_http_s3account_post()
    {
        $this->setupGuzzle();
        $response = $this->http->request(
            'POST', '/s3/s3account', [
            'form_params'   =>  [
                'slug'  =>  'a',
                'status'  =>  'a',
                'blocked_reason'  =>  'a',
                'quota_storage_bytes'  =>  '1',
                'quota_egress_bytes_mo'  =>  '1',
                'quota_max_buckets'  =>  '1',
                'quota_max_objects'  =>  '1',
                'storage_bytes_used'  =>  '1',
                'egress_bytes_mo_used'  =>  '1',
                'object_count'  =>  '1',
                    'usage_checked_at'  =>  now(),
                    'blocked_at'  =>  now(),
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
    public function test_s3account_model_get()
    {
        $result = AbstractS3AccountService::get();

        $this->assertIsObject($result, Collection::class);
    }

    public function test_s3account_get_all()
    {
        $result = AbstractS3AccountService::getAll();

        $this->assertIsObject($result, Collection::class);
    }

    public function test_s3account_get_paginated()
    {
        $result = AbstractS3AccountService::get(
            null, [
            'paginated' =>  'true'
            ]
        );

        $this->assertIsObject($result, LengthAwarePaginator::class);
    }

    public function test_s3account_event_retrieved_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3Account\S3AccountRetrievedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3account_event_created_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3Account\S3AccountCreatedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3account_event_creating_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3Account\S3AccountCreatingEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3account_event_saving_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3Account\S3AccountSavingEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3account_event_saved_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3Account\S3AccountSavedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3account_event_updating_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3Account\S3AccountUpdatingEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3account_event_updated_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3Account\S3AccountUpdatedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3account_event_deleting_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3Account\S3AccountDeletingEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3account_event_deleted_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3Account\S3AccountDeletedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3account_event_restoring_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3Account\S3AccountRestoringEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3account_event_restored_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3Account\S3AccountRestoredEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3account_event_retrieved_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3Account::first();

            event(new \NextDeveloper\S3\Events\S3Account\S3AccountRetrievedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3account_event_created_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3Account::first();

            event(new \NextDeveloper\S3\Events\S3Account\S3AccountCreatedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3account_event_creating_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3Account::first();

            event(new \NextDeveloper\S3\Events\S3Account\S3AccountCreatingEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3account_event_saving_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3Account::first();

            event(new \NextDeveloper\S3\Events\S3Account\S3AccountSavingEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3account_event_saved_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3Account::first();

            event(new \NextDeveloper\S3\Events\S3Account\S3AccountSavedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3account_event_updating_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3Account::first();

            event(new \NextDeveloper\S3\Events\S3Account\S3AccountUpdatingEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3account_event_updated_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3Account::first();

            event(new \NextDeveloper\S3\Events\S3Account\S3AccountUpdatedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3account_event_deleting_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3Account::first();

            event(new \NextDeveloper\S3\Events\S3Account\S3AccountDeletingEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3account_event_deleted_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3Account::first();

            event(new \NextDeveloper\S3\Events\S3Account\S3AccountDeletedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3account_event_restoring_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3Account::first();

            event(new \NextDeveloper\S3\Events\S3Account\S3AccountRestoringEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3account_event_restored_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3Account::first();

            event(new \NextDeveloper\S3\Events\S3Account\S3AccountRestoredEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3account_event_slug_filter()
    {
        try {
            $request = new Request(
                [
                'slug'  =>  'a'
                ]
            );

            $filter = new S3AccountQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Account::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3account_event_status_filter()
    {
        try {
            $request = new Request(
                [
                'status'  =>  'a'
                ]
            );

            $filter = new S3AccountQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Account::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3account_event_blocked_reason_filter()
    {
        try {
            $request = new Request(
                [
                'blocked_reason'  =>  'a'
                ]
            );

            $filter = new S3AccountQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Account::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3account_event_quota_storage_bytes_filter()
    {
        try {
            $request = new Request(
                [
                'quota_storage_bytes'  =>  '1'
                ]
            );

            $filter = new S3AccountQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Account::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3account_event_quota_egress_bytes_mo_filter()
    {
        try {
            $request = new Request(
                [
                'quota_egress_bytes_mo'  =>  '1'
                ]
            );

            $filter = new S3AccountQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Account::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3account_event_quota_max_buckets_filter()
    {
        try {
            $request = new Request(
                [
                'quota_max_buckets'  =>  '1'
                ]
            );

            $filter = new S3AccountQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Account::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3account_event_quota_max_objects_filter()
    {
        try {
            $request = new Request(
                [
                'quota_max_objects'  =>  '1'
                ]
            );

            $filter = new S3AccountQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Account::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3account_event_storage_bytes_used_filter()
    {
        try {
            $request = new Request(
                [
                'storage_bytes_used'  =>  '1'
                ]
            );

            $filter = new S3AccountQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Account::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3account_event_egress_bytes_mo_used_filter()
    {
        try {
            $request = new Request(
                [
                'egress_bytes_mo_used'  =>  '1'
                ]
            );

            $filter = new S3AccountQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Account::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3account_event_object_count_filter()
    {
        try {
            $request = new Request(
                [
                'object_count'  =>  '1'
                ]
            );

            $filter = new S3AccountQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Account::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3account_event_usage_checked_at_filter_start()
    {
        try {
            $request = new Request(
                [
                'usage_checked_atStart'  =>  now()
                ]
            );

            $filter = new S3AccountQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Account::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3account_event_blocked_at_filter_start()
    {
        try {
            $request = new Request(
                [
                'blocked_atStart'  =>  now()
                ]
            );

            $filter = new S3AccountQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Account::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3account_event_created_at_filter_start()
    {
        try {
            $request = new Request(
                [
                'created_atStart'  =>  now()
                ]
            );

            $filter = new S3AccountQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Account::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3account_event_updated_at_filter_start()
    {
        try {
            $request = new Request(
                [
                'updated_atStart'  =>  now()
                ]
            );

            $filter = new S3AccountQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Account::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3account_event_deleted_at_filter_start()
    {
        try {
            $request = new Request(
                [
                'deleted_atStart'  =>  now()
                ]
            );

            $filter = new S3AccountQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Account::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3account_event_usage_checked_at_filter_end()
    {
        try {
            $request = new Request(
                [
                'usage_checked_atEnd'  =>  now()
                ]
            );

            $filter = new S3AccountQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Account::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3account_event_blocked_at_filter_end()
    {
        try {
            $request = new Request(
                [
                'blocked_atEnd'  =>  now()
                ]
            );

            $filter = new S3AccountQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Account::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3account_event_created_at_filter_end()
    {
        try {
            $request = new Request(
                [
                'created_atEnd'  =>  now()
                ]
            );

            $filter = new S3AccountQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Account::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3account_event_updated_at_filter_end()
    {
        try {
            $request = new Request(
                [
                'updated_atEnd'  =>  now()
                ]
            );

            $filter = new S3AccountQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Account::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3account_event_deleted_at_filter_end()
    {
        try {
            $request = new Request(
                [
                'deleted_atEnd'  =>  now()
                ]
            );

            $filter = new S3AccountQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Account::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3account_event_usage_checked_at_filter_start_and_end()
    {
        try {
            $request = new Request(
                [
                'usage_checked_atStart'  =>  now(),
                'usage_checked_atEnd'  =>  now()
                ]
            );

            $filter = new S3AccountQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Account::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3account_event_blocked_at_filter_start_and_end()
    {
        try {
            $request = new Request(
                [
                'blocked_atStart'  =>  now(),
                'blocked_atEnd'  =>  now()
                ]
            );

            $filter = new S3AccountQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Account::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3account_event_created_at_filter_start_and_end()
    {
        try {
            $request = new Request(
                [
                'created_atStart'  =>  now(),
                'created_atEnd'  =>  now()
                ]
            );

            $filter = new S3AccountQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Account::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3account_event_updated_at_filter_start_and_end()
    {
        try {
            $request = new Request(
                [
                'updated_atStart'  =>  now(),
                'updated_atEnd'  =>  now()
                ]
            );

            $filter = new S3AccountQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Account::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3account_event_deleted_at_filter_start_and_end()
    {
        try {
            $request = new Request(
                [
                'deleted_atStart'  =>  now(),
                'deleted_atEnd'  =>  now()
                ]
            );

            $filter = new S3AccountQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Account::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}