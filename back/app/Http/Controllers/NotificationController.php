<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    /**
     * Display a listing of notifications for the authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = auth()->user()->notifications()->with('review.codeSubmission');

            // Filter by read status if provided
            if ($request->has('read')) {
                $query->where('read', $request->boolean('read'));
            }

            $notifications = $query->orderBy('created_at', 'desc')->paginate(20);

            return response()->json([
                'success' => true,
                'notifications' => $notifications
            ]);

        } catch (\Exception $e) {
            Log::error('Get notifications error', [
                'message' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve notifications'
            ], 500);
        }
    }

    /**
     * Store a newly created notification
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'review_id' => 'required|exists:reviews,id',
                'message' => 'required|string|max:500',
            ]);

            // Verify the review belongs to the authenticated user
            $reviewExists = \App\Models\Review::whereHas('codeSubmission', function ($q) {
                $q->where('user_id', auth()->id());
            })->where('id', $request->review_id)->exists();

            if (!$reviewExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Review not found or unauthorized'
                ], 404);
            }

            $notification = Notification::create([
                'user_id' => auth()->id(),
                'review_id' => $request->review_id,
                'message' => $request->message,
                'read' => false,
            ]);

            Log::info('Notification created', [
                'notification_id' => $notification->id,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Notification created successfully',
                'notification' => $notification->load('review')
            ], 201);

        } catch (\Exception $e) {
            Log::error('Create notification error', [
                'message' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create notification'
            ], 500);
        }
    }

    /**
     * Display the specified notification
     */
    public function show(string $id): JsonResponse
    {
        try {
            $notification = Notification::with(['review.codeSubmission'])
                ->where('id', $id)
                ->where('user_id', auth()->id())
                ->first();

            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found or unauthorized'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'notification' => $notification
            ]);

        } catch (\Exception $e) {
            Log::error('Get notification error', [
                'message' => $e->getMessage(),
                'notification_id' => $id,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve notification'
            ], 500);
        }
    }

    /**
     * Update the specified notification (mainly for marking as read)
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $notification = Notification::where('id', $id)
                ->where('user_id', auth()->id())
                ->first();

            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found or unauthorized'
                ], 404);
            }

            $request->validate([
                'read' => 'sometimes|boolean',
                'message' => 'sometimes|string|max:500',
            ]);

            $notification->update($request->only(['read', 'message']));

            Log::info('Notification updated', [
                'notification_id' => $notification->id,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Notification updated successfully',
                'notification' => $notification->fresh(['review'])
            ]);

        } catch (\Exception $e) {
            Log::error('Update notification error', [
                'message' => $e->getMessage(),
                'notification_id' => $id,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update notification'
            ], 500);
        }
    }

    /**
     * Remove the specified notification
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $notification = Notification::where('id', $id)
                ->where('user_id', auth()->id())
                ->first();

            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found or unauthorized'
                ], 404);
            }

            $notification->delete();

            Log::info('Notification deleted', [
                'notification_id' => $id,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Notification deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Delete notification error', [
                'message' => $e->getMessage(),
                'notification_id' => $id,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete notification'
            ], 500);
        }
    }

    /**
     * Mark all notifications as read for the authenticated user
     */
    public function markAllAsRead(): JsonResponse
    {
        try {
            $updatedCount = auth()->user()->notifications()
                ->where('read', false)
                ->update(['read' => true]);

            Log::info('All notifications marked as read', [
                'user_id' => auth()->id(),
                'updated_count' => $updatedCount
            ]);

            return response()->json([
                'success' => true,
                'message' => "Marked {$updatedCount} notifications as read"
            ]);

        } catch (\Exception $e) {
            Log::error('Mark all as read error', [
                'message' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark notifications as read'
            ], 500);
        }
    }

    /**
     * Get unread notification count for the authenticated user
     */
    public function unreadCount(): JsonResponse
    {
        try {
            $count = auth()->user()->notifications()
                ->where('read', false)
                ->count();

            return response()->json([
                'success' => true,
                'unread_count' => $count
            ]);

        } catch (\Exception $e) {
            Log::error('Get unread count error', [
                'message' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get unread count'
            ], 500);
        }
    }

    /**
     * Delete all read notifications for the authenticated user
     */
    public function clearRead(): JsonResponse
    {
        try {
            $deletedCount = auth()->user()->notifications()
                ->where('read', true)
                ->delete();

            Log::info('Read notifications cleared', [
                'user_id' => auth()->id(),
                'deleted_count' => $deletedCount
            ]);

            return response()->json([
                'success' => true,
                'message' => "Deleted {$deletedCount} read notifications"
            ]);

        } catch (\Exception $e) {
            Log::error('Clear read notifications error', [
                'message' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to clear read notifications'
            ], 500);
        }
    }
}
