<?php

namespace App\Actions;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Project;

readonly class EmployeeProjectAssignAction
{
    public function execute(Employee $employee, Project $project, Company $company): void
    {
        $employee->projects()->syncWithoutDetaching([
            $project->id => [
                'company_id' => $company->id,
            ],
        ]);
    }
}
