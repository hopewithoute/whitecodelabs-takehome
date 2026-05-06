<?php

namespace App\QueryBuilders;

use App\Models\TimeEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class TimeEntryIndexQuery extends QueryBuilder
{
    public function __construct(private readonly Request $baseRequest)
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

        parent::__construct($query, $baseRequest);

        $this
            ->allowedFilters(
                AllowedFilter::exact('company_id'),
                AllowedFilter::callback('search', function (Builder $query, mixed $value): void {
                    $search = Str::of($value)->trim()->toString();

                    if ($search === '') {
                        return;
                    }

                    $pattern = "{$search}%";

                    $query->where(function (Builder $query) use ($pattern): void {
                        $query
                            ->whereHas('company', fn (Builder $query) => $query->where('name', 'like', $pattern))
                            ->orWhereHas('employee', fn (Builder $query) => $query->where('name', 'like', $pattern))
                            ->orWhereHas('project', fn (Builder $query) => $query->where('name', 'like', $pattern))
                            ->orWhereHas('task', fn (Builder $query) => $query->where('name', 'like', $pattern));
                    });
                }),
            )
            ->allowedSorts('entry_date', 'created_at', 'hours')
            ->defaultSort('-entry_date', '-created_at');
    }

    public function jsonPaginate(): LengthAwarePaginator
    {
        return $this
            ->paginate($this->perPage(), ['*'], 'page')
            ->appends($this->baseRequest->query());
    }

    /**
     * @return array{
     *     total_hours: string,
     *     by_company: array<int, array{id: string|null, name: string|null, total_hours: string}>,
     *     by_employee: array<int, array{id: string|null, name: string|null, total_hours: string}>,
     *     by_project: array<int, array{id: string|null, name: string|null, total_hours: string}>,
     *     by_task: array<int, array{id: string|null, name: string|null, total_hours: string}>,
     *     by_date: array<int, array{date: string|null, total_hours: string}>
     * }
     */
    public function summaryTotals(): array
    {
        return [
            'total_hours' => $this->formatHours($this->summaryBaseQuery()->sum('hours')),
            'by_company' => $this->groupedHours('companies', 'company_id'),
            'by_employee' => $this->groupedHours('employees', 'employee_id'),
            'by_project' => $this->groupedHours('projects', 'project_id'),
            'by_task' => $this->groupedHours('tasks', 'task_id'),
            'by_date' => $this->dateHours(),
        ];
    }

    private function perPage(): int
    {
        return min(max($this->baseRequest->integer('per_page', 10), 1), 50);
    }

    private function summaryBaseQuery(): Builder
    {
        $query = (clone $this->getEloquentBuilder())
            ->withoutEagerLoads()
            ->reorder();

        $query->getQuery()->limit = null;
        $query->getQuery()->offset = null;

        return $query;
    }

    /**
     * @return array<int, array{id: string|null, name: string|null, total_hours: string}>
     */
    private function groupedHours(string $relatedTable, string $foreignKey): array
    {
        $timeEntriesTable = (new TimeEntry)->getTable();

        return $this->summaryBaseQuery()
            ->join($relatedTable, "{$relatedTable}.id", '=', "{$timeEntriesTable}.{$foreignKey}")
            ->select([
                "{$relatedTable}.id",
                "{$relatedTable}.name",
            ])
            ->selectRaw("sum({$timeEntriesTable}.hours) as total_hours")
            ->groupBy("{$relatedTable}.id", "{$relatedTable}.name")
            ->orderByDesc('total_hours')
            ->orderBy("{$relatedTable}.name")
            ->get()
            ->map(fn (TimeEntry $row): array => [
                'id' => $row->id,
                'name' => $row->name,
                'total_hours' => $this->formatHours($row->total_hours),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{date: string|null, total_hours: string}>
     */
    private function dateHours(): array
    {
        $timeEntriesTable = (new TimeEntry)->getTable();

        return $this->summaryBaseQuery()
            ->select(["{$timeEntriesTable}.entry_date"])
            ->selectRaw("sum({$timeEntriesTable}.hours) as total_hours")
            ->groupBy("{$timeEntriesTable}.entry_date")
            ->orderByDesc("{$timeEntriesTable}.entry_date")
            ->get()
            ->map(fn (TimeEntry $row): array => [
                'date' => $row->entry_date?->toDateString(),
                'total_hours' => $this->formatHours($row->total_hours),
            ])
            ->values()
            ->all();
    }

    private function formatHours(mixed $hours): string
    {
        return number_format($hours ?? 0, 2, '.', '');
    }
}
