<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ConsultationUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'operador';
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')],
            'password' => ['required', 'string', 'max:128', Password::min(8)->mixedCase()->numbers()],
            'role' => ['prohibited'],
            'ativo' => ['prohibited'],
        ];
    }
}
