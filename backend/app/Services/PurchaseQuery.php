<?php

namespace App\Services;

use App\Models\Compra;
use Illuminate\Database\Eloquent\Builder;

class PurchaseQuery
{
    public function filtered(array $filters): Builder
    {
        return Compra::with(['fornecedor', 'pagamentos'])
            ->when($filters['q'] ?? null, fn (Builder $q, string $term) => $q->where(function (Builder $q) use ($term): void {
                $q->where('descricao', 'like', '%'.$term.'%')->orWhereHas('fornecedor', fn (Builder $f) => $f->where('nome', 'like', '%'.$term.'%'));
            }))
            ->when($filters['status'] ?? null, fn (Builder $q, string $status) => $q->where('status', $status))
            ->when($filters['fornecedor_id'] ?? null, fn (Builder $q, int $id) => $q->where('fornecedores_id', $id))
            ->when($filters['de'] ?? null, fn (Builder $q, string $date) => $q->where('data', '>=', $date))
            ->when($filters['ate'] ?? null, fn (Builder $q, string $date) => $q->where('data', '<=', $date));
    }
}
