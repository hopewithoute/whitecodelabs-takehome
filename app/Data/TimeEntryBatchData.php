<?php

namespace App\Data;

use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class TimeEntryBatchData extends Data
{
    /**
     * @param  list<array<string, mixed>>  $entries
     */
    public function __construct(
        public array $entries,
    ) {}

    public static function rules(?ValidationContext $validationContext = null): array
    {
        return [
            'entries' => ['required', 'array', 'min:1'],
            'entries.*.company_id' => ['required', 'string', Rule::exists('companies', 'id')],
            'entries.*.employee_id' => ['required', 'string', Rule::exists('employees', 'id')],
            'entries.*.project_id' => ['required', 'string', Rule::exists('projects', 'id')],
            'entries.*.task_id' => ['required', 'string', Rule::exists('tasks', 'id')],
            'entries.*.entry_date' => ['required', 'date'],
            'entries.*.hours' => ['required', 'numeric', 'gt:0'],
        ];
    }

    public static function messages(): array
    {
        return [
            'entries.required' => 'Add at least one time entry.',
            'entries.array' => 'Submit time entries as a list.',
            'entries.min' => 'Add at least one time entry.',
            'entries.*.company_id.required' => 'Choose a company.',
            'entries.*.company_id.exists' => 'Choose a valid company.',
            'entries.*.employee_id.required' => 'Choose an employee.',
            'entries.*.employee_id.exists' => 'Choose a valid employee.',
            'entries.*.project_id.required' => 'Choose a project.',
            'entries.*.project_id.exists' => 'Choose a valid project.',
            'entries.*.task_id.required' => 'Choose a task.',
            'entries.*.task_id.exists' => 'Choose a valid task.',
            'entries.*.entry_date.required' => 'Choose a date.',
            'entries.*.entry_date.date' => 'Choose a valid date.',
            'entries.*.hours.required' => 'Enter hours.',
            'entries.*.hours.numeric' => 'Enter hours as a number.',
            'entries.*.hours.gt' => 'Enter hours greater than 0.',
        ];
    }
}
