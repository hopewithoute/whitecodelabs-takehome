<?php

namespace App\Domain\TimeEntries;

use App\Data\TimeEntryBatchData;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Throwable;

class TimeEntryBatchBusinessValidator
{
    /**
     * @throws ValidationException
     */
    public function validate(TimeEntryBatchData $data): void
    {
        $context = $this->buildContext($data);
        $errors = [];

        foreach ($data->entries as $index => $entry) {
            $this->validateCompanyConsistency($errors, $context, $entry, $index);
            $this->validateEmployeeProjectAssignment($errors, $context, $entry, $index);
            $this->validateBatchProjectConflict($errors, $context, $entry, $index);
            $this->validateExistingProjectConflict($errors, $context, $entry, $index);
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @return array{
     *     employees: EloquentCollection<int, Employee>,
     *     projects: EloquentCollection<int, Project>,
     *     tasks: EloquentCollection<int, Task>,
     *     existing_entries: Collection<string, Collection<int, TimeEntry>>,
     *     batch_projects_by_employee_date: Collection<string, Collection<int, string>>
     * }
     */
    private function buildContext(TimeEntryBatchData $data): array
    {
        $employeeIds = collect($data->entries)->pluck('employee_id')->unique()->values();
        $projectIds = collect($data->entries)->pluck('project_id')->unique()->values();
        $taskIds = collect($data->entries)->pluck('task_id')->unique()->values();
        $entryDates = collect($data->entries)
            ->pluck('entry_date')
            ->map(fn (mixed $date) => $this->dateKey($date))
            ->filter()
            ->unique()
            ->values();

        return [
            'employees' => Employee::query()
                ->whereKey($employeeIds)
                ->with([
                    'companies:id',
                    'projects' => fn ($query) => $query
                        ->select(['projects.id'])
                        ->withPivot('company_id'),
                ])
                ->get()
                ->keyBy('id'),
            'projects' => Project::query()
                ->whereKey($projectIds)
                ->get(['id', 'company_id'])
                ->keyBy('id'),
            'tasks' => Task::query()
                ->whereKey($taskIds)
                ->get(['id', 'company_id'])
                ->keyBy('id'),
            'existing_entries' => TimeEntry::query()
                ->whereIn('employee_id', $employeeIds)
                ->where(function ($query) use ($entryDates): void {
                    foreach ($entryDates as $entryDate) {
                        $query->orWhereDate('entry_date', $entryDate);
                    }
                })
                ->get(['id', 'employee_id', 'project_id', 'entry_date'])
                ->groupBy(fn (TimeEntry $entry) => $this->employeeDateKey($entry->employee_id, $entry->entry_date?->toDateString())),
            'batch_projects_by_employee_date' => collect($data->entries)
                ->groupBy(fn (array $entry) => $this->employeeDateKey($entry['employee_id'], $this->dateKey($entry['entry_date'])))
                ->map(fn (Collection $entries) => $entries->pluck('project_id')->unique()->values()),
        ];
    }

    /**
     * @param  array<string, list<string>>  $errors
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $entry
     */
    private function validateCompanyConsistency(array &$errors, array $context, array $entry, int|string $index): void
    {
        $companyId = $entry['company_id'];
        $employee = $context['employees']->get($entry['employee_id']);
        $project = $context['projects']->get($entry['project_id']);
        $task = $context['tasks']->get($entry['task_id']);

        if ($employee instanceof Employee && ! $employee->companies->contains('id', $companyId)) {
            $this->addError($errors, "entries.{$index}.employee_id", 'The selected employee does not belong to the selected company.');
        }

        if ($project instanceof Project && $project->company_id !== $companyId) {
            $this->addError($errors, "entries.{$index}.project_id", 'The selected project does not belong to the selected company.');
        }

        if ($task instanceof Task && $task->company_id !== $companyId) {
            $this->addError($errors, "entries.{$index}.task_id", 'The selected task does not belong to the selected company.');
        }
    }

    /**
     * @param  array<string, list<string>>  $errors
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $entry
     */
    private function validateEmployeeProjectAssignment(array &$errors, array $context, array $entry, int|string $index): void
    {
        $companyId = $entry['company_id'];
        $employee = $context['employees']->get($entry['employee_id']);

        if (! $employee instanceof Employee) {
            return;
        }

        $isAssigned = $employee->projects->contains(
            fn (Project $project) => $project->id === $entry['project_id']
                && $project->pivot->company_id === $companyId
        );

        if (! $isAssigned) {
            $this->addError($errors, "entries.{$index}.project_id", 'The selected project is not assigned to the selected employee.');
        }
    }

    /**
     * @param  array<string, list<string>>  $errors
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $entry
     */
    private function validateBatchProjectConflict(array &$errors, array $context, array $entry, int|string $index): void
    {
        $projects = $context['batch_projects_by_employee_date']->get(
            $this->employeeDateKey($entry['employee_id'], $this->dateKey($entry['entry_date'])),
            collect()
        );

        if ($projects->count() > 1) {
            $this->addError($errors, "entries.{$index}.project_id", 'An employee can only work on one project per date.');
        }
    }

    /**
     * @param  array<string, list<string>>  $errors
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $entry
     */
    private function validateExistingProjectConflict(array &$errors, array $context, array $entry, int|string $index): void
    {
        $existingEntries = $context['existing_entries']->get(
            $this->employeeDateKey($entry['employee_id'], $this->dateKey($entry['entry_date'])),
            collect()
        );

        if ($existingEntries->contains(fn (TimeEntry $timeEntry) => $timeEntry->project_id !== $entry['project_id'])) {
            $this->addError($errors, "entries.{$index}.project_id", 'An employee already has a different project on this date.');
        }
    }

    /**
     * @param  array<string, list<string>>  $errors
     */
    private function addError(array &$errors, string $key, string $message): void
    {
        $errors[$key][] = $message;
    }

    private function employeeDateKey(string $employeeId, ?string $date): string
    {
        return "{$employeeId}:{$date}";
    }

    private function dateKey(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }
}
