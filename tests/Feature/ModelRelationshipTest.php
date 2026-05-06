<?php

namespace Tests\Feature;

use App\Actions\CompanyEmployeeAttachAction;
use App\Actions\EmployeeProjectAssignAction;
use App\Data\CompanyEmployeeData;
use App\Data\EmployeeProjectData;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelRelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_employee_membership_relationships_work(): void
    {
        $company = Company::factory()->create();
        $employee = Employee::factory()->create();

        app(CompanyEmployeeAttachAction::class)->execute(new CompanyEmployeeData($company, $employee));

        $this->assertTrue($company->employees()->whereKey($employee)->exists());
        $this->assertTrue($employee->companies()->whereKey($company)->exists());
    }

    public function test_company_employee_relationship_supports_plain_attach(): void
    {
        $company = Company::factory()->create();
        $employee = Employee::factory()->create();

        $company->employees()->attach($employee);

        $this->assertTrue($company->employees()->whereKey($employee)->exists());
    }

    public function test_company_owns_projects_tasks_and_time_entries(): void
    {
        $company = Company::factory()->create();
        $employee = Employee::factory()->create();
        $project = Project::factory()->for($company)->create();
        $task = Task::factory()->for($company)->create();
        $timeEntry = TimeEntry::factory()->for($company)->for($employee)->for($project)->for($task)->create();

        $this->assertTrue($company->projects()->whereKey($project)->exists());
        $this->assertTrue($company->tasks()->whereKey($task)->exists());
        $this->assertTrue($company->timeEntries()->whereKey($timeEntry)->exists());
    }

    public function test_employee_project_assignment_relationships_include_company_context(): void
    {
        $company = Company::factory()->create();
        $employee = Employee::factory()->create();
        $project = Project::factory()->for($company)->create();

        app(EmployeeProjectAssignAction::class)->execute(new EmployeeProjectData($company, $employee, $project));

        $assignedProject = $employee->projects()->firstOrFail();
        $assignedEmployee = $project->employees()->firstOrFail();

        $this->assertTrue($assignedProject->is($project));
        $this->assertSame($company->id, $assignedProject->pivot->company_id);
        $this->assertTrue($assignedEmployee->is($employee));
        $this->assertSame($company->id, $assignedEmployee->pivot->company_id);
    }

    public function test_employee_project_relationship_supports_plain_attach_with_company_context(): void
    {
        $company = Company::factory()->create();
        $employee = Employee::factory()->create();
        $project = Project::factory()->for($company)->create();

        $employee->projects()->attach($project, ['company_id' => $company->id]);

        $this->assertTrue($employee->projects()->whereKey($project)->exists());
    }

    public function test_time_entry_belongs_to_required_records_and_appends_display_fields(): void
    {
        $company = Company::factory()->create();
        $employee = Employee::factory()->create();
        $project = Project::factory()->for($company)->create();
        $task = Task::factory()->for($company)->create();
        $timeEntry = TimeEntry::factory()
            ->for($company)
            ->for($employee)
            ->for($project)
            ->for($task)
            ->create([
                'entry_date' => '2026-01-15',
                'hours' => 3.5,
            ]);

        $this->assertTrue($timeEntry->company->is($company));
        $this->assertTrue($timeEntry->employee->is($employee));
        $this->assertTrue($timeEntry->project->is($project));
        $this->assertTrue($timeEntry->task->is($task));
        $this->assertSame('Jan 15, 2026', $timeEntry->entry_date_display);
        $this->assertSame('3.50', $timeEntry->hours_display);
        $this->assertArrayHasKey('entry_date_display', $timeEntry->toArray());
        $this->assertArrayHasKey('hours_display', $timeEntry->toArray());
    }

    public function test_time_entry_factory_keeps_generated_project_and_task_company_consistent(): void
    {
        $company = Company::factory()->create();

        $timeEntry = TimeEntry::factory()->for($company)->create();

        $this->assertSame($company->id, $timeEntry->project->company_id);
        $this->assertSame($company->id, $timeEntry->task->company_id);
    }
}
