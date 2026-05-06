<?php

namespace App\Data;

use App\Models\Company;
use App\Models\Employee;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class CompanyEmployeeData extends Data
{
    public function __construct(
        public Company $company,
        public Employee $employee,
    ) {}

    public static function rules(?ValidationContext $validationContext = null): array
    {
        return [
            'company' => ['required'],
            'employee' => ['required'],
        ];
    }
}
