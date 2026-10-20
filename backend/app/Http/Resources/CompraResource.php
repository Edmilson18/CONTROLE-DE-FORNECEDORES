<?php

namespace App\Http\Resources;

use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompraResource extends JsonResource
{
    public static bool $forceWrapping = true;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'data' => $this->data->format('Y-m-d'), 'valor' => $this->valor,
            'status' => $this->status, 'fornecedores_id' => $this->fornecedores_id,
            'fornecedor' => $this->fornecedor ? new FornecedorResource($this->fornecedor) : null,
            'vencimento' => $this->vencimento?->format('Y-m-d'), 'descricao' => $this->descricao,
            'pago' => $this->legacyPaid() ? $this->valor : Money::decimal($this->paidCents()),
            'saldo' => Money::decimal($this->balanceCents()), 'quitada_no_legado' => $this->legacyPaid(),
            'pagamentos' => $this->pagamentos,
        ];
    }
}
