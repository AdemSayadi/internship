<?php

namespace App\Http\Controllers;

use App\Models\CodeSubmission;
use App\Models\Repository;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CodeSubmissionController extends Controller
{
    /**
     * Display a listing of code submissions for the authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = auth()->user()->codeSubmissions()->with(['repository', 'reviews']);

            // Filter by repository if provided
            if ($request->has('repository_id')) {
                $query->where('repository_id', $request->repository_id);
            }

            // Filter by language if provided
            if ($request->has('language')) {
                $query->where('language', $request->language);
            }

            $submissions = $query->orderBy('created_at', 'desc')->paginate(15);

            return response()->json([
                'success' => true,
                'submissions' => $submissions
            ]);

        } catch (\Exception $e) {
            Log::error('Get code submissions error', [
                'message' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve code submissions'
            ], 500);
        }
    }

    /**
     * Store a newly created code submission
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'language' => 'required|string|max:50',
                'code_content' => 'required|string',
                'file_path' => 'nullable|string|max:500',
                'repository_id' => 'required|exists:repositories,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if repository belongs to the authenticated user
            $repository = Repository::where('id', $request->repository_id)
                ->where('user_id', auth()->id())
                ->first();

            if (!$repository) {
                return response()->json([
                    'success' => false,
                    'message' => 'Repository not found or unauthorized'
                ], 403);
            }

            $codeSubmission = CodeSubmission::create([
                'title' => $request->title,
                'language' => $request->language,
                'code_content' => $request->code_content,
                'file_path' => $request->file_path,
                'repository_id' => $request->repository_id,
                'user_id' => auth()->id(),
            ]);

            Log::info('Code submission created', [
                'submission_id' => $codeSubmission->id,
                'user_id' => auth()->id(),
                'repository_id' => $request->repository_id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Code submission created successfully',
                'submission' => $codeSubmission->load(['repository', 'user'])
            ], 201);

        } catch (\Exception $e) {
            Log::error('Create code submission error', [
                'message' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create code submission'
            ], 500);
        }
    }

    /**
     * Display the specified code submission
     */
    public function show(string $id): JsonResponse
    {
        try {
            $submission = CodeSubmission::with(['repository', 'user', 'reviews'])
                ->where('id', $id)
                ->where('user_id', auth()->id())
                ->first();

            if (!$submission) {
                return response()->json([
                    'success' => false,
                    'message' => 'Code submission not found or unauthorized'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'submission' => $submission
            ]);

        } catch (\Exception $e) {
            Log::error('Get code submission error', [
                'message' => $e->getMessage(),
                'submission_id' => $id,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve code submission'
            ], 500);
        }
    }

    /**
     * Update the specified code submission
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $submission = CodeSubmission::where('id', $id)
                ->where('user_id', auth()->id())
                ->first();

            if (!$submission) {
                return response()->json([
                    'success' => false,
                    'message' => 'Code submission not found or unauthorized'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|string|max:255',
                'language' => 'sometimes|string|max:50',
                'code_content' => 'sometimes|string',
                'file_path' => 'sometimes|nullable|string|max:500',
                'repository_id' => 'sometimes|exists:repositories,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // If repository_id is being updated, check ownership
            if ($request->has('repository_id')) {
                $repository = Repository::where('id', $request->repository_id)
                    ->where('user_id', auth()->id())
                    ->first();

                if (!$repository) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Repository not found or unauthorized'
                    ], 403);
                }
            }

            $submission->update($request->only([
                'title', 'language', 'code_content', 'file_path', 'repository_id'
            ]));

            Log::info('Code submission updated', [
                'submission_id' => $submission->id,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Code submission updated successfully',
                'submission' => $submission->fresh(['repository', 'user'])
            ]);

        } catch (\Exception $e) {
            Log::error('Update code submission error', [
                'message' => $e->getMessage(),
                'submission_id' => $id,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update code submission'
            ], 500);
        }
    }

    /**
     * Remove the specified code submission
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $submission = CodeSubmission::where('id', $id)
                ->where('user_id', auth()->id())
                ->first();

            if (!$submission) {
                return response()->json([
                    'success' => false,
                    'message' => 'Code submission not found or unauthorized'
                ], 404);
            }

            $submission->delete();

            Log::info('Code submission deleted', [
                'submission_id' => $id,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Code submission deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Delete code submission error', [
                'message' => $e->getMessage(),
                'submission_id' => $id,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete code submission'
            ], 500);
        }
    }

    /**
     * Get reviews for a specific code submission
     */
    public function reviews(string $id): JsonResponse
    {
        try {
            $submission = CodeSubmission::where('id', $id)
                ->where('user_id', auth()->id())
                ->first();

            if (!$submission) {
                return response()->json([
                    'success' => false,
                    'message' => 'Code submission not found or unauthorized'
                ], 404);
            }

            $reviews = $submission->reviews()
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'reviews' => $reviews
            ]);

        } catch (\Exception $e) {
            Log::error('Get submission reviews error', [
                'message' => $e->getMessage(),
                'submission_id' => $id,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve reviews'
            ], 500);
        }
    }
}
