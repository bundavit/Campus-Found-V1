<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $isStaffSession = $request->session()->get('is_admin') === true;
        $user = auth()->user();
        $isAdminUser = $user?->isAdmin() === true
            && $user?->status === 'active';

        if (! $isStaffSession && ! $isAdminUser) {
            return redirect()
                ->route('admin.login')
                ->with('error', 'Please log in as admin first.');
        }

        return $next($request);
    }
}
