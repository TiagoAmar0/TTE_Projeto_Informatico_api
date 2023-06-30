<?php

namespace App\Http\Middleware;

use App\Http\Enums\UserType;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    /**
     * Bloqueia o request caso o utilizador não seja admin
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     * @throws AuthorizationException
     */
    public function handle(Request $request, Closure $next): Response
    {
        if($request->user() && $request->user()->type == UserType::ADMIN->value)
            return $next($request);

        throw new AuthorizationException();
    }
}
