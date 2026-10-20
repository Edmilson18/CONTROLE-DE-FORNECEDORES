<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveUser
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->ativo, 403, 'Sua conta está desativada.');

        if ($request->user()->password_setup_required && ! in_array($request->path(), [
            'api/auth/me', 'api/auth/password', 'api/auth/logout',
        ], true)) {
            abort(403, 'Defina sua senha no perfil para continuar.');
        }

        return $next($request);
    }
}
