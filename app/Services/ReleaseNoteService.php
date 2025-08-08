<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ReleaseNoteService
{
    protected $baseUrl = 'https://api.github.com';
    protected $token;

    public function __construct() {
        $this->token = config('github.token');
    }

    public function getAllReleaseNotes($perPage = 30) {
        // Key for caching all releases in perPage
        $cacheKey = "github_all_releases_{$perPage}";

        return Cache::remember($cacheKey, now()->addHour(), function () use ($perPage) {
            $frontend = $this->getReleaseNotes('ncc-erp', 'ncc-erp-ams-fe', $perPage);
            $backend = $this->getReleaseNotes('ncc-erp', 'ncc-erp-ams', $perPage);

            Log::info($frontend);
            Log::info($backend);

            // Merge all release notes from frontend and backend
            $allReleases = [];
            foreach ($frontend as $release) {
                $allReleases[] = $release;
            }
            foreach ($backend as $release) {
                $allReleases[] = $release;
            }

            // Sort releases by published date in descending order (newest first)
            usort($allReleases, function ($a, $b) {
                return strtotime($b['published_at']) - strtotime($a['published_at']);
            });

            return $allReleases;
        });
    }

    public function getReleaseNotes($owner, $repo, $perPage = 10) {
        $cacheKey = "github_releases_{$owner}_{$repo}_{$perPage}";

        return Cache::remember($cacheKey, now()->addHour(), function () use ($owner, $repo, $perPage) {
            $header = $this->getHeaders();
            $url = "{$this->baseUrl}/repos/{$owner}/{$repo}/releases";
            Log::info("Fetching release notes from: {$url} with perPage: {$perPage}");
            
            $response = $this->getHttpClient()
                ->withHeaders($header)  // Add headers for the request
                ->get($url, [   // Send GET request to GitHub API & Query params
                    'per_page' => $perPage,
                ]);

            if ($response->failed()) {
                Log::error('GitHub API error: ' . $response->body());
                return [];
            }

            $releaseNotes = $response->json();

            return $releaseNotes;
        });
    }

    public function getHeaders() {
        // Prepare headers for GitHub API requests
        $header = [
            'Accept' => 'application/vnd.github.v3+json',
            'X-Github-Api-Version' => '2022-11-28',
        ];

        // If a token is set, add it to the headers to upgrade github rate limit
        if ($this->token) {
            $header['Authorization'] = "Bearer {$this->token}";
        }

        return $header;
    }

    public function clearCache($owner = null, $repo = null, $perPage = 30) {
        // Clear the cache for release notes
        if ($owner && $repo) {
            $cacheKey = "github_releases_{$owner}_{$repo}_{$perPage}";
            Cache::forget($cacheKey);
        } else {
            $cacheKey = "github_all_releases_{$perPage}";
            Cache::forget($cacheKey);
        }
    }

    private function getHttpClient() {
        $options = [
            'timeout' => 30,
            'verify' => false,  // TODO:: Set to true in production
        ];

        // Create instance of HTTP client with options
        return Http::withOptions($options);
    }
}