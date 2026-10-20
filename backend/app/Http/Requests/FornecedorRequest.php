<?php

namespace App\Http\Requests;

use App\Models\Fornecedor;
use App\Support\BrazilianDocuments;
use Illuminate\Foundation\Http\FormRequest;

class FornecedorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can($this->route('fornecedor') ? 'update' : 'create', $this->route('fornecedor') ?? Fornecedor::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'cnpj' => BrazilianDocuments::cnpj((string) $this->input('cnpj', '')),
            'telefone' => BrazilianDocuments::phone((string) $this->input('telefone', '')),
        ]);
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:200'],
            'cidade' => ['required', 'string', 'max:200'],
            'telefone' => ['nullable', 'regex:/^\([1-9][0-9]\) [0-9]{4,5}-[0-9]{4}$/D'],
            'cnpj' => ['required', 'string', 'max:18', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! BrazilianDocuments::validCnpj($value)) {
                    $fail('Informe um CNPJ válido, numérico ou alfanumérico.');
                }
                $raw = str_replace(['.', '/', '-'], '', $value);
                $exists = Fornecedor::query()
                    ->whereRaw("REPLACE(REPLACE(REPLACE(UPPER(cnpj), '.', ''), '/', ''), '-', '') = ?", [$raw])
                    ->when($this->route('fornecedor'), fn ($query, $model) => $query->where('id', '!=', $model->id))->exists();
                if ($exists) {
                    $fail('Já existe um fornecedor com este CNPJ.');
                }
            }],
        ];
    }
}
