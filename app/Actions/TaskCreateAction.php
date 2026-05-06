<?php

namespace App\Actions;

use App\Data\TaskData;
use App\Models\Task;

readonly class TaskCreateAction
{
    public function execute(TaskData $data): Task
    {
        return Task::query()->create($data->toModelData());
    }
}
