<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray($request)
    {
        $leaveBalances = $this->whenLoaded('leaveBalances', function () {
            return $this->leaveBalances->map(function ($balance) {
                return [
                    'leave_type' => $balance->leave_type,
                    'entitled_days' => (float) $balance->entitled_days,
                    'used_days' => (float) $balance->used_days,
                    'balance_days' => (float) $balance->balance_days,
                    'carry_forward_days' => (float) ($balance->carry_forward_days ?? 0),
                    'last_reset_at' => optional($balance->last_reset_at)->toDateString(),
                ];
            })->values();
        }, []);

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->name ?? null,
            'email' => $this->email,
            'phone' => $this->phone,
            'department_id' => $this->department_id,
            'department' => $this->department?->name ?? null,
            'role' => $this->roles?->first()?->title ?? null,
            'roles' => $this->roles?->pluck('title') ?? [],
            'role_ids' => $this->roles?->pluck('id') ?? [],
            'salary' => $this->salary,
            'status' => $this->status,
            'annual_leave_days' => $this->annual_leave_days,
            'annual_leave_used' => $this->annual_leave_used,
            'annual_leave_balance' => $this->annual_leave_balance,
            'leave_balances' => $leaveBalances,
            'created_at' => $this->created_at
        ];
    }
}
