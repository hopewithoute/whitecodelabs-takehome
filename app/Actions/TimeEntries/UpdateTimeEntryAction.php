<?php

namespace App\Actions\TimeEntries;

use App\Data\TimeEntryBatchData;
use App\Data\TimeEntryUpdateData;
use App\Domain\TimeEntries\TimeEntryBatchBusinessValidator;
use App\Models\TimeEntry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

readonly class UpdateTimeEntryAction
{
    public function __construct(
        private TimeEntryBatchBusinessValidator $validator,
    ) {}

    public function execute(TimeEntry $timeEntry, TimeEntryUpdateData $data): TimeEntry
    {
        $entry = $data->toBatchEntry();

        $this->validator->validate(
            new TimeEntryBatchData([$entry]),
            [$timeEntry->id],
        );

        return DB::transaction(function () use ($timeEntry, $entry): TimeEntry {
            $timeEntry->update([
                'company_id' => $entry['company_id'],
                'employee_id' => $entry['employee_id'],
                'project_id' => $entry['project_id'],
                'task_id' => $entry['task_id'],
                'entry_date' => Carbon::parse($entry['entry_date'])->toDateString(),
                'hours' => $entry['hours'],
            ]);

            return $timeEntry->fresh([
                'company:id,name,created_at,updated_at',
                'employee:id,name,email,created_at,updated_at',
                'project:id,company_id,name,created_at,updated_at',
                'task:id,company_id,name,created_at,updated_at',
            ]);
        });
    }
}
