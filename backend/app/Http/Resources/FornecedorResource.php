<?php

namespace App\Http\Resources;

use App\Support\BrazilianDocuments;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FornecedorResource extends JsonResource
{
    public static bool $forceWrapping = true;

    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'nome' => $this->nome, 'cnpj' => BrazilianDocuments::cnpj($this->cnpj),
            'telefone' => BrazilianDocuments::phone($this->telefone ?? ''), 'cidade' => $this->cidade,
            'arquivado' => $this->deleted_at !== null];
    }
}
