<?php

namespace App\QueryBuilders;

use App\Models\Company;
use App\Models\Project;
use App\Services\TimeEntryReferenceDataCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class CompanyProjectIndexQuery extends QueryBuilder
{
    private ?Company $company;

    private ?string $employeeId;

    private string $sort;

    public function __construct(Request $request)
    {
        $company = $request->route('company');
        $this->company = $company instanceof Company ? $company : null;
        $employeeId = $request->input('filter.employee_id');
        $this->employeeId = is_string($employeeId) ? $employeeId : null;
        $sort = $request->input('sort', 'name');
        $this->sort = is_string($sort) ? $sort : 'name';
        $query = Project::query()
            ->select(['id', 'company_id', 'name', 'created_at', 'updated_at']);

        if ($this->company instanceof Company) {
            $query->whereBelongsTo($this->company);
        }

        parent::__construct($query, $request);

        $this
            ->allowedFilters(
                AllowedFilter::callback(
                    'employee_id',
                    fn (Builder $query, string $employeeId) => $query->whereHas(
                        'employees',
                        fn (Builder $employeeQuery) => $employeeQuery
                            ->whereKey($employeeId)
                            ->when(
                                $this->company instanceof Company,
                                fn (Builder $query) => $query->where('employee_project.company_id', $this->company->id)
                            )
                    )
                ),
            )
            ->allowedSorts('name', 'created_at', 'updated_at')
            ->defaultSort('name');
    }

    /**
     * @return Collection<int, Project>
     */
    public function cachedGet(TimeEntryReferenceDataCache $cache): Collection
    {
        if (! $this->company instanceof Company) {
            return $this->get();
        }

        $rows = Cache::remember(
            $cache->companyProjectsKey($this->company, $this->employeeId, $this->sort),
            $cache->ttl(),
            fn () => $this->get()->map(fn (Project $p) => $p->toArray())->all(),
        );

        return Project::hydrate($rows);
    }
}
