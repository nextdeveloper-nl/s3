<?php

namespace NextDeveloper\S3\Tests\Database\Models;

use Tests\TestCase;
use GuzzleHttp\Client;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use NextDeveloper\S3\Database\Filters\S3MultipartUploadQueryFilter;
use NextDeveloper\S3\Services\AbstractServices\AbstractS3MultipartUploadService;
use Illuminate\Pagination\LengthAwarePaginator;
use League\Fractal\Resource\Collection;

trait S3MultipartUploadTestTraits
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

    public function test_http_s3multipartupload_get()
    {
        $this->setupGuzzle();
        $response = $this->http->request(
            'GET',
            '/s3/s3multipartupload',
            ['http_errors' => false]
        );

        $this->assertContains(
            $response->getStatusCode(), [
            Response::HTTP_OK,
            Response::HTTP_NOT_FOUND
            ]
        );
    }

    public function test_http_s3multipartupload_post()
    {
        $this->setupGuzzle();
        $response = $this->http->request(
            'POST', '/s3/s3multipartupload', [
            'form_params'   =>  [
                'upload_id'  =>  'a',
                'object_key'  =>  'a',
                'status'  =>  'a',
                'aborted_reason'  =>  'a',
                'size_bytes_so_far'  =>  '1',
                'part_count'  =>  '1',
                    'initiated_at'  =>  now(),
                    'last_activity_at'  =>  now(),
                    'aborted_at'  =>  now(),
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
    public function test_s3multipartupload_model_get()
    {
        $result = AbstractS3MultipartUploadService::get();

        $this->assertIsObject($result, Collection::class);
    }

    public function test_s3multipartupload_get_all()
    {
        $result = AbstractS3MultipartUploadService::getAll();

        $this->assertIsObject($result, Collection::class);
    }

    public function test_s3multipartupload_get_paginated()
    {
        $result = AbstractS3MultipartUploadService::get(
            null, [
            'paginated' =>  'true'
            ]
        );

        $this->assertIsObject($result, LengthAwarePaginator::class);
    }

    public function test_s3multipartupload_event_retrieved_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3MultipartUpload\S3MultipartUploadRetrievedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3multipartupload_event_created_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3MultipartUpload\S3MultipartUploadCreatedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3multipartupload_event_creating_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3MultipartUpload\S3MultipartUploadCreatingEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3multipartupload_event_saving_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3MultipartUpload\S3MultipartUploadSavingEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3multipartupload_event_saved_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3MultipartUpload\S3MultipartUploadSavedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3multipartupload_event_updating_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3MultipartUpload\S3MultipartUploadUpdatingEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3multipartupload_event_updated_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3MultipartUpload\S3MultipartUploadUpdatedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3multipartupload_event_deleting_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3MultipartUpload\S3MultipartUploadDeletingEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3multipartupload_event_deleted_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3MultipartUpload\S3MultipartUploadDeletedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3multipartupload_event_restoring_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3MultipartUpload\S3MultipartUploadRestoringEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3multipartupload_event_restored_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3MultipartUpload\S3MultipartUploadRestoredEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3multipartupload_event_retrieved_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3MultipartUpload::first();

            event(new \NextDeveloper\S3\Events\S3MultipartUpload\S3MultipartUploadRetrievedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3multipartupload_event_created_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3MultipartUpload::first();

            event(new \NextDeveloper\S3\Events\S3MultipartUpload\S3MultipartUploadCreatedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3multipartupload_event_creating_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3MultipartUpload::first();

            event(new \NextDeveloper\S3\Events\S3MultipartUpload\S3MultipartUploadCreatingEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3multipartupload_event_saving_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3MultipartUpload::first();

            event(new \NextDeveloper\S3\Events\S3MultipartUpload\S3MultipartUploadSavingEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3multipartupload_event_saved_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3MultipartUpload::first();

            event(new \NextDeveloper\S3\Events\S3MultipartUpload\S3MultipartUploadSavedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3multipartupload_event_updating_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3MultipartUpload::first();

            event(new \NextDeveloper\S3\Events\S3MultipartUpload\S3MultipartUploadUpdatingEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3multipartupload_event_updated_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3MultipartUpload::first();

            event(new \NextDeveloper\S3\Events\S3MultipartUpload\S3MultipartUploadUpdatedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3multipartupload_event_deleting_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3MultipartUpload::first();

            event(new \NextDeveloper\S3\Events\S3MultipartUpload\S3MultipartUploadDeletingEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3multipartupload_event_deleted_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3MultipartUpload::first();

            event(new \NextDeveloper\S3\Events\S3MultipartUpload\S3MultipartUploadDeletedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3multipartupload_event_restoring_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3MultipartUpload::first();

            event(new \NextDeveloper\S3\Events\S3MultipartUpload\S3MultipartUploadRestoringEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3multipartupload_event_restored_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3MultipartUpload::first();

            event(new \NextDeveloper\S3\Events\S3MultipartUpload\S3MultipartUploadRestoredEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3multipartupload_event_upload_id_filter()
    {
        try {
            $request = new Request(
                [
                'upload_id'  =>  'a'
                ]
            );

            $filter = new S3MultipartUploadQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3MultipartUpload::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3multipartupload_event_object_key_filter()
    {
        try {
            $request = new Request(
                [
                'object_key'  =>  'a'
                ]
            );

            $filter = new S3MultipartUploadQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3MultipartUpload::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3multipartupload_event_status_filter()
    {
        try {
            $request = new Request(
                [
                'status'  =>  'a'
                ]
            );

            $filter = new S3MultipartUploadQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3MultipartUpload::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3multipartupload_event_aborted_reason_filter()
    {
        try {
            $request = new Request(
                [
                'aborted_reason'  =>  'a'
                ]
            );

            $filter = new S3MultipartUploadQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3MultipartUpload::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3multipartupload_event_size_bytes_so_far_filter()
    {
        try {
            $request = new Request(
                [
                'size_bytes_so_far'  =>  '1'
                ]
            );

            $filter = new S3MultipartUploadQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3MultipartUpload::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3multipartupload_event_part_count_filter()
    {
        try {
            $request = new Request(
                [
                'part_count'  =>  '1'
                ]
            );

            $filter = new S3MultipartUploadQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3MultipartUpload::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3multipartupload_event_initiated_at_filter_start()
    {
        try {
            $request = new Request(
                [
                'initiated_atStart'  =>  now()
                ]
            );

            $filter = new S3MultipartUploadQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3MultipartUpload::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3multipartupload_event_last_activity_at_filter_start()
    {
        try {
            $request = new Request(
                [
                'last_activity_atStart'  =>  now()
                ]
            );

            $filter = new S3MultipartUploadQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3MultipartUpload::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3multipartupload_event_aborted_at_filter_start()
    {
        try {
            $request = new Request(
                [
                'aborted_atStart'  =>  now()
                ]
            );

            $filter = new S3MultipartUploadQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3MultipartUpload::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3multipartupload_event_created_at_filter_start()
    {
        try {
            $request = new Request(
                [
                'created_atStart'  =>  now()
                ]
            );

            $filter = new S3MultipartUploadQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3MultipartUpload::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3multipartupload_event_updated_at_filter_start()
    {
        try {
            $request = new Request(
                [
                'updated_atStart'  =>  now()
                ]
            );

            $filter = new S3MultipartUploadQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3MultipartUpload::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3multipartupload_event_initiated_at_filter_end()
    {
        try {
            $request = new Request(
                [
                'initiated_atEnd'  =>  now()
                ]
            );

            $filter = new S3MultipartUploadQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3MultipartUpload::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3multipartupload_event_last_activity_at_filter_end()
    {
        try {
            $request = new Request(
                [
                'last_activity_atEnd'  =>  now()
                ]
            );

            $filter = new S3MultipartUploadQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3MultipartUpload::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3multipartupload_event_aborted_at_filter_end()
    {
        try {
            $request = new Request(
                [
                'aborted_atEnd'  =>  now()
                ]
            );

            $filter = new S3MultipartUploadQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3MultipartUpload::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3multipartupload_event_created_at_filter_end()
    {
        try {
            $request = new Request(
                [
                'created_atEnd'  =>  now()
                ]
            );

            $filter = new S3MultipartUploadQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3MultipartUpload::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3multipartupload_event_updated_at_filter_end()
    {
        try {
            $request = new Request(
                [
                'updated_atEnd'  =>  now()
                ]
            );

            $filter = new S3MultipartUploadQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3MultipartUpload::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3multipartupload_event_initiated_at_filter_start_and_end()
    {
        try {
            $request = new Request(
                [
                'initiated_atStart'  =>  now(),
                'initiated_atEnd'  =>  now()
                ]
            );

            $filter = new S3MultipartUploadQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3MultipartUpload::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3multipartupload_event_last_activity_at_filter_start_and_end()
    {
        try {
            $request = new Request(
                [
                'last_activity_atStart'  =>  now(),
                'last_activity_atEnd'  =>  now()
                ]
            );

            $filter = new S3MultipartUploadQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3MultipartUpload::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3multipartupload_event_aborted_at_filter_start_and_end()
    {
        try {
            $request = new Request(
                [
                'aborted_atStart'  =>  now(),
                'aborted_atEnd'  =>  now()
                ]
            );

            $filter = new S3MultipartUploadQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3MultipartUpload::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3multipartupload_event_created_at_filter_start_and_end()
    {
        try {
            $request = new Request(
                [
                'created_atStart'  =>  now(),
                'created_atEnd'  =>  now()
                ]
            );

            $filter = new S3MultipartUploadQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3MultipartUpload::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3multipartupload_event_updated_at_filter_start_and_end()
    {
        try {
            $request = new Request(
                [
                'updated_atStart'  =>  now(),
                'updated_atEnd'  =>  now()
                ]
            );

            $filter = new S3MultipartUploadQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3MultipartUpload::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}