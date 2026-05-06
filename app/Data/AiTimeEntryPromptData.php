<?php

namespace App\Data;

use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class AiTimeEntryPromptData extends Data
{
    public function __construct(
        public string $prompt,
        public ?string $company_id = null,
    ) {}

    public static function rules(?ValidationContext $validationContext = null): array
    {
        return [
            'prompt' => ['required', 'string', 'min:3', 'max:10000'],
            'company_id' => ['nullable', 'string', Rule::exists('companies', 'id')],
        ];
    }

    public static function messages(): array
    {
        return [
            'prompt.required' => 'Describe the time entries to draft.',
            'prompt.min' => 'Describe the time entries with a little more detail.',
            'prompt.max' => 'Keep the time entry request shorter.',
            'company_id.exists' => 'Choose a valid company scope.',
        ];
    }
}
