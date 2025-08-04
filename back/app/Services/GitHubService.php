<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

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
}
