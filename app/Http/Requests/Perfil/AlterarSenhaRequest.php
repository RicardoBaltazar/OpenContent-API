<?php

namespace App\Http\Requests\Perfil;

use Illuminate\Foundation\Http\FormRequest;

class AlterarSenhaRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'senha_atual' => ['required', 'current_password:sanctum'],
            'senha' => ['required', 'string', 'min:8', 'confirmed', 'different:senha_atual'],
        ];
    }
}
