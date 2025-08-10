<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GitHubService
{
    public function getUserRepos(string $token): array
    {
        $response = Http::withToken($token)
            ->get('https://api.github.com/user/repos', [
                'visibility' => 'all',
                'affiliation' => 'owner,collaborator',
                'per_page' => 100,
            ]);

        if ($response->successful()) {
            return $response->json();
        }

        return [];
    }

    /**
     * Get files changed in a pull request
     */
    public function getPullRequestFiles(string $token, string $repoFullName, int $prNumber): array
    {
        try {
            $response = Http::withToken($token)
                ->get("https://api.github.com/repos/{$repoFullName}/pulls/{$prNumber}/files");

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Failed to fetch PR files', [
                'repo' => $repoFullName,
                'pr_number' => $prNumber,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [];

        } catch (\Exception $e) {
            Log::error('Exception fetching PR files', [
                'repo' => $repoFullName,
                'pr_number' => $prNumber,
                'error' => $e->getMessage()
            ]);

            return [];
        }
    }

    /**
     * Get pull request details
     */
    public function getPullRequest(string $token, string $repoFullName, int $prNumber): ?array
    {
        try {
            $response = Http::withToken($token)
                ->get("https://api.github.com/repos/{$repoFullName}/pulls/{$prNumber}");

            if ($response->successful()) {
                return $response->json();
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Exception fetching PR details', [
                'repo' => $repoFullName,
                'pr_number' => $prNumber,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Get file contents from GitHub
     */
    public function getFileContents(string $token, string $repoFullName, string $path, string $ref = 'main'): ?string
    {
        try {
            $response = Http::withToken($token)
                ->get("https://api.github.com/repos/{$repoFullName}/contents/{$path}", [
                    'ref' => $ref
                ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['content'])) {
                    return base64_decode($data['content']);
                }
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Exception fetching file contents', [
                'repo' => $repoFullName,
                'path' => $path,
                'ref' => $ref,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Create a webhook for a repository
     */
    public function createWebhook(string $token, string $repoFullName, string $webhookUrl, string $secret = null): ?array
    {
        try {
            $config = [
                'url' => $webhookUrl,
                'content_type' => 'json',
                'insecure_ssl' => '0'
            ];

            if ($secret) {
                $config['secret'] = $secret;
            }

            $response = Http::withToken($token)
                ->post("https://api.github.com/repos/{$repoFullName}/hooks", [
                    'name' => 'web',
                    'active' => true,
                    'events' => ['pull_request', 'push'],
                    'config' => $config
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Failed to create webhook', [
                'repo' => $repoFullName,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('Exception creating webhook', [
                'repo' => $repoFullName,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * List webhooks for a repository
     */
    public function listWebhooks(string $token, string $repoFullName): array
    {
        try {
            $response = Http::withToken($token)
                ->get("https://api.github.com/repos/{$repoFullName}/hooks");

            if ($response->successful()) {
                return $response->json();
            }

            return [];

        } catch (\Exception $e) {
            Log::error('Exception listing webhooks', [
                'repo' => $repoFullName,
                'error' => $e->getMessage()
            ]);

            return [];
        }
    }

    /**
     * Delete a webhook
     */
    public function deleteWebhook(string $token, string $repoFullName, int $hookId): bool
    {
        try {
            $response = Http::withToken($token)
                ->delete("https://api.github.com/repos/{$repoFullName}/hooks/{$hookId}");

            return $response->successful();

        } catch (\Exception $e) {
            Log::error('Exception deleting webhook', [
                'repo' => $repoFullName,
                'hook_id' => $hookId,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Get repository pull requests
     */
    public function getRepositoryPullRequests(string $token, string $repoFullName, string $state = 'open'): array
    {
        try {
            $response = Http::withToken($token)
                ->get("https://api.github.com/repos/{$repoFullName}/pulls", [
                    'state' => $state,
                    'sort' => 'updated',
                    'direction' => 'desc',
                    'per_page' => 100
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            return [];

        } catch (\Exception $e) {
            Log::error('Exception fetching repository PRs', [
                'repo' => $repoFullName,
                'error' => $e->getMessage()
            ]);

            return [];
        }
    }

    /**
     * Post a review comment on a pull request
     */
    public function createReviewComment(string $token, string $repoFullName, int $prNumber, array $comment): ?array
    {
        try {
            $response = Http::withToken($token)
                ->post("https://api.github.com/repos/{$repoFullName}/pulls/{$prNumber}/comments", $comment);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Failed to create review comment', [
                'repo' => $repoFullName,
                'pr_number' => $prNumber,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('Exception creating review comment', [
                'repo' => $repoFullName,
                'pr_number' => $prNumber,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }
}
