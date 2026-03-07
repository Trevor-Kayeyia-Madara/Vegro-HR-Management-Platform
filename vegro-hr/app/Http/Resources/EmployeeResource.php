<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->first_name . ' ' . $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'department' => $this->department->name ?? null,
            'role' => $this->role->name ?? null,
            'salary' => $this->salary,
            'created_at' => $this->created_at
        ];
    }
}