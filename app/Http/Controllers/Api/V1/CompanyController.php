<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\CompanyResource;
use App\Services\TimeEntryOptionService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CompanyController extends Controller
{
    public function index(TimeEntryOptionService $options): AnonymousResourceCollection
    {
        return CompanyResource::collection($options->companies());
    }
}
