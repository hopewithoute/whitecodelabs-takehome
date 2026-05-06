<?php

namespace App\Data;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Project;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class EmployeeProjectData extends Data
{
    public function __construct(
        public Company $company,
        public Employee $employee,
        public Project $project,
    ) {}

    public static function rules(?ValidationContext $validationContext = null): array
    {
        return [
            'company' => ['required'],
            'employee' => ['required'],
            'project' => ['required'],
        ];
    }

    /**
     * @return array{company_id: string}
     */
    public function toPivotData(): array
    {
        return [
            'company_id' => $this->company->id,
        ];
    }
}
