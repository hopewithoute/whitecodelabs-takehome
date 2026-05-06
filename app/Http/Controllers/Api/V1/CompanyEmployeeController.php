<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\EmployeeResource;
use App\Models\Company;
use App\Services\TimeEntryOptionService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CompanyEmployeeController extends Controller
{
    public function index(Company $company, TimeEntryOptionService $options): AnonymousResourceCollection
    {
        return EmployeeResource::collection($options->employeesForCompany($company));
    }
}
