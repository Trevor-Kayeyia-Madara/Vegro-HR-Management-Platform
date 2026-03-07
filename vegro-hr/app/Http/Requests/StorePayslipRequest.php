<?php

namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;

class StorePayslipRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'employee_id' => 'required|exists:employees,id',
            'pay_period' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'status' => 'required|in:pending,paid'
        ];
    }
}