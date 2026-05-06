<?php

namespace App\Actions;

use App\Data\TimeEntryData;
use App\Models\TimeEntry;

readonly class TimeEntryCreateAction
{
    public function execute(TimeEntryData $data): TimeEntry
    {
        return TimeEntry::query()->create($data->toModelData());
    }
}
