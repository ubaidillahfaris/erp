<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSatuanRequest extends FormRequest
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
        return [
            'nama' => ['required', 'string', 'max:255', 'unique:satuans,nama'],
            'simbol' => ['nullable', 'string', 'max:20', 'unique:satuans,simbol'],
            'deskripsi' => ['nullable', 'string'],
            'conversions' => ['nullable', 'array'],
            'conversions.*.to_satuan_id' => ['required', 'exists:satuans,id'],
            'conversions.*.rasio' => ['required', 'numeric', 'min:0'],
        ];
    }
}
