<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeRequest extends FormRequest
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
    public function rules()
  {
     return [
        'first_name' => 'sometimes|string|max:100',
        'last_name' => 'sometimes|string|max:100',
        'phone' => 'nullable|string|max:20',
        'department_id' => 'sometimes|exists:departments,id',
        'role_id' => 'sometimes|exists:roles,id',
        'salary' => 'sometimes|numeric|min:0'
     ];
   }
}
