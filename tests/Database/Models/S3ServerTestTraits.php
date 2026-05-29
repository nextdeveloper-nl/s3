<?php

namespace NextDeveloper\S3\Tests\Database\Models;

use Tests\TestCase;
use GuzzleHttp\Client;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use NextDeveloper\S3\Database\Filters\S3ServerQueryFilter;
use NextDeveloper\S3\Services\AbstractServices\AbstractS3ServerService;
use Illuminate\Pagination\LengthAwarePaginator;
use League\Fractal\Resource\Collection;

trait S3ServerTestTraits
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

    public function test_http_s3server_get()
    {
        $this->setupGuzzle();
        $response = $this->http->request(
            'GET',
            '/s3/s3server',
            ['http_errors' => false]
        );

        $this->assertContains(
            $response->getStatusCode(), [
            Response::HTTP_OK,
            Response::HTTP_NOT_FOUND
            ]
        );
    }

    public function test_http_s3server_post()
    {
        $this->setupGuzzle();
        $response = $this->http->request(
            'POST', '/s3/s3server', [
            'form_params'   =>  [
                'hostname'  =>  'a',
                'name'  =>  'a',
                'agent_api_key'  =>  'a',
                'agent_version'  =>  'a',
                'seaweedfs_version'  =>  'a',
                'agent_status'  =>  'a',
                'health'  =>  'a',
                'health_summary'  =>  'a',
                    'agent_last_seen_at'  =>  now(),
                    'agent_connected_at'  =>  now(),
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
    public function test_s3server_model_get()
    {
        $result = AbstractS3ServerService::get();

        $this->assertIsObject($result, Collection::class);
    }

    public function test_s3server_get_all()
    {
        $result = AbstractS3ServerService::getAll();

        $this->assertIsObject($result, Collection::class);
    }

    public function test_s3server_get_paginated()
    {
        $result = AbstractS3ServerService::get(
            null, [
            'paginated' =>  'true'
            ]
        );

        $this->assertIsObject($result, LengthAwarePaginator::class);
    }

    public function test_s3server_event_retrieved_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3Server\S3ServerRetrievedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3server_event_created_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3Server\S3ServerCreatedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3server_event_creating_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3Server\S3ServerCreatingEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3server_event_saving_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3Server\S3ServerSavingEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3server_event_saved_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3Server\S3ServerSavedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3server_event_updating_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3Server\S3ServerUpdatingEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3server_event_updated_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3Server\S3ServerUpdatedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3server_event_deleting_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3Server\S3ServerDeletingEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3server_event_deleted_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3Server\S3ServerDeletedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3server_event_restoring_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3Server\S3ServerRestoringEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3server_event_restored_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3Server\S3ServerRestoredEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3server_event_retrieved_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3Server::first();

            event(new \NextDeveloper\S3\Events\S3Server\S3ServerRetrievedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3server_event_created_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3Server::first();

            event(new \NextDeveloper\S3\Events\S3Server\S3ServerCreatedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3server_event_creating_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3Server::first();

            event(new \NextDeveloper\S3\Events\S3Server\S3ServerCreatingEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3server_event_saving_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3Server::first();

            event(new \NextDeveloper\S3\Events\S3Server\S3ServerSavingEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3server_event_saved_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3Server::first();

            event(new \NextDeveloper\S3\Events\S3Server\S3ServerSavedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3server_event_updating_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3Server::first();

            event(new \NextDeveloper\S3\Events\S3Server\S3ServerUpdatingEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3server_event_updated_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3Server::first();

            event(new \NextDeveloper\S3\Events\S3Server\S3ServerUpdatedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3server_event_deleting_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3Server::first();

            event(new \NextDeveloper\S3\Events\S3Server\S3ServerDeletingEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3server_event_deleted_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3Server::first();

            event(new \NextDeveloper\S3\Events\S3Server\S3ServerDeletedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3server_event_restoring_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3Server::first();

            event(new \NextDeveloper\S3\Events\S3Server\S3ServerRestoringEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3server_event_restored_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3Server::first();

            event(new \NextDeveloper\S3\Events\S3Server\S3ServerRestoredEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3server_event_hostname_filter()
    {
        try {
            $request = new Request(
                [
                'hostname'  =>  'a'
                ]
            );

            $filter = new S3ServerQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Server::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3server_event_name_filter()
    {
        try {
            $request = new Request(
                [
                'name'  =>  'a'
                ]
            );

            $filter = new S3ServerQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Server::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3server_event_agent_api_key_filter()
    {
        try {
            $request = new Request(
                [
                'agent_api_key'  =>  'a'
                ]
            );

            $filter = new S3ServerQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Server::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3server_event_agent_version_filter()
    {
        try {
            $request = new Request(
                [
                'agent_version'  =>  'a'
                ]
            );

            $filter = new S3ServerQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Server::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3server_event_seaweedfs_version_filter()
    {
        try {
            $request = new Request(
                [
                'seaweedfs_version'  =>  'a'
                ]
            );

            $filter = new S3ServerQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Server::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3server_event_agent_status_filter()
    {
        try {
            $request = new Request(
                [
                'agent_status'  =>  'a'
                ]
            );

            $filter = new S3ServerQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Server::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3server_event_health_filter()
    {
        try {
            $request = new Request(
                [
                'health'  =>  'a'
                ]
            );

            $filter = new S3ServerQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Server::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3server_event_health_summary_filter()
    {
        try {
            $request = new Request(
                [
                'health_summary'  =>  'a'
                ]
            );

            $filter = new S3ServerQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Server::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3server_event_agent_last_seen_at_filter_start()
    {
        try {
            $request = new Request(
                [
                'agent_last_seen_atStart'  =>  now()
                ]
            );

            $filter = new S3ServerQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Server::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3server_event_agent_connected_at_filter_start()
    {
        try {
            $request = new Request(
                [
                'agent_connected_atStart'  =>  now()
                ]
            );

            $filter = new S3ServerQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Server::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3server_event_created_at_filter_start()
    {
        try {
            $request = new Request(
                [
                'created_atStart'  =>  now()
                ]
            );

            $filter = new S3ServerQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Server::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3server_event_updated_at_filter_start()
    {
        try {
            $request = new Request(
                [
                'updated_atStart'  =>  now()
                ]
            );

            $filter = new S3ServerQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Server::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3server_event_deleted_at_filter_start()
    {
        try {
            $request = new Request(
                [
                'deleted_atStart'  =>  now()
                ]
            );

            $filter = new S3ServerQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Server::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3server_event_agent_last_seen_at_filter_end()
    {
        try {
            $request = new Request(
                [
                'agent_last_seen_atEnd'  =>  now()
                ]
            );

            $filter = new S3ServerQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Server::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3server_event_agent_connected_at_filter_end()
    {
        try {
            $request = new Request(
                [
                'agent_connected_atEnd'  =>  now()
                ]
            );

            $filter = new S3ServerQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Server::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3server_event_created_at_filter_end()
    {
        try {
            $request = new Request(
                [
                'created_atEnd'  =>  now()
                ]
            );

            $filter = new S3ServerQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Server::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3server_event_updated_at_filter_end()
    {
        try {
            $request = new Request(
                [
                'updated_atEnd'  =>  now()
                ]
            );

            $filter = new S3ServerQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Server::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3server_event_deleted_at_filter_end()
    {
        try {
            $request = new Request(
                [
                'deleted_atEnd'  =>  now()
                ]
            );

            $filter = new S3ServerQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Server::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3server_event_agent_last_seen_at_filter_start_and_end()
    {
        try {
            $request = new Request(
                [
                'agent_last_seen_atStart'  =>  now(),
                'agent_last_seen_atEnd'  =>  now()
                ]
            );

            $filter = new S3ServerQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Server::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3server_event_agent_connected_at_filter_start_and_end()
    {
        try {
            $request = new Request(
                [
                'agent_connected_atStart'  =>  now(),
                'agent_connected_atEnd'  =>  now()
                ]
            );

            $filter = new S3ServerQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Server::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3server_event_created_at_filter_start_and_end()
    {
        try {
            $request = new Request(
                [
                'created_atStart'  =>  now(),
                'created_atEnd'  =>  now()
                ]
            );

            $filter = new S3ServerQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Server::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3server_event_updated_at_filter_start_and_end()
    {
        try {
            $request = new Request(
                [
                'updated_atStart'  =>  now(),
                'updated_atEnd'  =>  now()
                ]
            );

            $filter = new S3ServerQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Server::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3server_event_deleted_at_filter_start_and_end()
    {
        try {
            $request = new Request(
                [
                'deleted_atStart'  =>  now(),
                'deleted_atEnd'  =>  now()
                ]
            );

            $filter = new S3ServerQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3Server::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}