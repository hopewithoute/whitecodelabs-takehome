<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\TimeEntries\CreateTimeEntryBatchAction;
use App\Actions\TimeEntries\UpdateTimeEntryAction;
use App\Data\TimeEntryBatchData;
use App\Data\TimeEntryUpdateData;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\TimeEntryResource;
use App\Models\TimeEntry;
use App\QueryBuilders\TimeEntryIndexQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TimeEntryController extends Controller
{
    public function index(TimeEntryIndexQuery $query): AnonymousResourceCollection
    {
        return TimeEntryResource::collection($query->jsonPaginate())
            ->additional([
                'meta' => [
                    'summary' => $query->summaryTotals(),
                ],
            ]);
    }

    public function store(TimeEntryBatchData $data, CreateTimeEntryBatchAction $action): JsonResponse
    {
        $entries = $action->execute($data);

        return TimeEntryResource::collection($entries)
            ->response()
            ->setStatusCode(201);
    }

    public function update(TimeEntry $timeEntry, TimeEntryUpdateData $data, UpdateTimeEntryAction $action): TimeEntryResource
    {
        return new TimeEntryResource($action->execute($timeEntry, $data));
    }
}
