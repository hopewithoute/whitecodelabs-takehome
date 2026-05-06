<?php

namespace App\Data;

use App\Models\Company;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class ProjectData extends Data
{
    public function __construct(
        public Company $company,
        public string $name,
    ) {}

    public static function rules(?ValidationContext $validationContext = null): array
    {
        return [
            'company' => ['required'],
            'name' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array{company_id: string, name: string}
     */
    public function toModelData(): array
    {
        return [
            'company_id' => $this->company->id,
            'name' => $this->name,
        ];
    }
}
