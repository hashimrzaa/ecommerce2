<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureStaff
{
    public function handle(Request $request, Closure $next, ?string $section = null)
    {
        $user = $request->user();
        if (! $user || ! $user->isStaff()) {
            abort(403, 'This area is for store staff only.');
        }
        if ($section && ! $user->canAccess($section)) {
            abort(403, 'Your role does not have access to this section.');
        }

        return $next($request);
    }
}
