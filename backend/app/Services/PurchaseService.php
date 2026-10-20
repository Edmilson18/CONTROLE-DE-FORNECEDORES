<?php

namespace App\Services;

use App\Models\Compra;
use App\Models\Pagamento;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseService
{
    public function __construct(private AuditService $audit) {}

    public function save(array $data, ?Compra $purchase = null): Compra
    {
        return DB::transaction(function () use ($data, $purchase): Compra {
            $purchase = $purchase ? Compra::lockForUpdate()->findOrFail($purchase->id) : new Compra;
            $before = $purchase->attributesToArray();
            if ($purchase->exists && $purchase->legacyPaid()) {
                throw ValidationException::withMessages(['valor' => 'Compra quitada no legado sem histórico de pagamentos. Preserve o registro para conciliação.']);
            }
            $paid = $purchase->exists ? $purchase->paidCents() : 0;
            if (Money::cents((string) $data['valor']) < $paid) {
                throw ValidationException::withMessages(['valor' => 'O valor não pode ser menor que os pagamentos realizados.']);
            }
            if ($data['status'] === 'cancelado' && $paid > 0) {
                throw ValidationException::withMessages(['status' => 'Estorne os pagamentos antes de cancelar.']);
            }
            $data['status'] = $data['status'] === 'cancelado' ? 'cancelado' : ($paid === Money::cents((string) $data['valor']) ? 'pago' : 'pendente');
            $purchase->fill($data)->save();
            $this->audit->record($before ? 'atualizar' : 'criar', $purchase, $before);

            return $purchase->fresh(['fornecedor', 'pagamentos']);
        });
    }

    public function pay(Compra $purchase, array $data): Pagamento
    {
        return DB::transaction(function () use ($purchase, $data): Pagamento {
            $purchase = Compra::lockForUpdate()->findOrFail($purchase->id);
            if ($purchase->status !== 'pendente' || Money::cents((string) $data['valor']) > $purchase->balanceCents()) {
                throw ValidationException::withMessages(['valor' => 'Pagamento excede o saldo ou a compra não está pendente.']);
            }
            $before = $purchase->attributesToArray();
            $payment = $purchase->pagamentos()->create($data);
            $purchase->unsetRelation('pagamentos');
            $purchase->status = $purchase->balanceCents() === 0 ? 'pago' : 'pendente';
            $purchase->save();
            $this->audit->record('pagar', $payment);
            $this->audit->record('atualizar', $purchase, $before);

            return $payment;
        });
    }

    public function reverse(Pagamento $payment, string $reason): void
    {
        DB::transaction(function () use ($payment, $reason): void {
            $purchase = Compra::lockForUpdate()->findOrFail($payment->compra_id);
            $payment = Pagamento::lockForUpdate()->findOrFail($payment->id);
            if ($payment->estornado_em) {
                throw ValidationException::withMessages(['motivo' => 'Este pagamento já foi estornado.']);
            }
            $before = $payment->attributesToArray();
            $payment->update(['estornado_em' => now(), 'motivo_estorno' => $reason]);
            $purchaseBefore = $purchase->attributesToArray();
            $purchase->update(['status' => 'pendente']);
            $this->audit->record('estornar', $payment, $before);
            $this->audit->record('atualizar', $purchase, $purchaseBefore);
        });
    }

    public function delete(Compra $purchase): void
    {
        DB::transaction(function () use ($purchase): void {
            $purchase = Compra::lockForUpdate()->findOrFail($purchase->id);
            if ($purchase->pagamentos()->exists() || $purchase->status === 'pago') {
                throw ValidationException::withMessages(['compra' => 'Compras com histórico financeiro não podem ser excluídas. Use o cancelamento após os estornos.']);
            }
            $before = $purchase->attributesToArray();
            $purchase->delete();
            $this->audit->record('excluir', $purchase, $before);
        });
    }
}
