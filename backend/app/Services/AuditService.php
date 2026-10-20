<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class AuditService
{
    public function record(string $action, Model $model, array $before = []): void
    {
        $allowed = ['id', 'nome', 'cnpj', 'telefone', 'cidade', 'data', 'valor', 'status', 'vencimento',
            'descricao', 'fornecedores_id', 'compra_id', 'estornado_em', 'motivo_estorno', 'deleted_at', 'name', 'email', 'role', 'ativo'];
        AuditLog::create([
            'user_id' => auth()->id(), 'acao' => $action, 'entidade' => $model->getTable(),
            'entidade_id' => $model->getKey(), 'antes' => Arr::only($before, $allowed),
            'depois' => Arr::only($model->attributesToArray(), $allowed),
        ]);
    }
}
