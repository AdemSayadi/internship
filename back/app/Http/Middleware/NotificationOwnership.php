<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NotificationOwnership
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip for routes that don't have notification ID
        if (!$request->route('notification')) {
            return $next($request);
        }

        $notificationId = $request->route('notification');
        $userId = auth()->id();

        // Check if the notification belongs to the authenticated user
        $notification = \App\Models\Notification::where('id', $notificationId)
            ->where('user_id', $userId)
            ->first();

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found or unauthorized'
            ], 404);
        }

        // Add the notification to the request for use in the controller
        $request->merge(['notification_instance' => $notification]);

        return $next($request);
    }
}
