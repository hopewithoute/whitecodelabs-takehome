<?php

namespace App\Ai;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

class TimeEntryDraftAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function provider(): string
    {
        return config('ai.agents.time_entry_drafts.provider', 'deepseek');
    }

    public function model(): ?string
    {
        return config('ai.agents.time_entry_drafts.model');
    }

    public function instructions(): string
    {
        $today = now()->toDateString();

        return <<<PROMPT
        You convert plain-English time entry notes into structured draft rows for a time entry spreadsheet.

            Today's date is {$today}.

            Return draft rows only as JSON matching the provided schema. Do not include explanations, markdown, or extra fields.

            Each row represents one task-level time entry with:
            - company
            - employee
            - project
            - task
            - entry_date
            - hours
            - warning

            Extraction rules:
            - Do not invent companies, employees, projects, or tasks.
            - Use only values mentioned in the user's text or strongly implied by the provided reference data.
            - If a value is unclear, unknown, missing, or ambiguous, leave the text field as an empty string and add a short warning.
            - Match names against the provided reference data when available.
            - Prefer exact matches. Use fuzzy matches only when highly confident.
            - If multiple matches are possible, leave the field empty and add a warning.
            - Preserve dates as ISO YYYY-MM-DD when possible.
            - If the user provides relative dates such as today, yesterday, or last Friday, resolve them using today's date ({$today}).
            - If the date cannot be resolved confidently, set entry_date to an empty string and add a warning.
            - Normalize hours into a number.
            - "4 hours" => 4
            - "30 minutes" => 0.5
            - "1 hour 30 minutes" => 1.5
            - If hours are missing or unclear, set hours to 0 and add a warning.
            - Multiple tasks for the same employee, project, and date must be returned as separate rows.
            - If the note appears to assign the same employee to different projects on the same date, still return the draft rows but add a warning.
            - Do not perform final validation. Backend validation remains the source of truth.

            Warning rules:
            - warning must be a single short string.
            - Use an empty string if there is no warning.
            - If there are multiple issues, combine them briefly using semicolons.
            - Examples:
            - "Date is unclear."
            - "Hours are missing."
            - "Employee name is ambiguous."
            - "Project not found in reference data."
            - "Same employee may have multiple projects on this date."

            Return an empty array if no time entry rows can be extracted.
        PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'entries' => $schema->array()
                ->items($schema->object([
                    'company' => $schema->string()->description('Matched company name, or empty string if missing or unclear.')->required(),
                    'employee' => $schema->string()->description('Matched employee name, or empty string if missing or unclear.')->required(),
                    'project' => $schema->string()->description('Matched project name, or empty string if missing or unclear.')->required(),
                    'task' => $schema->string()->description('Matched task name, or empty string if missing or unclear.')->required(),
                    'entry_date' => $schema->string()->description('ISO date YYYY-MM-DD, or empty string if missing or unclear.')->required(),
                    'hours' => $schema->number()->description('Hours for this task row, or 0 if missing or unclear.')->required(),
                    'warning' => $schema->string()->description('Short warning for uncertain parsing, or empty string if there is no warning.')->required(),
                ])->withoutAdditionalProperties()),
        ];
    }
}
