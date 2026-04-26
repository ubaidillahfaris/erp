<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $employeeId = $this->route('employee');

        return [
            'name' => 'required|string|max:255',
            'nik' => [
                'required',
                'string',
                Rule::unique('employees', 'nik')->ignore($employeeId),
            ],
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'position' => 'required|string|max:100',
            'department' => 'required|string|max:100',
            'join_date' => 'required|date',
            'employment_type' => 'required|string|in:Tetap,Kontrak,Harian',
            'status' => 'required|string|in:active,inactive',
            'basic_salary' => 'required|numeric|min:0',
            'bank_name' => 'nullable|string|max:100',
            'bank_account' => 'nullable|string|max:50',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'user_id' => 'nullable|exists:users,id',
            'status' => 'required|string|in:active,inactive',
            'documents' => 'nullable|array',
            'documents.*' => 'file|mimes:jpg,jpeg,png,webp,pdf,doc,docx|max:5120',
            'documents_meta' => 'nullable|string',
        ];
    }
}
