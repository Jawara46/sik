<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\AnnouncementService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class CheckAnnouncementDate
{
    public function __construct(
        private readonly AnnouncementService $announcementService,
    ) {
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Don't intercept admin routes (including subdirectory deployments like /sik/public/admin/*)
        if ($request->is('admin*') || Str::contains($request->path(), 'admin')) {
            return $next($request);
        }

        $announcementDate = $this->announcementService->getAnnouncementAt();
        if ($announcementDate !== null) {
            if (now()->lessThan($announcementDate)) {
                // Determine if we are on the countdown page itself
                // To avoid infinite redirect loop
                if (!$request->is('countdown')) {
                    return redirect()->route('countdown');
                }
            } else {
                // If past the date and requesting countdown, redirect to home
                if ($request->is('countdown')) {
                    return redirect()->route('login');
                }
            }
        }

        return $next($request);
    }
}
