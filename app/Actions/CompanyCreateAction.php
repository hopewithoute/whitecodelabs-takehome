<?php

namespace App\Actions;

use App\Data\CompanyData;
use App\Models\Company;
use App\Services\TimeEntryReferenceDataCache;

readonly class CompanyCreateAction
{
    public function __construct(
        private TimeEntryReferenceDataCache $cache,
    ) {}

    public function execute(CompanyData $data): Company
    {
        $company = Company::query()->create($data->toModelData());

        $this->cache->invalidateCompanies();

        return $company;
    }
}
