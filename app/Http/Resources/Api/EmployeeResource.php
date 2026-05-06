<?php

namespace App\Http\Resources\Api;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/** @mixin Employee */
class EmployeeResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'created_at' => $this->created_at instanceof Carbon ? $this->created_at->toIso8601String() : null,
            'updated_at' => $this->updated_at instanceof Carbon ? $this->updated_at->toIso8601String() : null,
        ];
    }
}
