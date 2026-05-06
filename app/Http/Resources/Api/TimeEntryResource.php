<?php

namespace App\Http\Resources\Api;

use App\Models\TimeEntry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/** @mixin TimeEntry */
class TimeEntryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'employee_id' => $this->employee_id,
            'project_id' => $this->project_id,
            'task_id' => $this->task_id,
            'entry_date' => $this->entry_date instanceof Carbon ? $this->entry_date->toDateString() : null,
            'entry_date_display' => $this->entry_date_display,
            'hours' => $this->hours,
            'hours_display' => $this->hours_display,
            'company' => new CompanyResource($this->whenLoaded('company')),
            'employee' => new EmployeeResource($this->whenLoaded('employee')),
            'project' => new ProjectResource($this->whenLoaded('project')),
            'task' => new TaskResource($this->whenLoaded('task')),
            'created_at' => $this->created_at instanceof Carbon ? $this->created_at->toIso8601String() : null,
            'updated_at' => $this->updated_at instanceof Carbon ? $this->updated_at->toIso8601String() : null,
        ];
    }
}
