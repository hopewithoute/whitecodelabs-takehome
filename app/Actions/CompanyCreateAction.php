<?php

namespace App\Actions;

use App\Data\CompanyData;
use App\Models\Company;

readonly class CompanyCreateAction
{
    public function execute(CompanyData $data): Company
    {
        return Company::query()->create($data->toModelData());
    }
}
