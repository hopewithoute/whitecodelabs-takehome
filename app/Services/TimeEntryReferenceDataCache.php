<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Facades\Cache;

class TimeEntryReferenceDataCache
{
    private const PREFIX = 'time-entry-reference-data';

    public function ttl(): \DateTimeInterface
    {
        return now()->addHour();
    }

    public function companiesKey(): string
    {
        return $this->key('companies', $this->version('companies'));
    }

    public function companyEmployeesKey(Company|string $company): string
    {
        $companyId = $this->companyId($company);

        return $this->key('company', $companyId, $this->companyVersion($companyId), 'employees');
    }

    public function companyProjectsKey(Company|string $company, ?string $employeeId = null, string $sort = 'name'): string
    {
        $companyId = $this->companyId($company);

        return $this->key('company', $companyId, $this->companyVersion($companyId), 'projects', $employeeId ?? 'all', $sort);
    }

    public function companyTasksKey(Company|string $company): string
    {
        $companyId = $this->companyId($company);

        return $this->key('company', $companyId, $this->companyVersion($companyId), 'tasks');
    }

    public function invalidateCompanies(): void
    {
        $this->bumpVersion($this->versionKey('companies'));
    }

    public function invalidateCompany(Company|string $company): void
    {
        $this->bumpVersion($this->versionKey($this->companyVersionKey($this->companyId($company))));
    }

    private function companyVersion(string $companyId): int
    {
        return $this->version($this->companyVersionKey($companyId));
    }

    private function version(string $name): int
    {
        $key = $this->versionKey($name);

        Cache::add($key, 1);

        return Cache::get($key, 1);
    }

    private function bumpVersion(string $key): void
    {
        Cache::add($key, 1);
        Cache::increment($key);
    }

    private function companyVersionKey(string $companyId): string
    {
        return "company:{$companyId}";
    }

    private function versionKey(string $name): string
    {
        return $this->key('versions', $name);
    }

    private function key(string ...$parts): string
    {
        return implode(':', [self::PREFIX, ...$parts]);
    }

    private function companyId(Company|string $company): string
    {
        return $company instanceof Company ? $company->id : $company;
    }
}
