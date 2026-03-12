<?php

namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Company;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $companyId = $this->input('company_id');
        if (!$companyId && $this->filled('company_domain')) {
            $companyId = Company::where('domain', $this->input('company_domain'))->value('id');
        }

        $emailRules = ['required', 'string', 'email', 'max:255'];
        if ($companyId) {
            $emailRules[] = Rule::unique('users', 'email')->where('company_id', $companyId);
        } else {
            $emailRules[] = 'unique:users';
        }

        return [
            'name' => 'required|string|max:255',
            'email' => $emailRules,
            'password' => 'required|string|min:8|confirmed',
            'company_id' => 'nullable|integer|exists:companies,id',
            'company_domain' => 'nullable|string|max:255',
        ];
    }
}
