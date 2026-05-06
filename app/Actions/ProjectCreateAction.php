<?php

namespace App\Actions;

use App\Data\ProjectData;
use App\Models\Project;
use App\Services\TimeEntryReferenceDataCache;

readonly class ProjectCreateAction
{
    public function __construct(
        private TimeEntryReferenceDataCache $cache,
    ) {}

    public function execute(ProjectData $data): Project
    {
        $project = Project::query()->create($data->toModelData());

        $this->cache->invalidateCompany($data->company);

        return $project;
    }
}
