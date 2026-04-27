<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUnitRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255', 'unique:units,name,'.$this->unit->id],
            'symbol' => ['required', 'string', 'max:20', 'unique:units,symbol,'.$this->unit->id],
            'description' => ['nullable', 'string'],
            'conversions' => ['nullable', 'array'],
            'conversions.*.to_unit_id' => ['required', 'exists:units,id'],
            'conversions.*.rasio' => ['required', 'numeric', 'min:0.0001'],
        ];
    }
}
