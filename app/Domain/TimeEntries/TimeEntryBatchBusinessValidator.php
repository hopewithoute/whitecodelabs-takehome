<?php

namespace App\Domain\TimeEntries;

use App\Data\TimeEntryBatchData;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Throwable;

class TimeEntryBatchBusinessValidator
{
    /**
     * @param  list<string>  $ignoredTimeEntryIds
     *
     * @throws ValidationException
     */
    public function validate(TimeEntryBatchData $data, array $ignoredTimeEntryIds = []): void
    {
        $context = $this->buildContext($data, $ignoredTimeEntryIds);
        $errors = [];

        foreach ($data->entries as $index => $entry) {
            $this->validateCompanyConsistency($errors, $context, $entry, $index);
            $this->validateEmployeeProjectAssignment($errors, $context, $entry, $index);
            $this->validateBatchProjectConflict($errors, $context, $entry, $index);
            $this->validateExistingProjectConflict($errors, $context, $entry, $index);
            $this->validateDuplicateTaskEntry($errors, $context, $entry, $index);
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @param  list<string>  $ignoredTimeEntryIds
     * @return array{
     *     employees: EloquentCollection<int, Employee>,
     *     projects: EloquentCollection<int, Project>,
     *     tasks: EloquentCollection<int, Task>,
     *     existing_entries: Collection<string, Collection<int, TimeEntry>>,
     *     existing_entries_by_task_key: Collection<string, Collection<int, TimeEntry>>,
     *     batch_projects_by_employee_date: Collection<string, Collection<int, string>>,
     *     duplicate_batch_task_keys: Collection<int, string>
     * }
     */
    private function buildContext(TimeEntryBatchData $data, array $ignoredTimeEntryIds = []): array
    {
        $companyIds = collect($data->entries)->pluck('company_id')->unique()->values();
        $employeeIds = collect($data->entries)->pluck('employee_id')->unique()->values();
        $projectIds = collect($data->entries)->pluck('project_id')->unique()->values();
        $taskIds = collect($data->entries)->pluck('task_id')->unique()->values();
        $entryDates = collect($data->entries)
            ->pluck('entry_date')
            ->map(fn (mixed $date) => $this->dateKey($date))
            ->filter()
            ->unique()
            ->values();
        $existingEntries = TimeEntry::query()
            ->whereIn('company_id', $companyIds)
            ->whereIn('employee_id', $employeeIds)
            ->when($ignoredTimeEntryIds !== [], fn ($query) => $query->whereNotIn('id', $ignoredTimeEntryIds))
            ->where(function ($query) use ($entryDates): void {
                foreach ($entryDates as $entryDate) {
                    $query->orWhereDate('entry_date', $entryDate);
                }
            })
            ->get(['id', 'company_id', 'employee_id', 'project_id', 'task_id', 'entry_date']);

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
            'existing_entries' => $existingEntries
                ->groupBy(fn (TimeEntry $entry) => $this->employeeDateKey(
                    $entry->company_id,
                    $entry->employee_id,
                    $this->dateKey($entry->entry_date),
                )),
            'existing_entries_by_task_key' => $existingEntries
                ->groupBy(fn (TimeEntry $entry) => $this->timeEntryTaskKey(
                    $entry->company_id,
                    $entry->employee_id,
                    $entry->project_id,
                    $entry->task_id,
                    $this->dateKey($entry->entry_date),
                )),
            'batch_projects_by_employee_date' => collect($data->entries)
                ->groupBy(fn (array $entry) => $this->employeeDateKey(
                    $entry['company_id'],
                    $entry['employee_id'],
                    $this->dateKey($entry['entry_date']),
                ))
                ->map(fn (Collection $entries) => $entries->pluck('project_id')->unique()->values()),
            'duplicate_batch_task_keys' => collect($data->entries)
                ->groupBy(fn (array $entry) => $this->timeEntryTaskKey(
                    $entry['company_id'],
                    $entry['employee_id'],
                    $entry['project_id'],
                    $entry['task_id'],
                    $this->dateKey($entry['entry_date']),
                ))
                ->filter(fn (Collection $entries) => $entries->count() > 1)
                ->keys(),
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
            $this->employeeDateKey($entry['company_id'], $entry['employee_id'], $this->dateKey($entry['entry_date'])),
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
            $this->employeeDateKey($entry['company_id'], $entry['employee_id'], $this->dateKey($entry['entry_date'])),
            collect()
        );

        if ($existingEntries->contains(fn (TimeEntry $timeEntry) => $timeEntry->project_id !== $entry['project_id'])) {
            $this->addError($errors, "entries.{$index}.project_id", 'An employee already has a different project on this date.');
        }
    }

    /**
     * @param  array<string, list<string>>  $errors
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $entry
     */
    private function validateDuplicateTaskEntry(array &$errors, array $context, array $entry, int|string $index): void
    {
        $taskKey = $this->timeEntryTaskKey(
            $entry['company_id'],
            $entry['employee_id'],
            $entry['project_id'],
            $entry['task_id'],
            $this->dateKey($entry['entry_date']),
        );

        if ($context['duplicate_batch_task_keys']->contains($taskKey)) {
            $this->addError($errors, "entries.{$index}.task_id", 'This task is already listed for the selected employee, project, and date.');
        }

        if ($context['existing_entries_by_task_key']->has($taskKey)) {
            $this->addError($errors, "entries.{$index}.task_id", 'This task already exists for the selected employee, project, and date.');
        }
    }

    /**
     * @param  array<string, list<string>>  $errors
     */
    private function addError(array &$errors, string $key, string $message): void
    {
        $errors[$key][] = $message;
    }

    private function employeeDateKey(string $companyId, string $employeeId, ?string $date): string
    {
        return "{$companyId}:{$employeeId}:{$date}";
    }

    private function timeEntryTaskKey(string $companyId, string $employeeId, string $projectId, string $taskId, ?string $date): string
    {
        return "{$companyId}:{$employeeId}:{$projectId}:{$taskId}:{$date}";
    }

    private function dateKey(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value)->toDateString();
        }

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
