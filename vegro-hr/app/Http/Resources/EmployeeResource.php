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
            'department' => $this->department->name ?? null,
            'role' => $this->role->first()?->title ?? null,
            'salary' => $this->salary,
            'created_at' => $this->created_at
        ];
    }
}