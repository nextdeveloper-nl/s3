<?php

namespace NextDeveloper\S3\Tests\Database\Models;

use Tests\TestCase;
use GuzzleHttp\Client;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use NextDeveloper\S3\Database\Filters\S3DepositLedgerQueryFilter;
use NextDeveloper\S3\Services\AbstractServices\AbstractS3DepositLedgerService;
use Illuminate\Pagination\LengthAwarePaginator;
use League\Fractal\Resource\Collection;

trait S3DepositLedgerTestTraits
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

    public function test_http_s3depositledger_get()
    {
        $this->setupGuzzle();
        $response = $this->http->request(
            'GET',
            '/s3/s3depositledger',
            ['http_errors' => false]
        );

        $this->assertContains(
            $response->getStatusCode(), [
            Response::HTTP_OK,
            Response::HTTP_NOT_FOUND
            ]
        );
    }

    public function test_http_s3depositledger_post()
    {
        $this->setupGuzzle();
        $response = $this->http->request(
            'POST', '/s3/s3depositledger', [
            'form_params'   =>  [
                'type'  =>  'a',
                'reference'  =>  'a',
                'performed_by'  =>  'a',
                'notes'  =>  'a',
                'days_remaining'  =>  '1',
                'days_total'  =>  '1',
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
    public function test_s3depositledger_model_get()
    {
        $result = AbstractS3DepositLedgerService::get();

        $this->assertIsObject($result, Collection::class);
    }

    public function test_s3depositledger_get_all()
    {
        $result = AbstractS3DepositLedgerService::getAll();

        $this->assertIsObject($result, Collection::class);
    }

    public function test_s3depositledger_get_paginated()
    {
        $result = AbstractS3DepositLedgerService::get(
            null, [
            'paginated' =>  'true'
            ]
        );

        $this->assertIsObject($result, LengthAwarePaginator::class);
    }

    public function test_s3depositledger_event_retrieved_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3DepositLedger\S3DepositLedgerRetrievedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3depositledger_event_created_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3DepositLedger\S3DepositLedgerCreatedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3depositledger_event_creating_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3DepositLedger\S3DepositLedgerCreatingEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3depositledger_event_saving_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3DepositLedger\S3DepositLedgerSavingEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3depositledger_event_saved_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3DepositLedger\S3DepositLedgerSavedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3depositledger_event_updating_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3DepositLedger\S3DepositLedgerUpdatingEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3depositledger_event_updated_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3DepositLedger\S3DepositLedgerUpdatedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3depositledger_event_deleting_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3DepositLedger\S3DepositLedgerDeletingEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3depositledger_event_deleted_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3DepositLedger\S3DepositLedgerDeletedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3depositledger_event_restoring_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3DepositLedger\S3DepositLedgerRestoringEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3depositledger_event_restored_without_object()
    {
        try {
            event(new \NextDeveloper\S3\Events\S3DepositLedger\S3DepositLedgerRestoredEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3depositledger_event_retrieved_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3DepositLedger::first();

            event(new \NextDeveloper\S3\Events\S3DepositLedger\S3DepositLedgerRetrievedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3depositledger_event_created_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3DepositLedger::first();

            event(new \NextDeveloper\S3\Events\S3DepositLedger\S3DepositLedgerCreatedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3depositledger_event_creating_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3DepositLedger::first();

            event(new \NextDeveloper\S3\Events\S3DepositLedger\S3DepositLedgerCreatingEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3depositledger_event_saving_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3DepositLedger::first();

            event(new \NextDeveloper\S3\Events\S3DepositLedger\S3DepositLedgerSavingEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3depositledger_event_saved_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3DepositLedger::first();

            event(new \NextDeveloper\S3\Events\S3DepositLedger\S3DepositLedgerSavedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3depositledger_event_updating_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3DepositLedger::first();

            event(new \NextDeveloper\S3\Events\S3DepositLedger\S3DepositLedgerUpdatingEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3depositledger_event_updated_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3DepositLedger::first();

            event(new \NextDeveloper\S3\Events\S3DepositLedger\S3DepositLedgerUpdatedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3depositledger_event_deleting_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3DepositLedger::first();

            event(new \NextDeveloper\S3\Events\S3DepositLedger\S3DepositLedgerDeletingEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3depositledger_event_deleted_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3DepositLedger::first();

            event(new \NextDeveloper\S3\Events\S3DepositLedger\S3DepositLedgerDeletedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3depositledger_event_restoring_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3DepositLedger::first();

            event(new \NextDeveloper\S3\Events\S3DepositLedger\S3DepositLedgerRestoringEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_s3depositledger_event_restored_with_object()
    {
        try {
            $model = \NextDeveloper\S3\Database\Models\S3DepositLedger::first();

            event(new \NextDeveloper\S3\Events\S3DepositLedger\S3DepositLedgerRestoredEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3depositledger_event_type_filter()
    {
        try {
            $request = new Request(
                [
                'type'  =>  'a'
                ]
            );

            $filter = new S3DepositLedgerQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3DepositLedger::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3depositledger_event_reference_filter()
    {
        try {
            $request = new Request(
                [
                'reference'  =>  'a'
                ]
            );

            $filter = new S3DepositLedgerQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3DepositLedger::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3depositledger_event_performed_by_filter()
    {
        try {
            $request = new Request(
                [
                'performed_by'  =>  'a'
                ]
            );

            $filter = new S3DepositLedgerQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3DepositLedger::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3depositledger_event_notes_filter()
    {
        try {
            $request = new Request(
                [
                'notes'  =>  'a'
                ]
            );

            $filter = new S3DepositLedgerQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3DepositLedger::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3depositledger_event_days_remaining_filter()
    {
        try {
            $request = new Request(
                [
                'days_remaining'  =>  '1'
                ]
            );

            $filter = new S3DepositLedgerQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3DepositLedger::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3depositledger_event_days_total_filter()
    {
        try {
            $request = new Request(
                [
                'days_total'  =>  '1'
                ]
            );

            $filter = new S3DepositLedgerQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3DepositLedger::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3depositledger_event_performed_at_filter_start()
    {
        try {
            $request = new Request(
                [
                'performed_atStart'  =>  now()
                ]
            );

            $filter = new S3DepositLedgerQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3DepositLedger::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3depositledger_event_performed_at_filter_end()
    {
        try {
            $request = new Request(
                [
                'performed_atEnd'  =>  now()
                ]
            );

            $filter = new S3DepositLedgerQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3DepositLedger::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_s3depositledger_event_performed_at_filter_start_and_end()
    {
        try {
            $request = new Request(
                [
                'performed_atStart'  =>  now(),
                'performed_atEnd'  =>  now()
                ]
            );

            $filter = new S3DepositLedgerQueryFilter($request);

            $model = \NextDeveloper\S3\Database\Models\S3DepositLedger::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}