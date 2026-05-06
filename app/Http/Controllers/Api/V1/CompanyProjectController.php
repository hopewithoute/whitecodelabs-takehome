<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ProjectResource;
use App\Models\Company;
use App\QueryBuilders\CompanyProjectIndexQuery;
use App\Services\TimeEntryReferenceDataCache;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CompanyProjectController extends Controller
{
    public function index(
        Company $company,
        CompanyProjectIndexQuery $query,
        TimeEntryReferenceDataCache $cache,
    ): AnonymousResourceCollection {
        return ProjectResource::collection($query->cachedGet($cache));
    }
}
