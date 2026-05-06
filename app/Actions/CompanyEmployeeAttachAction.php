<?php

namespace App\Actions;

use App\Data\CompanyEmployeeData;

readonly class CompanyEmployeeAttachAction
{
    public function execute(CompanyEmployeeData $data): void
    {
        $data->company->employees()->syncWithoutDetaching($data->employee);
    }
}
