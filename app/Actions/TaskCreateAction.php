<?php

namespace App\Actions;

use App\Data\TaskData;
use App\Models\Task;
use App\Services\TimeEntryReferenceDataCache;

readonly class TaskCreateAction
{
    public function __construct(
        private TimeEntryReferenceDataCache $cache,
    ) {}

    public function execute(TaskData $data): Task
    {
        $task = Task::query()->create($data->toModelData());

        $this->cache->invalidateCompany($data->company);

        return $task;
    }
}
