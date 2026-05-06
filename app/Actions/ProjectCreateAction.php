<?php

namespace App\Actions;

use App\Data\ProjectData;
use App\Models\Project;

readonly class ProjectCreateAction
{
    public function execute(ProjectData $data): Project
    {
        return Project::query()->create($data->toModelData());
    }
}
