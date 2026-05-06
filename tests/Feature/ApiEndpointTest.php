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

class ApiEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_options_endpoint_returns_companies(): void
    {
        Company::factory()->create(['name' => 'Globex Services']);
        Company::factory()->create(['name' => 'Acme Operations']);

        $this->getJson('/api/v1/companies')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Acme Operations')
            ->assertJsonPath('data.1.name', 'Globex Services')
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'name',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ]);
    }

    public function test_company_employees_endpoint_returns_only_company_members(): void
    {
        [$company, $employee, $outsideEmployee] = $this->createCompanyWithEmployees();

        $this->getJson("/api/v1/companies/{$company->id}/employees")
            ->assertOk()
            ->assertJsonPath('data.0.id', $employee->id)
            ->assertJsonMissing(['id' => $outsideEmployee->id]);
    }

    public function test_company_projects_endpoint_returns_company_projects(): void
    {
        $company = Company::factory()->create();
        Project::factory()->for($company)->create(['name' => 'Client Portal']);
        Project::factory()->create(['name' => 'Outside Portal']);

        $this->getJson("/api/v1/companies/{$company->id}/projects")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Client Portal');
    }

    public function test_company_projects_endpoint_can_filter_by_employee_assignment(): void
    {
        $company = Company::factory()->create();
        $employee = Employee::factory()->create();
        $assignedProject = Project::factory()->for($company)->create(['name' => 'Assigned Project']);
        $unassignedProject = Project::factory()->for($company)->create(['name' => 'Unassigned Project']);

        app(EmployeeProjectAssignAction::class)->execute(new EmployeeProjectData(
            company: $company,
            employee: $employee,
            project: $assignedProject,
        ));

        $this->getJson("/api/v1/companies/{$company->id}/projects?filter[employee_id]={$employee->id}")
            ->assertOk()
            ->assertJsonPath('data.0.id', $assignedProject->id)
            ->assertJsonMissing(['id' => $unassignedProject->id]);
    }

    public function test_company_tasks_endpoint_returns_company_tasks(): void
    {
        $company = Company::factory()->create();
        Task::factory()->for($company)->create(['name' => 'Implementation']);
        Task::factory()->create(['name' => 'Outside Task']);

        $this->getJson("/api/v1/companies/{$company->id}/tasks")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Implementation');
    }

    public function test_time_entries_endpoint_returns_history_with_related_labels(): void
    {
        [$company, $employee] = $this->createCompanyWithEmployees();
        $project = Project::factory()->for($company)->create(['name' => 'Client Portal']);
        $task = Task::factory()->for($company)->create(['name' => 'Implementation']);

        $timeEntry = TimeEntry::factory()
            ->for($company)
            ->for($employee)
            ->for($project)
            ->for($task)
            ->create([
                'entry_date' => '2026-01-15',
                'hours' => '3.50',
            ]);

        $this->getJson('/api/v1/time-entries')
            ->assertOk()
            ->assertJsonPath('data.0.id', $timeEntry->id)
            ->assertJsonPath('data.0.company.name', $company->name)
            ->assertJsonPath('data.0.employee.name', $employee->name)
            ->assertJsonPath('data.0.project.name', $project->name)
            ->assertJsonPath('data.0.task.name', $task->name)
            ->assertJsonPath('data.0.entry_date', '2026-01-15')
            ->assertJsonPath('data.0.hours_display', '3.50');
    }

    public function test_time_entries_endpoint_can_filter_by_company(): void
    {
        [$company, $employee] = $this->createCompanyWithEmployees();
        $project = Project::factory()->for($company)->create();
        $task = Task::factory()->for($company)->create();
        $includedEntry = TimeEntry::factory()->for($company)->for($employee)->for($project)->for($task)->create();
        $outsideEntry = TimeEntry::factory()->create();

        $this->getJson("/api/v1/time-entries?filter[company_id]={$company->id}")
            ->assertOk()
            ->assertJsonPath('data.0.id', $includedEntry->id)
            ->assertJsonMissing(['id' => $outsideEntry->id]);
    }

    public function test_time_entries_endpoint_creates_a_valid_batch(): void
    {
        [$company, $employee, $project, $task] = $this->createAssignableTimeEntryGraph();

        $this->postJson('/api/v1/time-entries', [
            'entries' => [[
                'company_id' => $company->id,
                'employee_id' => $employee->id,
                'project_id' => $project->id,
                'task_id' => $task->id,
                'entry_date' => '2026-01-15',
                'hours' => '3.50',
            ]],
        ])
            ->assertCreated()
            ->assertJsonPath('data.0.company.name', $company->name)
            ->assertJsonPath('data.0.employee.name', $employee->name)
            ->assertJsonPath('data.0.project.name', $project->name)
            ->assertJsonPath('data.0.task.name', $task->name)
            ->assertJsonPath('data.0.hours_display', '3.50');

        $savedEntry = TimeEntry::query()->firstOrFail();

        $this->assertSame($company->id, $savedEntry->company_id);
        $this->assertSame($employee->id, $savedEntry->employee_id);
        $this->assertSame($project->id, $savedEntry->project_id);
        $this->assertSame($task->id, $savedEntry->task_id);
        $this->assertSame('2026-01-15', $savedEntry->entry_date->toDateString());
        $this->assertSame('3.50', $savedEntry->hours_display);
    }

    public function test_time_entries_endpoint_allows_multiple_tasks_for_same_project_and_date(): void
    {
        [$company, $employee, $project, $task] = $this->createAssignableTimeEntryGraph();
        $otherTask = Task::factory()->for($company)->create();

        $this->postJson('/api/v1/time-entries', [
            'entries' => [
                [
                    'company_id' => $company->id,
                    'employee_id' => $employee->id,
                    'project_id' => $project->id,
                    'task_id' => $task->id,
                    'entry_date' => '2026-01-15',
                    'hours' => '2.00',
                ],
                [
                    'company_id' => $company->id,
                    'employee_id' => $employee->id,
                    'project_id' => $project->id,
                    'task_id' => $otherTask->id,
                    'entry_date' => '2026-01-15',
                    'hours' => '1.50',
                ],
            ],
        ])->assertCreated();

        $this->assertSame(2, TimeEntry::query()->count());
    }

    public function test_time_entries_endpoint_rejects_invalid_company_relationships(): void
    {
        [$company, $employee, $project, $task] = $this->createAssignableTimeEntryGraph();
        $outsideEmployee = Employee::factory()->create();

        $this->postJson('/api/v1/time-entries', [
            'entries' => [[
                'company_id' => $company->id,
                'employee_id' => $outsideEmployee->id,
                'project_id' => $project->id,
                'task_id' => $task->id,
                'entry_date' => '2026-01-15',
                'hours' => '3.50',
            ]],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['entries.0.employee_id']);

        $this->assertDatabaseCount('time_entries', 0);
    }

    public function test_time_entries_endpoint_rejects_invalid_project_and_task_company(): void
    {
        [$company, $employee, $project, $task] = $this->createAssignableTimeEntryGraph();
        $outsideProject = Project::factory()->create();
        $outsideTask = Task::factory()->create();

        $this->postJson('/api/v1/time-entries', [
            'entries' => [[
                'company_id' => $company->id,
                'employee_id' => $employee->id,
                'project_id' => $outsideProject->id,
                'task_id' => $outsideTask->id,
                'entry_date' => '2026-01-15',
                'hours' => '3.50',
            ]],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'entries.0.project_id',
                'entries.0.task_id',
            ]);

        $this->assertDatabaseCount('time_entries', 0);
    }

    public function test_time_entries_endpoint_rejects_invalid_hours_and_date(): void
    {
        [$company, $employee, $project, $task] = $this->createAssignableTimeEntryGraph();

        $this->postJson('/api/v1/time-entries', [
            'entries' => [[
                'company_id' => $company->id,
                'employee_id' => $employee->id,
                'project_id' => $project->id,
                'task_id' => $task->id,
                'entry_date' => 'not-a-date',
                'hours' => '0',
            ]],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'entries.0.entry_date',
                'entries.0.hours',
            ]);

        $this->assertDatabaseCount('time_entries', 0);
    }

    public function test_time_entries_endpoint_rejects_unassigned_employee_project(): void
    {
        [$company, $employee, $project, $task] = $this->createAssignableTimeEntryGraph();
        $unassignedProject = Project::factory()->for($company)->create();

        $this->postJson('/api/v1/time-entries', [
            'entries' => [[
                'company_id' => $company->id,
                'employee_id' => $employee->id,
                'project_id' => $unassignedProject->id,
                'task_id' => $task->id,
                'entry_date' => '2026-01-15',
                'hours' => '3.50',
            ]],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['entries.0.project_id']);

        $this->assertDatabaseMissing('time_entries', [
            'project_id' => $project->id,
        ]);
    }

    public function test_time_entries_endpoint_rejects_project_conflicts_within_batch(): void
    {
        [$company, $employee, $project, $task] = $this->createAssignableTimeEntryGraph();
        $otherProject = Project::factory()->for($company)->create();
        $otherTask = Task::factory()->for($company)->create();

        app(EmployeeProjectAssignAction::class)->execute(new EmployeeProjectData(
            company: $company,
            employee: $employee,
            project: $otherProject,
        ));

        $this->postJson('/api/v1/time-entries', [
            'entries' => [
                [
                    'company_id' => $company->id,
                    'employee_id' => $employee->id,
                    'project_id' => $project->id,
                    'task_id' => $task->id,
                    'entry_date' => '2026-01-15',
                    'hours' => '2.00',
                ],
                [
                    'company_id' => $company->id,
                    'employee_id' => $employee->id,
                    'project_id' => $otherProject->id,
                    'task_id' => $otherTask->id,
                    'entry_date' => 'January 15, 2026',
                    'hours' => '1.50',
                ],
            ],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['entries.0.project_id', 'entries.1.project_id']);

        $this->assertDatabaseCount('time_entries', 0);
    }

    public function test_time_entries_endpoint_rejects_project_conflicts_with_existing_entries(): void
    {
        [$company, $employee, $project, $task] = $this->createAssignableTimeEntryGraph();
        $otherProject = Project::factory()->for($company)->create();
        $otherTask = Task::factory()->for($company)->create();

        app(EmployeeProjectAssignAction::class)->execute(new EmployeeProjectData(
            company: $company,
            employee: $employee,
            project: $otherProject,
        ));

        TimeEntry::factory()
            ->for($company)
            ->for($employee)
            ->for($project)
            ->for($task)
            ->create(['entry_date' => '2026-01-15']);

        $this->postJson('/api/v1/time-entries', [
            'entries' => [[
                'company_id' => $company->id,
                'employee_id' => $employee->id,
                'project_id' => $otherProject->id,
                'task_id' => $otherTask->id,
                'entry_date' => '2026-01-15',
                'hours' => '1.50',
            ]],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['entries.0.project_id']);

        $this->assertSame(1, TimeEntry::query()->count());
    }

    /**
     * @return array{0: Company, 1: Employee, 2: Employee}
     */
    private function createCompanyWithEmployees(): array
    {
        $company = Company::factory()->create();
        $employee = Employee::factory()->create(['name' => 'Ava Chen']);
        $outsideEmployee = Employee::factory()->create(['name' => 'Ben Ortiz']);

        app(CompanyEmployeeAttachAction::class)->execute(new CompanyEmployeeData(
            company: $company,
            employee: $employee,
        ));

        return [$company, $employee, $outsideEmployee];
    }

    /**
     * @return array{0: Company, 1: Employee, 2: Project, 3: Task}
     */
    private function createAssignableTimeEntryGraph(): array
    {
        [$company, $employee] = $this->createCompanyWithEmployees();
        $project = Project::factory()->for($company)->create();
        $task = Task::factory()->for($company)->create();

        app(EmployeeProjectAssignAction::class)->execute(new EmployeeProjectData(
            company: $company,
            employee: $employee,
            project: $project,
        ));

        return [$company, $employee, $project, $task];
    }
}
