<?php

namespace App\QueryBuilders;

use App\Models\TimeEntry;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class TimeEntryIndexQuery extends QueryBuilder
{
    public function __construct(Request $request)
    {
        $query = TimeEntry::query()
            ->select([
                'id',
                'company_id',
                'employee_id',
                'project_id',
                'task_id',
                'entry_date',
                'hours',
                'created_at',
                'updated_at',
            ])
            ->with([
                'company:id,name,created_at,updated_at',
                'employee:id,name,email,created_at,updated_at',
                'project:id,company_id,name,created_at,updated_at',
                'task:id,company_id,name,created_at,updated_at',
            ]);

        parent::__construct($query, $request);

        $this
            ->allowedFilters(
                AllowedFilter::exact('company_id'),
            )
            ->allowedSorts('entry_date', 'created_at', 'hours')
            ->defaultSort('-entry_date', '-created_at');
    }
}
