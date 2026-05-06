<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Task;
use Illuminate\Database\Eloquent\Collection;

class TimeEntryOptionService
{
    /**
     * @return Collection<int, Company>
     */
    public function companies(): Collection
    {
        return Company::query()
            ->select(['id', 'name', 'created_at', 'updated_at'])
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, Employee>
     */
    public function employeesForCompany(Company $company): Collection
    {
        return $company->employees()
            ->select(['employees.id', 'employees.name', 'employees.email', 'employees.created_at', 'employees.updated_at'])
            ->orderBy('employees.name')
            ->get();
    }

    /**
     * @return Collection<int, Task>
     */
    public function tasksForCompany(Company $company): Collection
    {
        return $company->tasks()
            ->select(['id', 'company_id', 'name', 'created_at', 'updated_at'])
            ->orderBy('name')
            ->get();
    }
}
