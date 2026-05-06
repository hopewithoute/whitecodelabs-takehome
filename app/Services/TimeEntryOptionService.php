<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Task;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

readonly class TimeEntryOptionService
{
    public function __construct(
        private TimeEntryReferenceDataCache $cache,
    ) {}

    /**
     * @return Collection<int, Company>
     */
    public function companies(): Collection
    {
        $rows = Cache::remember(
            $this->cache->companiesKey(),
            $this->cache->ttl(),
            fn () => Company::query()
                ->select(['id', 'name', 'created_at', 'updated_at'])
                ->orderBy('name')
                ->get()
                ->map(fn (Company $c) => $c->toArray())
                ->all(),
        );

        return Company::hydrate($rows);
    }

    /**
     * @return Collection<int, Employee>
     */
    public function employeesForCompany(Company $company): Collection
    {
        $rows = Cache::remember(
            $this->cache->companyEmployeesKey($company),
            $this->cache->ttl(),
            fn () => $company->employees()
                ->select(['employees.id', 'employees.name', 'employees.email', 'employees.created_at', 'employees.updated_at'])
                ->orderBy('employees.name')
                ->get()
                ->map(fn (Employee $e) => $e->toArray())
                ->all(),
        );

        return Employee::hydrate($rows);
    }

    /**
     * @return Collection<int, Task>
     */
    public function tasksForCompany(Company $company): Collection
    {
        $rows = Cache::remember(
            $this->cache->companyTasksKey($company),
            $this->cache->ttl(),
            fn () => $company->tasks()
                ->select(['id', 'company_id', 'name', 'created_at', 'updated_at'])
                ->orderBy('name')
                ->get()
                ->map(fn (Task $t) => $t->toArray())
                ->all(),
        );

        return Task::hydrate($rows);
    }
}
