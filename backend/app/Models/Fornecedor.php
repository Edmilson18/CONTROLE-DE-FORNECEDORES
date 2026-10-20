<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Fornecedor extends Model
{
    use SoftDeletes;

    protected $table = 'fornecedores';

    protected $fillable = ['nome', 'cnpj', 'telefone', 'cidade'];

    public function compras(): HasMany
    {
        return $this->hasMany(Compra::class, 'fornecedores_id');
    }
}
