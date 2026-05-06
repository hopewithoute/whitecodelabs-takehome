<?php

namespace App\Actions\TimeEntries;

use App\Data\TimeEntryBatchData;
use App\Domain\TimeEntries\TimeEntryBatchBusinessValidator;
use App\Models\TimeEntry;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

readonly class CreateTimeEntryBatchAction
{
    public function __construct(
        private TimeEntryBatchBusinessValidator $validator,
    ) {}

    /**
     * @return EloquentCollection<int, TimeEntry>
     */
    public function execute(TimeEntryBatchData $data): EloquentCollection
    {
        $this->validator->validate($data);

        $timeEntry = new TimeEntry;
        $timestamp = now();
        $ids = collect();
        $rows = collect($data->entries)->map(function (array $entry) use ($timeEntry, $timestamp, $ids): array {
            $id = $timeEntry->newUniqueId();
            $ids->push($id);

            return [
                'id' => $id,
                'company_id' => $entry['company_id'],
                'employee_id' => $entry['employee_id'],
                'project_id' => $entry['project_id'],
                'task_id' => $entry['task_id'],
                'entry_date' => Carbon::parse($entry['entry_date'])->toDateString(),
                'hours' => $entry['hours'],
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        });

        return DB::transaction(function () use ($ids, $rows): EloquentCollection {
            TimeEntry::query()->insert($rows->all());

            $createdEntries = TimeEntry::query()
                ->whereKey($ids)
                ->with([
                    'company:id,name,created_at,updated_at',
                    'employee:id,name,email,created_at,updated_at',
                    'project:id,company_id,name,created_at,updated_at',
                    'task:id,company_id,name,created_at,updated_at',
                ])
                ->get()
                ->keyBy('id');

            return new EloquentCollection($ids
                ->map(fn (string $id): ?TimeEntry => $createdEntries->get($id))
                ->filter()
                ->values()
                ->all());
        });
    }
}
