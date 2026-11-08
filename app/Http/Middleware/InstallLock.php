<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InstallLock
{
    /**
     * Block access to the installer if the application is already installed.
     * Detection: presence of storage/installed.lock file.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $lockFile = storage_path('installed.lock');

        if (file_exists($lockFile)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Application is already installed.',
                ], 403);
            }

            return redirect('/dashboard');
        }

        return $next($request);
    }
}
