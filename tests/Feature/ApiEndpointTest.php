<?php

namespace Tests\Feature;

use App\Actions\CompanyCreateAction;
use App\Actions\CompanyEmployeeAttachAction;
use App\Actions\EmployeeProjectAssignAction;
use App\Actions\ProjectCreateAction;
use App\Actions\TaskCreateAction;
use App\Ai\TimeEntryDraftAgent;
use App\Data\CompanyData;
use App\Data\CompanyEmployeeData;
use App\Data\EmployeeProjectData;
use App\Data\ProjectData;
use App\Data\TaskData;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Cache;
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

    public function test_reference_option_cache_is_invalidated_by_write_actions(): void
    {
        Cache::flush();

        $company = Company::factory()->create();
        Task::factory()->for($company)->create(['name' => 'Implementation']);

        $this->getJson("/api/v1/companies/{$company->id}/tasks")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Implementation');

        Task::factory()->for($company)->create(['name' => 'Direct Write']);

        $this->getJson("/api/v1/companies/{$company->id}/tasks")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonMissing(['name' => 'Direct Write']);

        app(TaskCreateAction::class)->execute(new TaskData($company, 'Action Write'));

        $this->getJson("/api/v1/companies/{$company->id}/tasks")
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.name', 'Action Write')
            ->assertJsonPath('data.1.name', 'Direct Write')
            ->assertJsonPath('data.2.name', 'Implementation');
    }

    public function test_company_option_cache_is_invalidated_by_company_create_action(): void
    {
        Cache::flush();

        Company::factory()->create(['name' => 'Acme Operations']);

        $this->getJson('/api/v1/companies')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Acme Operations');

        Company::factory()->create(['name' => 'Direct Write']);

        $this->getJson('/api/v1/companies')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonMissing(['name' => 'Direct Write']);

        app(CompanyCreateAction::class)->execute(new CompanyData('Action Write'));

        $this->getJson('/api/v1/companies')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.name', 'Acme Operations')
            ->assertJsonPath('data.1.name', 'Action Write')
            ->assertJsonPath('data.2.name', 'Direct Write');
    }

    public function test_company_employee_option_cache_is_invalidated_by_attach_action(): void
    {
        Cache::flush();

        $company = Company::factory()->create();
        $employee = Employee::factory()->create(['name' => 'Ava Chen']);
        $company->employees()->attach($employee);

        $this->getJson("/api/v1/companies/{$company->id}/employees")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Ava Chen');

        $directEmployee = Employee::factory()->create(['name' => 'Ben Carter']);
        $company->employees()->attach($directEmployee);

        $this->getJson("/api/v1/companies/{$company->id}/employees")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonMissing(['name' => 'Ben Carter']);

        $actionEmployee = Employee::factory()->create(['name' => 'Cora Diaz']);
        app(CompanyEmployeeAttachAction::class)->execute(new CompanyEmployeeData($company, $actionEmployee));

        $this->getJson("/api/v1/companies/{$company->id}/employees")
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.name', 'Ava Chen')
            ->assertJsonPath('data.1.name', 'Ben Carter')
            ->assertJsonPath('data.2.name', 'Cora Diaz');
    }

    public function test_company_project_option_cache_is_invalidated_by_project_create_action(): void
    {
        Cache::flush();

        $company = Company::factory()->create();
        Project::factory()->for($company)->create(['name' => 'Client Portal']);

        $this->getJson("/api/v1/companies/{$company->id}/projects")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Client Portal');

        Project::factory()->for($company)->create(['name' => 'Direct Write']);

        $this->getJson("/api/v1/companies/{$company->id}/projects")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonMissing(['name' => 'Direct Write']);

        app(ProjectCreateAction::class)->execute(new ProjectData($company, 'Action Write'));

        $this->getJson("/api/v1/companies/{$company->id}/projects")
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.name', 'Action Write')
            ->assertJsonPath('data.1.name', 'Client Portal')
            ->assertJsonPath('data.2.name', 'Direct Write');
    }

    public function test_employee_filtered_project_cache_is_invalidated_by_assignment_action(): void
    {
        Cache::flush();

        $company = Company::factory()->create();
        $employee = Employee::factory()->create();
        $assignedProject = Project::factory()->for($company)->create(['name' => 'Assigned Project']);
        $directProject = Project::factory()->for($company)->create(['name' => 'Direct Assignment']);
        $actionProject = Project::factory()->for($company)->create(['name' => 'Action Assignment']);

        app(EmployeeProjectAssignAction::class)->execute(new EmployeeProjectData(
            company: $company,
            employee: $employee,
            project: $assignedProject,
        ));

        $this->getJson("/api/v1/companies/{$company->id}/projects?filter[employee_id]={$employee->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Assigned Project');

        $employee->projects()->attach($directProject, ['company_id' => $company->id]);

        $this->getJson("/api/v1/companies/{$company->id}/projects?filter[employee_id]={$employee->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonMissing(['name' => 'Direct Assignment']);

        app(EmployeeProjectAssignAction::class)->execute(new EmployeeProjectData(
            company: $company,
            employee: $employee,
            project: $actionProject,
        ));

        $this->getJson("/api/v1/companies/{$company->id}/projects?filter[employee_id]={$employee->id}")
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.name', 'Action Assignment')
            ->assertJsonPath('data.1.name', 'Assigned Project')
            ->assertJsonPath('data.2.name', 'Direct Assignment');
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

    public function test_time_entries_endpoint_paginates_history(): void
    {
        $company = Company::factory()->create();
        $employee = Employee::factory()->create();
        $project = Project::factory()->for($company)->create();
        $task = Task::factory()->for($company)->create();

        foreach (range(1, 12) as $day) {
            TimeEntry::factory()
                ->for($company)
                ->for($employee)
                ->for($project)
                ->for($task)
                ->create([
                    'entry_date' => sprintf('2026-01-%02d', $day),
                    'hours' => '1.00',
                ]);
        }

        $response = $this->getJson('/api/v1/time-entries?per_page=5&page=2')
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('data.0.entry_date', '2026-01-07')
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.last_page', 3)
            ->assertJsonPath('meta.per_page', 5)
            ->assertJsonPath('meta.total', 12)
            ->assertJsonPath('meta.summary.total_hours', '12.00')
            ->assertJsonPath('meta.summary.by_company.0.total_hours', '12.00')
            ->assertJsonPath('meta.summary.by_employee.0.total_hours', '12.00')
            ->assertJsonPath('meta.summary.by_project.0.total_hours', '12.00')
            ->assertJsonPath('meta.summary.by_task.0.total_hours', '12.00')
            ->assertJsonCount(12, 'meta.summary.by_date');

        $this->assertNotNull($response->json('links.prev'));
        $this->assertNotNull($response->json('links.next'));
    }

    public function test_time_entries_endpoint_returns_correct_history_summary_totals(): void
    {
        $acme = Company::factory()->create(['name' => 'Acme Operations']);
        $globex = Company::factory()->create(['name' => 'Globex Services']);
        $ava = Employee::factory()->create(['name' => 'Ava Chen']);
        $ben = Employee::factory()->create(['name' => 'Ben Carter']);
        $cora = Employee::factory()->create(['name' => 'Cora Diaz']);
        $alpha = Project::factory()->for($acme)->create(['name' => 'Alpha']);
        $beta = Project::factory()->for($acme)->create(['name' => 'Beta']);
        $gamma = Project::factory()->for($globex)->create(['name' => 'Gamma']);
        $design = Task::factory()->for($acme)->create(['name' => 'Design']);
        $build = Task::factory()->for($acme)->create(['name' => 'Build']);
        $support = Task::factory()->for($globex)->create(['name' => 'Support']);

        TimeEntry::factory()->for($acme)->for($ava)->for($alpha)->for($design)->create([
            'entry_date' => '2026-01-01',
            'hours' => '2.00',
        ]);
        TimeEntry::factory()->for($acme)->for($ava)->for($alpha)->for($build)->create([
            'entry_date' => '2026-01-01',
            'hours' => '3.50',
        ]);
        TimeEntry::factory()->for($acme)->for($ben)->for($beta)->for($design)->create([
            'entry_date' => '2026-01-02',
            'hours' => '1.25',
        ]);
        TimeEntry::factory()->for($globex)->for($cora)->for($gamma)->for($support)->create([
            'entry_date' => '2026-01-01',
            'hours' => '4.00',
        ]);

        $summary = $this->getJson('/api/v1/time-entries?per_page=2')
            ->assertOk()
            ->assertJsonPath('meta.summary.total_hours', '10.75')
            ->json('meta.summary');

        $this->assertSame([
            ['id' => $acme->id, 'name' => 'Acme Operations', 'total_hours' => '6.75'],
            ['id' => $globex->id, 'name' => 'Globex Services', 'total_hours' => '4.00'],
        ], $summary['by_company']);
        $this->assertSame([
            ['id' => $ava->id, 'name' => 'Ava Chen', 'total_hours' => '5.50'],
            ['id' => $cora->id, 'name' => 'Cora Diaz', 'total_hours' => '4.00'],
            ['id' => $ben->id, 'name' => 'Ben Carter', 'total_hours' => '1.25'],
        ], $summary['by_employee']);
        $this->assertSame([
            ['id' => $alpha->id, 'name' => 'Alpha', 'total_hours' => '5.50'],
            ['id' => $gamma->id, 'name' => 'Gamma', 'total_hours' => '4.00'],
            ['id' => $beta->id, 'name' => 'Beta', 'total_hours' => '1.25'],
        ], $summary['by_project']);
        $this->assertSame([
            ['id' => $support->id, 'name' => 'Support', 'total_hours' => '4.00'],
            ['id' => $build->id, 'name' => 'Build', 'total_hours' => '3.50'],
            ['id' => $design->id, 'name' => 'Design', 'total_hours' => '3.25'],
        ], $summary['by_task']);
        $this->assertSame([
            ['date' => '2026-01-02', 'total_hours' => '1.25'],
            ['date' => '2026-01-01', 'total_hours' => '9.50'],
        ], $summary['by_date']);

        $this->getJson("/api/v1/time-entries?filter[company_id]={$acme->id}&per_page=1")
            ->assertOk()
            ->assertJsonPath('meta.summary.total_hours', '6.75')
            ->assertJsonCount(1, 'meta.summary.by_company')
            ->assertJsonPath('meta.summary.by_company.0.id', $acme->id)
            ->assertJsonPath('meta.summary.by_date.1.total_hours', '5.50');
    }

    public function test_time_entries_endpoint_can_search_history_by_related_labels(): void
    {
        [$company, $employee] = $this->createCompanyWithEmployees();
        $project = Project::factory()->for($company)->create(['name' => 'Client Portal']);
        $task = Task::factory()->for($company)->create(['name' => 'Implementation']);
        $includedEntry = TimeEntry::factory()
            ->for($company)
            ->for($employee)
            ->for($project)
            ->for($task)
            ->create();

        TimeEntry::factory()->create();

        $this->getJson('/api/v1/time-entries?filter[search]=Client')
            ->assertOk()
            ->assertJsonPath('data.0.id', $includedEntry->id)
            ->assertJsonCount(1, 'data');
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

    public function test_ai_time_entry_draft_endpoint_returns_spreadsheet_ready_rows(): void
    {
        config()->set('ai.agents.time_entry_drafts.provider', 'deepseek');
        config()->set('ai.agents.time_entry_drafts.model', 'any-local-model');

        [$company, $employee, $project, $task] = $this->createAssignableTimeEntryGraph();

        TimeEntryDraftAgent::fake([[
            'entries' => [[
                'company' => '',
                'employee' => $employee->name,
                'project' => $project->name,
                'task' => $task->name,
                'entry_date' => 'Jan 15, 2026',
                'hours' => 2.5,
                'warning' => '',
            ]],
        ]]);

        $this->postJson('/api/v1/ai/time-entry-drafts', [
            'company_id' => $company->id,
            'prompt' => "{$employee->name} worked on {$project->name}.",
        ])
            ->assertCreated()
            ->assertJsonPath('data.mode', 'draft')
            ->assertJsonPath('data.entries.0.company_id', $company->id)
            ->assertJsonPath('data.entries.0.employee_id', $employee->id)
            ->assertJsonPath('data.entries.0.project_id', $project->id)
            ->assertJsonPath('data.entries.0.task_id', $task->id)
            ->assertJsonPath('data.entries.0.entry_date', '2026-01-15')
            ->assertJsonPath('data.entries.0.hours', 2.5)
            ->assertJsonPath('data.entries.0.warnings', []);

        $this->assertDatabaseCount('time_entries', 0);
    }

    public function test_ai_time_entry_draft_endpoint_preserves_unresolved_warnings(): void
    {
        config()->set('ai.agents.time_entry_drafts.provider', 'deepseek');
        config()->set('ai.agents.time_entry_drafts.model', 'any-local-model');

        [$company, $employee] = $this->createCompanyWithEmployees();

        TimeEntryDraftAgent::fake([[
            'entries' => [[
                'company' => '',
                'employee' => $employee->name,
                'project' => 'Unknown Project',
                'task' => 'Unknown Task',
                'entry_date' => '',
                'hours' => 1,
                'warning' => 'Date was not provided.',
            ]],
        ]]);

        $response = $this->postJson('/api/v1/ai/time-entry-drafts', [
            'company_id' => $company->id,
            'prompt' => "{$employee->name} did some work.",
        ])->assertCreated();

        $draft = $response->json('data.entries.0');

        $this->assertSame($employee->id, $draft['employee_id']);
        $this->assertNull($draft['project_id']);
        $this->assertNull($draft['task_id']);
        $this->assertNull($draft['entry_date']);
        $this->assertContains('Date was not provided.', $draft['warnings']);
        $this->assertContains('Choose a matching project.', $draft['warnings']);
        $this->assertContains('Choose a matching task.', $draft['warnings']);
        $this->assertDatabaseCount('time_entries', 0);
    }

    public function test_ai_time_entry_draft_endpoint_parses_multiline_entries(): void
    {
        config()->set('ai.agents.time_entry_drafts.provider', 'deepseek');
        config()->set('ai.agents.time_entry_drafts.model', 'any-local-model');

        $company = Company::factory()->create();
        $employeeA = Employee::factory()->create();
        $employeeB = Employee::factory()->create();
        $project = Project::factory()->for($company)->create();
        $taskA = Task::factory()->for($company)->create(['name' => 'Development']);
        $taskB = Task::factory()->for($company)->create(['name' => 'Review']);

        foreach ([$employeeA, $employeeB] as $employee) {
            app(CompanyEmployeeAttachAction::class)->execute(new CompanyEmployeeData($company, $employee));
            app(EmployeeProjectAssignAction::class)->execute(new EmployeeProjectData($company, $employee, $project));
        }

        TimeEntryDraftAgent::fake([[
            'entries' => [
                [
                    'company' => '',
                    'employee' => $employeeA->name,
                    'project' => $project->name,
                    'task' => $taskA->name,
                    'entry_date' => 'Jan 20, 2026',
                    'hours' => 3.0,
                    'warning' => null,
                ],
                [
                    'company' => '',
                    'employee' => $employeeB->name,
                    'project' => $project->name,
                    'task' => $taskB->name,
                    'entry_date' => 'Jan 20, 2026',
                    'hours' => 5.5,
                    'warning' => null,
                ],
            ],
        ]]);

        $prompt = "Log the following for {$company->name}:\n"
            ."{$employeeA->name} worked 3 hours on Development for {$project->name} on Jan 20 2026.\n"
            ."{$employeeB->name} worked 5.5 hours on Review for {$project->name} on Jan 20 2026.";

        $this->postJson('/api/v1/ai/time-entry-drafts', [
            'company_id' => $company->id,
            'prompt' => $prompt,
        ])
            ->assertCreated()
            ->assertJsonPath('data.mode', 'draft')
            ->assertJsonCount(2, 'data.entries')
            ->assertJsonPath('data.entries.0.employee_id', $employeeA->id)
            ->assertJsonPath('data.entries.0.task_id', $taskA->id)
            ->assertJsonPath('data.entries.0.hours', 3)
            ->assertJsonPath('data.entries.0.warnings', [])
            ->assertJsonPath('data.entries.1.employee_id', $employeeB->id)
            ->assertJsonPath('data.entries.1.task_id', $taskB->id)
            ->assertJsonPath('data.entries.1.hours', 5.5)
            ->assertJsonPath('data.entries.1.warnings', []);

        $this->assertDatabaseCount('time_entries', 0);
    }

    public function test_ai_time_entry_draft_endpoint_can_call_real_provider(): void
    {
        if (! filter_var(env('AI_TIME_ENTRY_DRAFT_REAL_TEST', false), FILTER_VALIDATE_BOOLEAN)) {
            $this->markTestSkipped('Set AI_TIME_ENTRY_DRAFT_REAL_TEST=true to call the configured AI provider.');
        }

        $provider = config('ai.agents.time_entry_drafts.provider', 'openai');
        $apiKey = config("ai.providers.{$provider}.key");

        if (blank($apiKey)) {
            $this->markTestSkipped("Set the API key for the configured [{$provider}] AI provider.");
        }

        [$company, $employee, $project, $task] = $this->createAssignableTimeEntryGraph();

        $response = $this->postJson('/api/v1/ai/time-entry-drafts', [
            'company_id' => $company->id,
            'prompt' => "{$employee->name} worked 1.25 hours on {$task->name} for {$project->name} on 2026-01-15.",
        ])->assertCreated();

        $response
            ->assertJsonPath('data.mode', 'draft')
            ->assertJsonPath('data.entries.0.company_id', $company->id)
            ->assertJsonPath('data.entries.0.employee_id', $employee->id)
            ->assertJsonPath('data.entries.0.project_id', $project->id)
            ->assertJsonPath('data.entries.0.task_id', $task->id)
            ->assertJsonPath('data.entries.0.entry_date', '2026-01-15');

        $this->assertNotEmpty($response->json('data.entries.0.hours'));
        $this->assertDatabaseCount('time_entries', 0);
    }

    public function test_time_entry_draft_agent_provider_and_model_are_configurable(): void
    {
        $testProvider = env('AI_TEST_PROVIDER', 'deepseek');
        $testModel = env('AI_TEST_MODEL', 'blackbox-grok-fast');

        $previousProvider = $this->swapEnvironmentValue('AI_TIME_ENTRY_DRAFT_PROVIDER', $testProvider);
        $previousModel = $this->swapEnvironmentValue('AI_TIME_ENTRY_DRAFT_MODEL', $testModel);

        try {
            Env::enablePutenv();
            config()->set('ai', require config_path('ai.php'));

            $agent = new TimeEntryDraftAgent;

            $this->assertSame($testProvider, $agent->provider());
            $this->assertSame($testModel, $agent->model());
        } finally {
            $this->restoreEnvironmentValue('AI_TIME_ENTRY_DRAFT_PROVIDER', $previousProvider);
            $this->restoreEnvironmentValue('AI_TIME_ENTRY_DRAFT_MODEL', $previousModel);
            Env::enablePutenv();
            config()->set('ai', require config_path('ai.php'));
        }
    }

    public function test_time_entries_endpoint_updates_an_existing_entry(): void
    {
        [$company, $employee, $project, $task] = $this->createAssignableTimeEntryGraph();
        $otherTask = Task::factory()->for($company)->create(['name' => 'Review']);
        $timeEntry = TimeEntry::factory()
            ->for($company)
            ->for($employee)
            ->for($project)
            ->for($task)
            ->create([
                'entry_date' => '2026-01-15',
                'hours' => '2.00',
            ]);

        $this->patchJson("/api/v1/time-entries/{$timeEntry->id}", [
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'project_id' => $project->id,
            'task_id' => $otherTask->id,
            'entry_date' => '2026-01-16',
            'hours' => '4.25',
        ])
            ->assertOk()
            ->assertJsonPath('data.id', $timeEntry->id)
            ->assertJsonPath('data.task.name', 'Review')
            ->assertJsonPath('data.entry_date', '2026-01-16')
            ->assertJsonPath('data.hours_display', '4.25');

        $timeEntry->refresh();

        $this->assertSame($otherTask->id, $timeEntry->task_id);
        $this->assertSame('2026-01-16', $timeEntry->entry_date->toDateString());
        $this->assertSame('4.25', $timeEntry->hours_display);
    }

    public function test_time_entries_endpoint_rejects_update_project_conflicts(): void
    {
        [$company, $employee, $firstProject, $task] = $this->createAssignableTimeEntryGraph();
        $secondProject = Project::factory()->for($company)->create();
        $otherTask = Task::factory()->for($company)->create();

        app(EmployeeProjectAssignAction::class)->execute(new EmployeeProjectData(
            company: $company,
            employee: $employee,
            project: $secondProject,
        ));

        TimeEntry::factory()
            ->for($company)
            ->for($employee)
            ->for($firstProject)
            ->for($task)
            ->create(['entry_date' => '2026-01-15']);
        $timeEntry = TimeEntry::factory()
            ->for($company)
            ->for($employee)
            ->for($secondProject)
            ->for($otherTask)
            ->create(['entry_date' => '2026-01-16']);

        $this->patchJson("/api/v1/time-entries/{$timeEntry->id}", [
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'project_id' => $secondProject->id,
            'task_id' => $otherTask->id,
            'entry_date' => '2026-01-15',
            'hours' => '1.00',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['entries.0.project_id']);
    }

    public function test_time_entries_endpoint_rejects_update_duplicate_task_conflicts(): void
    {
        [$company, $employee, $project, $task] = $this->createAssignableTimeEntryGraph();
        $otherTask = Task::factory()->for($company)->create();

        TimeEntry::factory()
            ->for($company)
            ->for($employee)
            ->for($project)
            ->for($task)
            ->create(['entry_date' => '2026-01-15']);
        $timeEntry = TimeEntry::factory()
            ->for($company)
            ->for($employee)
            ->for($project)
            ->for($otherTask)
            ->create(['entry_date' => '2026-01-16']);

        $this->patchJson("/api/v1/time-entries/{$timeEntry->id}", [
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'project_id' => $project->id,
            'task_id' => $task->id,
            'entry_date' => '2026-01-15',
            'hours' => '1.00',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['entries.0.task_id']);
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

    public function test_time_entries_endpoint_allows_new_task_for_existing_project_and_date(): void
    {
        [$company, $employee, $project, $task] = $this->createAssignableTimeEntryGraph();
        $otherTask = Task::factory()->for($company)->create();

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
                'project_id' => $project->id,
                'task_id' => $otherTask->id,
                'entry_date' => '2026-01-15',
                'hours' => '1.50',
            ]],
        ])->assertCreated();

        $this->assertSame(2, TimeEntry::query()->count());
    }

    public function test_time_entries_endpoint_rejects_duplicate_task_rows_within_batch(): void
    {
        [$company, $employee, $project, $task] = $this->createAssignableTimeEntryGraph();

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
                    'task_id' => $task->id,
                    'entry_date' => '2026-01-15',
                    'hours' => '1.50',
                ],
            ],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['entries.0.task_id', 'entries.1.task_id']);

        $this->assertDatabaseCount('time_entries', 0);
    }

    public function test_time_entries_endpoint_rejects_duplicate_task_rows_against_existing_entries(): void
    {
        [$company, $employee, $project, $task] = $this->createAssignableTimeEntryGraph();

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
                'project_id' => $project->id,
                'task_id' => $task->id,
                'entry_date' => '2026-01-15',
                'hours' => '1.50',
            ]],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['entries.0.task_id']);

        $this->assertSame(1, TimeEntry::query()->count());
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

    public function test_time_entries_endpoint_returns_readable_required_field_messages(): void
    {
        $response = $this->postJson('/api/v1/time-entries', [
            'entries' => [[
                'company_id' => null,
                'employee_id' => null,
                'project_id' => null,
                'task_id' => null,
                'entry_date' => null,
                'hours' => null,
            ]],
        ])->assertUnprocessable();

        $errors = $response->json('errors');

        $this->assertSame('Choose a company.', $errors['entries.0.company_id'][0]);
        $this->assertSame('Choose an employee.', $errors['entries.0.employee_id'][0]);
        $this->assertSame('Choose a project.', $errors['entries.0.project_id'][0]);
        $this->assertSame('Choose a task.', $errors['entries.0.task_id'][0]);
        $this->assertSame('Choose a date.', $errors['entries.0.entry_date'][0]);
        $this->assertSame('Enter hours.', $errors['entries.0.hours'][0]);
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

    public function test_time_entries_endpoint_allows_shared_employee_on_different_company_projects_same_date(): void
    {
        [$firstCompany, $secondCompany, $employee, $firstProject, $secondProject, $firstTask, $secondTask] = $this->createSharedEmployeeCompanyGraphs();

        $this->postJson('/api/v1/time-entries', [
            'entries' => [
                [
                    'company_id' => $firstCompany->id,
                    'employee_id' => $employee->id,
                    'project_id' => $firstProject->id,
                    'task_id' => $firstTask->id,
                    'entry_date' => '2026-01-15',
                    'hours' => '2.00',
                ],
                [
                    'company_id' => $secondCompany->id,
                    'employee_id' => $employee->id,
                    'project_id' => $secondProject->id,
                    'task_id' => $secondTask->id,
                    'entry_date' => '2026-01-15',
                    'hours' => '1.50',
                ],
            ],
        ])->assertCreated();

        $this->assertSame(2, TimeEntry::query()->count());
    }

    public function test_time_entries_endpoint_allows_shared_employee_existing_conflict_only_per_company(): void
    {
        [$firstCompany, $secondCompany, $employee, $firstProject, $secondProject, $firstTask, $secondTask] = $this->createSharedEmployeeCompanyGraphs();

        TimeEntry::factory()
            ->for($firstCompany)
            ->for($employee)
            ->for($firstProject)
            ->for($firstTask)
            ->create(['entry_date' => '2026-01-15']);

        $this->postJson('/api/v1/time-entries', [
            'entries' => [[
                'company_id' => $secondCompany->id,
                'employee_id' => $employee->id,
                'project_id' => $secondProject->id,
                'task_id' => $secondTask->id,
                'entry_date' => '2026-01-15',
                'hours' => '1.50',
            ]],
        ])->assertCreated();

        $this->assertSame(2, TimeEntry::query()->count());
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

    /**
     * @return array{0: Company, 1: Company, 2: Employee, 3: Project, 4: Project, 5: Task, 6: Task}
     */
    private function createSharedEmployeeCompanyGraphs(): array
    {
        $firstCompany = Company::factory()->create();
        $secondCompany = Company::factory()->create();
        $employee = Employee::factory()->create();
        $firstProject = Project::factory()->for($firstCompany)->create();
        $secondProject = Project::factory()->for($secondCompany)->create();
        $firstTask = Task::factory()->for($firstCompany)->create();
        $secondTask = Task::factory()->for($secondCompany)->create();

        foreach ([$firstCompany, $secondCompany] as $company) {
            app(CompanyEmployeeAttachAction::class)->execute(new CompanyEmployeeData(
                company: $company,
                employee: $employee,
            ));
        }

        app(EmployeeProjectAssignAction::class)->execute(new EmployeeProjectData(
            company: $firstCompany,
            employee: $employee,
            project: $firstProject,
        ));
        app(EmployeeProjectAssignAction::class)->execute(new EmployeeProjectData(
            company: $secondCompany,
            employee: $employee,
            project: $secondProject,
        ));

        return [$firstCompany, $secondCompany, $employee, $firstProject, $secondProject, $firstTask, $secondTask];
    }

    /**
     * @return array{env: mixed, server: mixed, getenv: string|false}
     */
    private function swapEnvironmentValue(string $key, string $value): array
    {
        $previous = [
            'env' => $_ENV[$key] ?? null,
            'server' => $_SERVER[$key] ?? null,
            'getenv' => getenv($key),
        ];

        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
        putenv("{$key}={$value}");

        return $previous;
    }

    /**
     * @param  array{env: mixed, server: mixed, getenv: string|false}  $previous
     */
    private function restoreEnvironmentValue(string $key, array $previous): void
    {
        if ($previous['env'] === null) {
            unset($_ENV[$key]);
        } else {
            $_ENV[$key] = $previous['env'];
        }

        if ($previous['server'] === null) {
            unset($_SERVER[$key]);
        } else {
            $_SERVER[$key] = $previous['server'];
        }

        if ($previous['getenv'] === false) {
            putenv($key);

            return;
        }

        putenv("{$key}={$previous['getenv']}");
    }
}
