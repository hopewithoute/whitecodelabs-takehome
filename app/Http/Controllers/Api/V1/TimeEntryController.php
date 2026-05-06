<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\TimeEntries\CreateTimeEntryBatchAction;
use App\Data\TimeEntryBatchData;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\TimeEntryResource;
use App\QueryBuilders\TimeEntryIndexQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TimeEntryController extends Controller
{
    public function index(TimeEntryIndexQuery $query): AnonymousResourceCollection
    {
        return TimeEntryResource::collection($query->get());
    }

    public function store(TimeEntryBatchData $data, CreateTimeEntryBatchAction $action): JsonResponse
    {
        $entries = $action->execute($data);

        return TimeEntryResource::collection($entries)
            ->response()
            ->setStatusCode(201);
    }
}
