<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TimeEntry>
 */
class TimeEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'entry_date' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'hours' => fake()->randomFloat(2, 0.25, 8),
        ];
    }

    /**
     * Configure generated project and task records to match the time entry company.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (TimeEntry $timeEntry): void {
            $company = $this->resolveCompany($timeEntry);

            $timeEntry->company_id = $company->id;

            if ($timeEntry->project_id === null) {
                $timeEntry->project()->associate(Project::factory()->for($company)->create());
            }

            if ($timeEntry->task_id === null) {
                $timeEntry->task()->associate(Task::factory()->for($company)->create());
            }
        });
    }

    private function resolveCompany(TimeEntry $timeEntry): Company
    {
        if ($timeEntry->project_id !== null) {
            return Project::query()->findOrFail($timeEntry->project_id)->company;
        }

        if ($timeEntry->task_id !== null) {
            return Task::query()->findOrFail($timeEntry->task_id)->company;
        }

        if ($timeEntry->company_id !== null) {
            return Company::query()->findOrFail($timeEntry->company_id);
        }

        return Company::factory()->create();
    }
}
