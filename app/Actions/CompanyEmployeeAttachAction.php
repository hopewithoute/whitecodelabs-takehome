<?php

namespace App\Actions;

use App\Models\Company;
use App\Models\Employee;

readonly class CompanyEmployeeAttachAction
{
    public function execute(Company $company, Employee $employee): void
    {
        $company->employees()->syncWithoutDetaching($employee);
    }
}
