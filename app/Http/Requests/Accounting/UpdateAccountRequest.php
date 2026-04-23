<?php

namespace App\Http\Requests\Accounting;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'unique:accounts,code,'.$this->account->id],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:asset,liability,equity,income,expense'],
            'balance_type' => ['required', 'string', 'in:debit,credit'],
            'is_active' => ['boolean'],
        ];
    }
}
