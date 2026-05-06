<?php

namespace App\Actions\TimeEntries;

use App\Ai\TimeEntryDraftAgent;
use App\Data\AiTimeEntryPromptData;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

readonly class ParseTimeEntryDraftsAction
{
    public function execute(AiTimeEntryPromptData $data): array
    {
        $companies = $this->loadCompanies($data->company_id);
        $response = (new TimeEntryDraftAgent)->prompt($this->buildPrompt($data, $companies));

        return [
            'entries' => collect($response['entries'] ?? [])
                ->map(fn (array $entry): array => $this->resolveEntry($entry, $data, $companies))
                ->values()
                ->all(),
            'mode' => 'draft',
        ];
    }

    /**
     * @return Collection<int, Company>
     */
    private function loadCompanies(?string $companyId = null): Collection
    {
        $query = Company::query()
            ->with([
                'employees' => fn ($q) => $q->orderBy('name'),
                'employees.projects' => fn ($q) => $q->select(['projects.id'])->withPivot('company_id'),
                'projects' => fn ($q) => $q->orderBy('name'),
                'tasks' => fn ($q) => $q->orderBy('name'),
            ])
            ->orderBy('name');

        if ($companyId !== null) {
            $query->where('id', $companyId);
        }

        return $query->get();
    }

    /**
     * @param  Collection<int, Company>  $companies
     */
    private function buildPrompt(AiTimeEntryPromptData $data, Collection $companies): string
    {
        $reference = $companies
            ->map(fn (Company $company): array => [
                'company' => $company->name,
                'employees' => $company->employees->pluck('name')->values()->all(),
                'projects' => $company->projects->pluck('name')->values()->all(),
                'tasks' => $company->tasks->pluck('name')->values()->all(),
            ])
            ->values()
            ->all();

        return json_encode([
            'user_text' => $data->prompt,
            'selected_company_id' => $data->company_id,
            'reference_data' => $reference,
        ], JSON_PRETTY_PRINT) ?: $data->prompt;
    }

    /**
     * @param  array<string, mixed>  $entry
     * @param  Collection<int, Company>  $companies
     * @return array<string, mixed>
     */
    private function resolveEntry(array $entry, AiTimeEntryPromptData $data, Collection $companies): array
    {
        $company = $this->resolveCompany($entry, $data, $companies);
        $employee = $this->resolveRelated($company?->employees, $entry['employee'] ?? null);
        $project = $this->resolveRelated($company?->projects, $entry['project'] ?? null);
        $task = $this->resolveRelated($company?->tasks, $entry['task'] ?? null);
        $entryDate = $this->parseDate($entry['entry_date'] ?? null);

        return [
            'company_id' => $company?->id,
            'company_name' => $company?->name ?? $this->text($entry['company'] ?? null),
            'employee_id' => $employee?->id,
            'employee_name' => $employee?->name ?? $this->text($entry['employee'] ?? null),
            'project_id' => $project?->id,
            'project_name' => $project?->name ?? $this->text($entry['project'] ?? null),
            'task_id' => $task?->id,
            'task_name' => $task?->name ?? $this->text($entry['task'] ?? null),
            'entry_date' => $entryDate,
            'hours' => $entry['hours'] ?? null,
            'warnings' => $this->collectWarnings($entry, $company, $employee, $project, $task),
            'field_warnings' => $this->collectFieldWarnings($entry, $company, $employee, $project, $task, $entryDate),
        ];
    }

    /**
     * @param  array<string, mixed>  $entry
     * @param  Collection<int, Company>  $companies
     */
    private function resolveCompany(array $entry, AiTimeEntryPromptData $data, Collection $companies): ?Company
    {
        if ($data->company_id !== null) {
            return $companies->firstWhere('id', $data->company_id);
        }

        return $this->resolveRelated($companies, $entry['company'] ?? null);
    }

    /**
     * @param  Collection<int, mixed>|null  $records
     */
    private function resolveRelated(?Collection $records, mixed $name): mixed
    {
        $normalized = $this->normalize($name);

        if ($normalized === '' || $records === null) {
            return null;
        }

        $matches = $records->filter(
            fn (mixed $record): bool => $this->normalize($record->name ?? null) === $normalized,
        );

        return $matches->count() === 1 ? $matches->first() : null;
    }

    /**
     * @return array<int, string>
     */
    private function collectWarnings(
        array $entry,
        ?Company $company,
        mixed $employee,
        mixed $project,
        mixed $task,
    ): array {
        $warnings = collect();

        if (! empty($entry['warning'] ?? null)) {
            $warnings->push($entry['warning']);
        }

        if (! $company instanceof Company) {
            $warnings->push('Choose a company before saving this row.');
        }

        if (! $employee instanceof Employee) {
            $warnings->push('Choose a matching employee.');
        }

        if (! $project instanceof Project) {
            $warnings->push('Choose a matching project.');
        }

        if (! $task instanceof Task) {
            $warnings->push('Choose a matching task.');
        }

        if (
            $employee instanceof Employee
            && $project instanceof Project
            && $company instanceof Company
            && ! $this->employeeIsAssignedToProject($employee, $project, $company)
        ) {
            $warnings->push('The matched employee is not assigned to the matched project.');
        }

        return $warnings->unique()->values()->all();
    }

    /**
     * @return array<string, list<string>>
     */
    private function collectFieldWarnings(
        array $entry,
        ?Company $company,
        mixed $employee,
        mixed $project,
        mixed $task,
        ?string $entryDate,
    ): array {
        $warnings = [];

        if (! empty($entry['warning'] ?? null)) {
            $this->addFieldWarning($warnings, $this->warningField($entry['warning']), $entry['warning']);
        }

        if (! $company instanceof Company) {
            $this->addFieldWarning($warnings, 'company_id', 'Choose a company before saving this row.');
        }

        if (! $employee instanceof Employee) {
            $this->addFieldWarning($warnings, 'employee_id', 'Choose a matching employee.');
        }

        if (! $project instanceof Project) {
            $this->addFieldWarning($warnings, 'project_id', 'Choose a matching project.');
        }

        if (! $task instanceof Task) {
            $this->addFieldWarning($warnings, 'task_id', 'Choose a matching task.');
        }

        if ($this->text($entry['entry_date'] ?? null) !== '' && $entryDate === null) {
            $this->addFieldWarning($warnings, 'entry_date', 'Choose a matching date.');
        }

        if (($entry['hours'] ?? null) === null || $entry['hours'] === 0) {
            $this->addFieldWarning($warnings, 'hours', 'Check the drafted hours.');
        }

        if (
            $employee instanceof Employee
            && $project instanceof Project
            && $company instanceof Company
            && ! $this->employeeIsAssignedToProject($employee, $project, $company)
        ) {
            $this->addFieldWarning($warnings, 'project_id', 'The matched employee is not assigned to the matched project.');
        }

        return collect($warnings)
            ->map(fn (array $messages): array => collect($messages)->unique()->values()->all())
            ->all();
    }

    /**
     * @param  array<string, list<string>>  $warnings
     */
    private function addFieldWarning(array &$warnings, string $field, string $message): void
    {
        $warnings[$field][] = $message;
    }

    private function warningField(mixed $warning): string
    {
        $warning = $this->normalize($warning);

        return match (true) {
            str_contains($warning, 'company') => 'company_id',
            str_contains($warning, 'employee') => 'employee_id',
            str_contains($warning, 'project') => 'project_id',
            str_contains($warning, 'task') => 'task_id',
            str_contains($warning, 'date') => 'entry_date',
            str_contains($warning, 'hour') || str_contains($warning, 'minute') => 'hours',
            default => 'task_id',
        };
    }

    private function employeeIsAssignedToProject(Employee $employee, Project $project, Company $company): bool
    {
        return $employee->projects->contains(
            fn (Project $assigned): bool => $assigned->id === $project->id
                && $assigned->pivot->company_id === $company->id,
        );
    }

    private function parseDate(mixed $value): ?string
    {
        $text = $this->text($value);

        if ($text === '') {
            return null;
        }

        try {
            return Carbon::parse($text)->toDateString();
        } catch (\InvalidArgumentException) {
            return null;
        }
    }

    private function normalize(mixed $value): string
    {
        return Str::of($this->text($value))->lower()->squish()->toString();
    }

    private function text(mixed $value): string
    {
        return $value === null ? '' : Str::of($value)->trim()->toString();
    }
}
