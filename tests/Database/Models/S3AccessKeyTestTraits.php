<?php

namespace NextDeveloper\S3\Tests\Database\Models;

use Tests\TestCase;
use GuzzleHttp\Client;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use NextDeveloper\S3\Database\Filters\S3AccessKeyQueryFilter;
use NextDeveloper\S3\Services\AbstractServices\AbstractS3AccessKeyService;
use Illuminate\Pagination\LengthAwarePaginator;
use League\Fractal\Resource\Collection;

trait S3AccessKeyTestTraits
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

    public function test_http_s3accesskey_get()
    {
        $this->setupGuzzle();
        $response = $this->http->request(
            'GET',
            '/s3/s3accesskey',
            ['http_errors' => false]
        );

        $this->assertContains(
            $response->getStatusCode(), [
            Response::HTTP_OK,
            Response::HTTP_NOT_FOUND
            ]
        );
    }

    public function test_http_s3accesskey_post()
    {
        $this->setupGuzzle();
        $response = $this->http->request(
            'POST', '/s3/s3accesskey', [
            'form_params'   =>  [
                'access_key'  =>  'a',
                'secret_key_enc'  =>  'a',
                'role'  =>  'a',
                'status'  =>  'a',
                'revoked_reason'  =>  'a',
                    'expires_at'  =>  now(),
                    'last_used_at'  =>  now(),
                    'revoked_at'  =>  now(),
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
    public function test_s3accesskey_model_get()
    {
        $result = AbstractS3AccessKeyService::get();

        $this->assertIsObject($result, Collection::class);
    }

    public function test_s3accesskey_get_all()
    {
        $result = AbstractS3AccessKeyService::getAll();

        $this->assertIsObject($result, Collection::class);
    }

    public function test_s3accesskey_get_paginated()
    {
        $result = AbstractS3AccessKeyService::get(
            null, [
            'paginated' =>  'true'
            ]
        );

        $this->assertIsObject($result, LengthAwarePaginator::class);
    }

    public function test_s3accesskey_event_retrieved_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3AccessKey\S3AccessKeyRetrievedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3accesskey_event_created_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3AccessKey\S3AccessKeyCreatedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3accesskey_event_creating_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3AccessKey\S3AccessKeyCreatingEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3accesskey_event_saving_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3AccessKey\S3AccessKeySavingEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3accesskey_event_saved_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3AccessKey\S3AccessKeySavedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3accesskey_event_updating_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3AccessKey\S3AccessKeyUpdatingEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3accesskey_event_updated_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3AccessKey\S3AccessKeyUpdatedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3accesskey_event_deleting_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3AccessKey\S3AccessKeyDeletingEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3accesskey_event_deleted_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3AccessKey\S3AccessKeyDeletedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3accesskey_event_restoring_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3AccessKey\S3AccessKeyRestoringEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3accesskey_event_restored_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3AccessKey\S3AccessKeyRestoredEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3accesskey_event_retrieved_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3AccessKey::first();

            event(new \NextDeveloper\S3\Events\S3AccessKey\S3AccessKeyRetrievedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3accesskey_event_created_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3AccessKey::first();

            event(new \NextDeveloper\S3\Events\S3AccessKey\S3AccessKeyCreatedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3accesskey_event_creating_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3AccessKey::first();

            event(new \NextDeveloper\S3\Events\S3AccessKey\S3AccessKeyCreatingEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3accesskey_event_saving_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3AccessKey::first();

            event(new \NextDeveloper\S3\Events\S3AccessKey\S3AccessKeySavingEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3accesskey_event_saved_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3AccessKey::first();

            event(new \NextDeveloper\S3\Events\S3AccessKey\S3AccessKeySavedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3accesskey_event_updating_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3AccessKey::first();

            event(new \NextDeveloper\S3\Events\S3AccessKey\S3AccessKeyUpdatingEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3accesskey_event_updated_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3AccessKey::first();

            event(new \NextDeveloper\S3\Events\S3AccessKey\S3AccessKeyUpdatedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3accesskey_event_deleting_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3AccessKey::first();

            event(new \NextDeveloper\S3\Events\S3AccessKey\S3AccessKeyDeletingEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3accesskey_event_deleted_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3AccessKey::first();

            event(new \NextDeveloper\S3\Events\S3AccessKey\S3AccessKeyDeletedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3accesskey_event_restoring_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3AccessKey::first();

            event(new \NextDeveloper\S3\Events\S3AccessKey\S3AccessKeyRestoringEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3accesskey_event_restored_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3AccessKey::first();

            event(new \NextDeveloper\S3\Events\S3AccessKey\S3AccessKeyRestoredEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3accesskey_event_access_key_filter()
    {
        try {
            $request = new Request(
                [
                'access_key'  =>  'a'
                ]
            );

            $filter = new S3AccessKeyQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3AccessKey::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3accesskey_event_secret_key_enc_filter()
    {
        try {
            $request = new Request(
                [
                'secret_key_enc'  =>  'a'
                ]
            );

            $filter = new S3AccessKeyQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3AccessKey::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3accesskey_event_role_filter()
    {
        try {
            $request = new Request(
                [
                'role'  =>  'a'
                ]
            );

            $filter = new S3AccessKeyQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3AccessKey::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3accesskey_event_status_filter()
    {
        try {
            $request = new Request(
                [
                'status'  =>  'a'
                ]
            );

            $filter = new S3AccessKeyQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3AccessKey::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3accesskey_event_revoked_reason_filter()
    {
        try {
            $request = new Request(
                [
                'revoked_reason'  =>  'a'
                ]
            );

            $filter = new S3AccessKeyQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3AccessKey::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3accesskey_event_expires_at_filter_start()
    {
        try {
            $request = new Request(
                [
                'expires_atStart'  =>  now()
                ]
            );

            $filter = new S3AccessKeyQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3AccessKey::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3accesskey_event_last_used_at_filter_start()
    {
        try {
            $request = new Request(
                [
                'last_used_atStart'  =>  now()
                ]
            );

            $filter = new S3AccessKeyQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3AccessKey::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3accesskey_event_revoked_at_filter_start()
    {
        try {
            $request = new Request(
                [
                'revoked_atStart'  =>  now()
                ]
            );

            $filter = new S3AccessKeyQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3AccessKey::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3accesskey_event_created_at_filter_start()
    {
        try {
            $request = new Request(
                [
                'created_atStart'  =>  now()
                ]
            );

            $filter = new S3AccessKeyQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3AccessKey::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3accesskey_event_updated_at_filter_start()
    {
        try {
            $request = new Request(
                [
                'updated_atStart'  =>  now()
                ]
            );

            $filter = new S3AccessKeyQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3AccessKey::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3accesskey_event_deleted_at_filter_start()
    {
        try {
            $request = new Request(
                [
                'deleted_atStart'  =>  now()
                ]
            );

            $filter = new S3AccessKeyQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3AccessKey::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3accesskey_event_expires_at_filter_end()
    {
        try {
            $request = new Request(
                [
                'expires_atEnd'  =>  now()
                ]
            );

            $filter = new S3AccessKeyQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3AccessKey::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3accesskey_event_last_used_at_filter_end()
    {
        try {
            $request = new Request(
                [
                'last_used_atEnd'  =>  now()
                ]
            );

            $filter = new S3AccessKeyQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3AccessKey::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3accesskey_event_revoked_at_filter_end()
    {
        try {
            $request = new Request(
                [
                'revoked_atEnd'  =>  now()
                ]
            );

            $filter = new S3AccessKeyQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3AccessKey::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3accesskey_event_created_at_filter_end()
    {
        try {
            $request = new Request(
                [
                'created_atEnd'  =>  now()
                ]
            );

            $filter = new S3AccessKeyQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3AccessKey::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3accesskey_event_updated_at_filter_end()
    {
        try {
            $request = new Request(
                [
                'updated_atEnd'  =>  now()
                ]
            );

            $filter = new S3AccessKeyQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3AccessKey::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3accesskey_event_deleted_at_filter_end()
    {
        try {
            $request = new Request(
                [
                'deleted_atEnd'  =>  now()
                ]
            );

            $filter = new S3AccessKeyQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3AccessKey::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3accesskey_event_expires_at_filter_start_and_end()
    {
        try {
            $request = new Request(
                [
                'expires_atStart'  =>  now(),
                'expires_atEnd'  =>  now()
                ]
            );

            $filter = new S3AccessKeyQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3AccessKey::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3accesskey_event_last_used_at_filter_start_and_end()
    {
        try {
            $request = new Request(
                [
                'last_used_atStart'  =>  now(),
                'last_used_atEnd'  =>  now()
                ]
            );

            $filter = new S3AccessKeyQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3AccessKey::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3accesskey_event_revoked_at_filter_start_and_end()
    {
        try {
            $request = new Request(
                [
                'revoked_atStart'  =>  now(),
                'revoked_atEnd'  =>  now()
                ]
            );

            $filter = new S3AccessKeyQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3AccessKey::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3accesskey_event_created_at_filter_start_and_end()
    {
        try {
            $request = new Request(
                [
                'created_atStart'  =>  now(),
                'created_atEnd'  =>  now()
                ]
            );

            $filter = new S3AccessKeyQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3AccessKey::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3accesskey_event_updated_at_filter_start_and_end()
    {
        try {
            $request = new Request(
                [
                'updated_atStart'  =>  now(),
                'updated_atEnd'  =>  now()
                ]
            );

            $filter = new S3AccessKeyQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3AccessKey::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3accesskey_event_deleted_at_filter_start_and_end()
    {
        try {
            $request = new Request(
                [
                'deleted_atStart'  =>  now(),
                'deleted_atEnd'  =>  now()
                ]
            );

            $filter = new S3AccessKeyQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3AccessKey::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}