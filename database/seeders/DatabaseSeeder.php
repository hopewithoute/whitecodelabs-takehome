<?php

namespace Database\Seeders;

use App\Actions\CompanyCreateAction;
use App\Actions\CompanyEmployeeAttachAction;
use App\Actions\EmployeeCreateAction;
use App\Actions\EmployeeProjectAssignAction;
use App\Actions\ProjectCreateAction;
use App\Actions\TaskCreateAction;
use App\Actions\TimeEntries\CreateTimeEntryBatchAction;
use App\Data\CompanyData;
use App\Data\CompanyEmployeeData;
use App\Data\EmployeeData;
use App\Data\EmployeeProjectData;
use App\Data\ProjectData;
use App\Data\TaskData;
use App\Data\TimeEntryBatchData;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(
        CompanyCreateAction $createCompany,
        EmployeeCreateAction $createEmployee,
        CompanyEmployeeAttachAction $attachEmployee,
        ProjectCreateAction $createProject,
        TaskCreateAction $createTask,
        EmployeeProjectAssignAction $assignProject,
        CreateTimeEntryBatchAction $createTimeEntries,
    ): void {
        $acme = $createCompany->execute(new CompanyData('Acme Operations'));
        $globex = $createCompany->execute(new CompanyData('Globex Services'));

        $ava = $createEmployee->execute(new EmployeeData('Ava Chen', 'ava.chen@example.test'));
        $ben = $createEmployee->execute(new EmployeeData('Ben Carter', 'ben.carter@example.test'));
        $cora = $createEmployee->execute(new EmployeeData('Cora Diaz', 'cora.diaz@example.test'));
        $dev = $createEmployee->execute(new EmployeeData('Dev Malik', 'dev.malik@example.test'));

        foreach ([$ava, $ben, $cora] as $employee) {
            $attachEmployee->execute(new CompanyEmployeeData($acme, $employee));
        }

        foreach ([$cora, $dev] as $employee) {
            $attachEmployee->execute(new CompanyEmployeeData($globex, $employee));
        }

        $acmeWebsite = $createProject->execute(new ProjectData($acme, 'Website Redesign'));
        $acmeMobile = $createProject->execute(new ProjectData($acme, 'Mobile Launch'));
        $globexMigration = $createProject->execute(new ProjectData($globex, 'Data Migration'));
        $globexSupport = $createProject->execute(new ProjectData($globex, 'Client Support'));

        $createTask->execute(new TaskData($acme, 'Planning'));
        $acmeDevelopment = $createTask->execute(new TaskData($acme, 'Development'));
        $acmeReview = $createTask->execute(new TaskData($acme, 'Review'));

        $createTask->execute(new TaskData($globex, 'Discovery'));
        $globexCleanup = $createTask->execute(new TaskData($globex, 'Cleanup'));
        $globexSupportTask = $createTask->execute(new TaskData($globex, 'Support'));

        $assignProject->execute(new EmployeeProjectData($acme, $ava, $acmeWebsite));
        $assignProject->execute(new EmployeeProjectData($acme, $ava, $acmeMobile));
        $assignProject->execute(new EmployeeProjectData($acme, $ben, $acmeWebsite));
        $assignProject->execute(new EmployeeProjectData($acme, $cora, $acmeMobile));
        $assignProject->execute(new EmployeeProjectData($globex, $cora, $globexMigration));
        $assignProject->execute(new EmployeeProjectData($globex, $dev, $globexMigration));
        $assignProject->execute(new EmployeeProjectData($globex, $dev, $globexSupport));

        $createTimeEntries->execute(new TimeEntryBatchData([
            [
                'company_id' => $acme->id,
                'employee_id' => $ava->id,
                'project_id' => $acmeWebsite->id,
                'task_id' => $acmeDevelopment->id,
                'entry_date' => '2026-01-05',
                'hours' => '4.00',
            ],
            [
                'company_id' => $acme->id,
                'employee_id' => $ava->id,
                'project_id' => $acmeWebsite->id,
                'task_id' => $acmeReview->id,
                'entry_date' => '2026-01-05',
                'hours' => '2.00',
            ],
            [
                'company_id' => $globex->id,
                'employee_id' => $dev->id,
                'project_id' => $globexSupport->id,
                'task_id' => $globexSupportTask->id,
                'entry_date' => '2026-01-06',
                'hours' => '3.50',
            ],
            [
                'company_id' => $globex->id,
                'employee_id' => $cora->id,
                'project_id' => $globexMigration->id,
                'task_id' => $globexCleanup->id,
                'entry_date' => '2026-01-07',
                'hours' => '5.25',
            ],
        ]));
    }
}
