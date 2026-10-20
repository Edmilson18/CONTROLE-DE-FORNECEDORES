<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pagamento extends Model
{
    protected $table = 'pagamentos';

    protected $fillable = ['valor', 'data', 'estornado_em', 'motivo_estorno'];

    protected function casts(): array
    {
        return ['valor' => 'decimal:2', 'data' => 'date:Y-m-d', 'estornado_em' => 'datetime'];
    }

    public function compra(): BelongsTo
    {
        return $this->belongsTo(Compra::class)->withTrashed();
    }
}
