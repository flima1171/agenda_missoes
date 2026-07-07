<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restringe rotas administrativas (gestão de militares e de usuários) a
 * quem tem is_admin. Usuário comum gere missões, mas não a base de pessoas.
 */
class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! $request->user()->is_admin) {
            abort(403, 'Acesso restrito a administradores.');
        }

        return $next($request);
    }
}
