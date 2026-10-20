<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Compra extends Model
{
    use SoftDeletes;

    protected $table = 'compras';

    protected $fillable = ['fornecedores_id', 'data', 'valor', 'status', 'vencimento', 'descricao'];

    protected function casts(): array
    {
        return ['data' => 'date:Y-m-d', 'vencimento' => 'date:Y-m-d', 'valor' => 'decimal:2'];
    }

    public function fornecedor(): BelongsTo
    {
        return $this->belongsTo(Fornecedor::class, 'fornecedores_id')->withTrashed();
    }

    public function pagamentos(): HasMany
    {
        return $this->hasMany(Pagamento::class);
    }

    public function paidCents(): int
    {
        return $this->pagamentos->whereNull('estornado_em')->sum(fn (Pagamento $payment): int => Money::cents($payment->valor));
    }

    public function legacyPaid(): bool
    {
        return $this->status === 'pago' && $this->pagamentos->isEmpty();
    }

    public function balanceCents(): int
    {
        return $this->status === 'cancelado' || $this->legacyPaid() ? 0 : max(0, Money::cents($this->valor) - $this->paidCents());
    }
}
