<?php

namespace App\QueryBuilders;

use App\Models\Company;
use App\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class CompanyProjectIndexQuery extends QueryBuilder
{
    public function __construct(Request $request)
    {
        $company = $request->route('company');
        $query = Project::query()
            ->select(['id', 'company_id', 'name', 'created_at', 'updated_at']);

        if ($company instanceof Company) {
            $query->whereBelongsTo($company);
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
                                $company instanceof Company,
                                fn (Builder $query) => $query->where('employee_project.company_id', $company->id)
                            )
                    )
                ),
            )
            ->allowedSorts('name', 'created_at', 'updated_at')
            ->defaultSort('name');
    }
}
