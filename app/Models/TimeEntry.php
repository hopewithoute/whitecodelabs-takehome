<?php

namespace App\Models;

use Database\Factories\TimeEntryFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class TimeEntry extends Model
{
    /** @use HasFactory<TimeEntryFactory> */
    use HasFactory, HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'employee_id',
        'project_id',
        'task_id',
        'entry_date',
        'hours',
    ];

    /**
     * @var list<string>
     */
    protected $appends = [
        'entry_date_display',
        'hours_display',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'hours' => 'decimal:2',
        ];
    }

    /**
     * @return Attribute<never, never>
     */
    protected function entryDateDisplay(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->entry_date instanceof Carbon
                ? $this->entry_date->toFormattedDateString()
                : null,
        );
    }

    /**
     * @return Attribute<never, never>
     */
    protected function hoursDisplay(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->hours,
        );
    }
}
