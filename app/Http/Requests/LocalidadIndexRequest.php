<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LocalidadIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => 'nullable|string|min:2|max:100',
            'limit' => 'nullable|integer|min:1|max:100'
        ];
    }

    public function messages(): array
    {
        return [
            'q.min' => 'El término de búsqueda debe tener al menos 2 caracteres',
            'limit.max' => 'El límite máximo es 100 registros'
        ];
    }
}
