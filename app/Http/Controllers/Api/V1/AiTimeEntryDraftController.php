<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\TimeEntries\ParseTimeEntryDraftsAction;
use App\Data\AiTimeEntryPromptData;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class AiTimeEntryDraftController extends Controller
{
    public function store(AiTimeEntryPromptData $data, ParseTimeEntryDraftsAction $action): JsonResponse
    {
        return response()->json([
            'data' => $action->execute($data),
        ], 201);
    }
}
