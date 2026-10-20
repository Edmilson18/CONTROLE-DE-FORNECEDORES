<?php

namespace App\Http\Requests;

use App\Models\Compra;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can($this->route('compra') ? 'update' : 'create', $this->route('compra') ?? Compra::class);
    }

    public function rules(): array
    {
        return [
            'fornecedores_id' => ['required', 'integer', Rule::exists('fornecedores', 'id')->whereNull('deleted_at')],
            'data' => ['required', 'date_format:Y-m-d'],
            'vencimento' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:data'],
            'valor' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:9999999999.99'],
            'status' => ['required', Rule::in(['pendente', 'pago', 'cancelado'])],
            'descricao' => ['nullable', 'string', 'max:500'],
        ];
    }
}
