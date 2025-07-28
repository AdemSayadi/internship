<?php

namespace App\Http\Controllers;

use App\Models\Repository;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RepositoryController extends Controller
{
    public function index(): JsonResponse
    {
        $repositories = auth()->user()->repositories()->with('codeSubmissions')->get();

        return response()->json([
            'repositories' => $repositories
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'nullable|url',
            'provider' => 'required|in:github,gitlab,manual',
        ]);

        $repository = Repository::create([
            'name' => $request->name,
            'url' => $request->url,
            'provider' => $request->provider,
            'user_id' => auth()->id(),
        ]);

        return response()->json([
            'repository' => $repository->load('codeSubmissions')
        ], 201);
    }

    public function show(Repository $repository): JsonResponse
    {
        if ($repository->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'repository' => $repository->load(['codeSubmissions.reviews'])
        ]);
    }

    public function update(Request $request, Repository $repository): JsonResponse
    {
        if ($repository->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'url' => 'sometimes|nullable|url',
            'provider' => 'sometimes|in:github,gitlab,manual',
        ]);

        $repository->update($request->only(['name', 'url', 'provider']));

        return response()->json([
            'repository' => $repository->fresh()
        ]);
    }

    public function destroy(Repository $repository): JsonResponse
    {
        if ($repository->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $repository->delete();

        return response()->json([
            'message' => 'Repository deleted successfully'
        ]);
    }

    public function submissions(Repository $repository): JsonResponse
    {
        if ($repository->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $submissions = $repository->codeSubmissions()
            ->with(['reviews', 'user'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'submissions' => $submissions
        ]);
    }
}
