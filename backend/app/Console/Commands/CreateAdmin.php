<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdmin extends Command
{
    protected $signature = 'app:admin {email?} {--name=}';
    protected $description = 'Cria um administrador com senha informada de forma oculta';

    public function handle(AuditService $audit): int
    {
        $data = ['email' => $this->argument('email') ?? $this->ask('E-mail'),
            'name' => $this->option('name') ?: $this->ask('Nome'),
            'password' => $this->secret('Senha (mínimo 12 caracteres, maiúscula, minúscula e número)')];
        $validator = Validator::make($data, [
            'email' => 'required|email|unique:users,email', 'name' => 'required|string|max:200',
            'password' => ['required', 'max:128', Password::min(12)->mixedCase()->numbers()],
        ]);
        if ($validator->fails()) {
            $this->error($validator->errors()->first());
            return self::FAILURE;
        }
        $user = User::create([...$data, 'role' => 'admin', 'ativo' => true]);
        $audit->record('criar', $user);
        $this->info('Administrador criado.');
        return self::SUCCESS;
    }
}
