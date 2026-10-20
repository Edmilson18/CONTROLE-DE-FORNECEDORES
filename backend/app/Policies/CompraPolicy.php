<?php

namespace App\Policies;

use App\Models\Compra;
use App\Models\User;

class CompraPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->ativo;
    }

    public function view(User $user, Compra $compra): bool
    {
        return $user->ativo;
    }

    public function create(User $user): bool
    {
        return $user->ativo && in_array($user->role, ['admin', 'operador'], true);
    }

    public function update(User $user, Compra $compra): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, Compra $compra): bool
    {
        return $user->ativo && $user->role === 'admin';
    }
}
