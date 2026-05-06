<?php

namespace App\Data;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Task;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class TimeEntryData extends Data
{
    public function __construct(
        public Company $company,
        public Employee $employee,
        public Project $project,
        public Task $task,
        public string $entry_date,
        public string $hours,
    ) {}

    public static function rules(?ValidationContext $validationContext = null): array
    {
        return [
            'company' => ['required'],
            'employee' => ['required'],
            'project' => ['required'],
            'task' => ['required'],
            'entry_date' => ['required', 'date'],
            'hours' => ['required', 'numeric'],
        ];
    }

    /**
     * @return array{company_id: string, employee_id: string, project_id: string, task_id: string, entry_date: string, hours: string}
     */
    public function toModelData(): array
    {
        return [
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'project_id' => $this->project->id,
            'task_id' => $this->task->id,
            'entry_date' => $this->entry_date,
            'hours' => $this->hours,
        ];
    }
}
