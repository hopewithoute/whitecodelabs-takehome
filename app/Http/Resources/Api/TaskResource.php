<?php

namespace App\Http\Resources\Api;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/** @mixin Task */
class TaskResource extends JsonResource
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
            'name' => $this->name,
            'created_at' => $this->created_at instanceof Carbon ? $this->created_at->toIso8601String() : null,
            'updated_at' => $this->updated_at instanceof Carbon ? $this->updated_at->toIso8601String() : null,
        ];
    }
}
