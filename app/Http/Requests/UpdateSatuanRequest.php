<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSatuanRequest extends FormRequest
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
            'nama' => ['required', 'string', 'max:255', 'unique:satuans,nama,'.$this->satuan->id],
            'simbol' => ['required', 'string', 'max:20', 'unique:satuans,simbol,'.$this->satuan->id],
            'deskripsi' => ['nullable', 'string'],
            'conversions' => ['nullable', 'array'],
            'conversions.*.to_satuan_id' => ['required', 'exists:satuans,id'],
            'conversions.*.rasio' => ['required', 'numeric', 'min:0.0001'],
        ];
    }
}
