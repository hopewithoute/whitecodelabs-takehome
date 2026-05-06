<?php

namespace App\Actions;

use App\Data\EmployeeProjectData;

readonly class EmployeeProjectAssignAction
{
    public function execute(EmployeeProjectData $data): void
    {
        $data->employee->projects()->syncWithoutDetaching([
            $data->project->id => $data->toPivotData(),
        ]);
    }
}
