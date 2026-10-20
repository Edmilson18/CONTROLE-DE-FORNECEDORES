<?php

namespace App\Policies;

use App\Models\Fornecedor;
use App\Models\User;

class FornecedorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->ativo;
    }

    public function view(User $user, Fornecedor $fornecedor): bool
    {
        return $user->ativo;
    }

    public function create(User $user): bool
    {
        return $user->ativo && in_array($user->role, ['admin', 'operador'], true);
    }

    public function update(User $user, Fornecedor $fornecedor): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, Fornecedor $fornecedor): bool
    {
        return $user->ativo && $user->role === 'admin';
    }
}
