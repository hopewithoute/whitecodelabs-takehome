<?php

namespace App\Actions;

use App\Data\EmployeeProjectData;
use App\Services\TimeEntryReferenceDataCache;

readonly class EmployeeProjectAssignAction
{
    public function __construct(
        private TimeEntryReferenceDataCache $cache,
    ) {}

    public function execute(EmployeeProjectData $data): void
    {
        $data->employee->projects()->syncWithoutDetaching([
            $data->project->id => $data->toPivotData(),
        ]);

        $this->cache->invalidateCompany($data->company);
    }
}
