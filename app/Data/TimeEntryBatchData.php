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
}
