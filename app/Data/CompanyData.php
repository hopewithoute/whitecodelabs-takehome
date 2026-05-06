<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class CompanyData extends Data
{
    public function __construct(
        public string $name,
    ) {}

    public static function rules(?ValidationContext $validationContext = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array{name: string}
     */
    public function toModelData(): array
    {
        return [
            'name' => $this->name,
        ];
    }
}
