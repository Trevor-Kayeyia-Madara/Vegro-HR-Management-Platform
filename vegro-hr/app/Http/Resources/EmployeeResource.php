<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name ?? null,
            'email' => $this->email,
            'phone' => $this->phone,
            'department' => $this->department?->name ?? null,
            'role' => $this->roles?->first()?->title ?? null,
            'roles' => $this->roles?->pluck('title') ?? [],
            'salary' => $this->salary,
            'annual_leave_days' => $this->annual_leave_days,
            'annual_leave_used' => $this->annual_leave_used,
            'annual_leave_balance' => $this->annual_leave_balance,
            'created_at' => $this->created_at
        ];
    }
}
