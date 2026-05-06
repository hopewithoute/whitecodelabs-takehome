<?php

namespace App\Data;

use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class TimeEntryUpdateData extends Data
{
    public function __construct(
        public string $company_id,
        public string $employee_id,
        public string $project_id,
        public string $task_id,
        public string $entry_date,
        public string $hours,
    ) {}

    public static function rules(?ValidationContext $validationContext = null): array
    {
        return [
            'company_id' => ['required', 'string', Rule::exists('companies', 'id')],
            'employee_id' => ['required', 'string', Rule::exists('employees', 'id')],
            'project_id' => ['required', 'string', Rule::exists('projects', 'id')],
            'task_id' => ['required', 'string', Rule::exists('tasks', 'id')],
            'entry_date' => ['required', 'date'],
            'hours' => ['required', 'numeric', 'gt:0'],
        ];
    }

    public static function messages(): array
    {
        return [
            'company_id.required' => 'Choose a company.',
            'company_id.exists' => 'Choose a valid company.',
            'employee_id.required' => 'Choose an employee.',
            'employee_id.exists' => 'Choose a valid employee.',
            'project_id.required' => 'Choose a project.',
            'project_id.exists' => 'Choose a valid project.',
            'task_id.required' => 'Choose a task.',
            'task_id.exists' => 'Choose a valid task.',
            'entry_date.required' => 'Choose a date.',
            'entry_date.date' => 'Choose a valid date.',
            'hours.required' => 'Enter hours.',
            'hours.numeric' => 'Enter hours as a number.',
            'hours.gt' => 'Enter hours greater than 0.',
        ];
    }

    /**
     * @return array{company_id: string, employee_id: string, project_id: string, task_id: string, entry_date: string, hours: string}
     */
    public function toBatchEntry(): array
    {
        return [
            'company_id' => $this->company_id,
            'employee_id' => $this->employee_id,
            'project_id' => $this->project_id,
            'task_id' => $this->task_id,
            'entry_date' => $this->entry_date,
            'hours' => $this->hours,
        ];
    }
}
