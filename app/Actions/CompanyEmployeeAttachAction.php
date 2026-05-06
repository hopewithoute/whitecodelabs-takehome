<?php

namespace App\Actions;

use App\Data\CompanyEmployeeData;
use App\Services\TimeEntryReferenceDataCache;

readonly class CompanyEmployeeAttachAction
{
    public function __construct(
        private TimeEntryReferenceDataCache $cache,
    ) {}

    public function execute(CompanyEmployeeData $data): void
    {
        $data->company->employees()->syncWithoutDetaching($data->employee);
        $this->cache->invalidateCompany($data->company);
    }
}
