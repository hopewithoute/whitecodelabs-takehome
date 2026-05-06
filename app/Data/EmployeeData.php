<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class EmployeeData extends Data
{
    public function __construct(
        public string $name,
        public ?string $email = null,
    ) {}

    public static function rules(?ValidationContext $validationContext = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
        ];
    }

    /**
     * @return array{name: string, email: string|null}
     */
    public function toModelData(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
        ];
    }
}
