<?php

namespace App\Actions;

use App\Data\EmployeeData;
use App\Models\Employee;

readonly class EmployeeCreateAction
{
    public function execute(EmployeeData $data): Employee
    {
        return Employee::query()->create($data->toModelData());
    }
}
