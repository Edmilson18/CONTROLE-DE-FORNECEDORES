<?php

namespace App\Http\Controllers;

use App\Models\AccountAccessRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        if (! Auth::guard('web')->attempt([...$data, 'ativo' => true])) {
            throw ValidationException::withMessages(['email' => 'E-mail ou senha incorretos.']);
        }
        $request->session()->regenerate();

        return response()->json(['data' => $request->user()]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Sessão encerrada.']);
    }

    public function password(Request $request): JsonResponse
    {
        $user = $request->user();
        $passwordRules = ['required', 'max:128', Password::min(8)->mixedCase()->numbers()];
        if (! $user->password_setup_required) {
            $passwordRules[] = 'confirmed';
        }
        $data = $request->validate([
            'current_password' => $user->password_setup_required
                ? ['nullable', 'string']
                : ['required', 'current_password:web'],
            'password' => $passwordRules,
        ]);

        DB::transaction(function () use ($user, $data): void {
            $wasPending = $user->password_setup_required;
            $user->update(['password' => $data['password'], 'password_setup_required' => false]);
            if ($wasPending) {
                AccountAccessRequest::query()->where('email', $user->email)->where('status', 'approved')->update([
                    'status' => 'completed',
                    'setup_token_hash' => null,
                    'setup_token_expires_at' => null,
                    'completed_at' => now(),
                ]);
            }
        });
        $request->session()->regenerate();

        return response()->json(['message' => 'Senha atualizada.']);
    }
}
