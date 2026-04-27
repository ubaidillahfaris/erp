<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreUnitRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:255', 'unique:units,name'],
            'symbol' => ['nullable', 'string', 'max:20', 'unique:units,symbol'],
            'description' => ['nullable', 'string'],
            'conversions' => ['nullable', 'array'],
            'conversions.*.to_unit_id' => ['required', 'exists:units,id'],
            'conversions.*.rasio' => ['required', 'numeric', 'min:0'],
        ];
    }
}
