<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\TaskResource;
use App\Models\Company;
use App\Services\TimeEntryOptionService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CompanyTaskController extends Controller
{
    public function index(Company $company, TimeEntryOptionService $options): AnonymousResourceCollection
    {
        return TaskResource::collection($options->tasksForCompany($company));
    }
}
