<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\TimeEntry;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Seed data should exercise the required relationship edge cases.
     */
    public function test_database_seeder_creates_demo_time_entry_graph(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(2, Company::query()->count());
        $this->assertSame(4, Employee::query()->count());
        $this->assertSame(4, TimeEntry::query()->count());

        $cora = Employee::query()->where('email', 'cora.diaz@example.test')->firstOrFail();
        $this->assertSame(2, $cora->companies()->count());
        $this->assertSame(2, $cora->projects()->count());

        $avaJanuaryFifth = TimeEntry::query()
            ->whereRelation('employee', 'email', 'ava.chen@example.test')
            ->whereDate('entry_date', '2026-01-05')
            ->get();

        $this->assertCount(2, $avaJanuaryFifth);
        $this->assertSame(1, $avaJanuaryFifth->pluck('project_id')->unique()->count());
        $this->assertSame(2, $avaJanuaryFifth->pluck('task_id')->unique()->count());
    }
}
