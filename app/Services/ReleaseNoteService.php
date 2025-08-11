<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ReleaseNoteService
{
    protected $baseUrl;
    protected $token;
    protected $apiVersion;
    protected $timeout;
    protected $verifySSL;
    protected $repositories;
    protected $supportedTypes = ['ALL', 'BE', 'FE'];

    public function __construct() {
        $this->baseUrl = config('github.api.base_url');
        $this->token = config('github.token');
        $this->apiVersion = config('github.api.version');
        $this->timeout = config('github.api.timeout');
        $this->verifySSL = config('github.verify_ssl');
        $this->repositories = config('github.repositories');
    }

    public function getAllReleaseNotes($type = 'ALL', $page = 1, $limit = null) {
        $page = max(1, (int) $page);
        $limit = $limit ? max(1, min(100, (int) $limit)) : 10;

        // Key for caching all releases by type, page, limit
        $cacheKey = "github_releases_{$type}_{$page}_{$limit}";

        $allReleases = [];

        // Fetch enough data to handle pagination properly
        // GitHub API default is 30, max is 100 per request
        $fetchLimit = min(100, max(50, $limit * 5)); // Get at least enough for 5 pages

        // Fetch releases by type
        if ($type === 'FE' || $type === 'ALL') {
            $frontend = $this->getReleaseNotes(
                $this->repositories['frontend']['owner'],
                $this->repositories['frontend']['repo'],
                $fetchLimit
            );

            foreach ($frontend as $release) {
                $release['type'] = 'FE';
                $allReleases[] = $release;
            }
        }

        if ($type === 'BE' || $type === 'ALL') {
            $backend = $this->getReleaseNotes(
                $this->repositories['backend']['owner'],
                $this->repositories['backend']['repo'],
                $fetchLimit
            );

            foreach ($backend as $release) {
                $release['type'] = 'BE';
                $allReleases[] = $release;
            }
        }

        // Sort releases by published date in descending order (newest first)
        usort($allReleases, function ($a, $b) {
            return strtotime($b['published_at']) - strtotime($a['published_at']);
        });

        // Apply pagination
        $total = count($allReleases);
        $offset = ($page - 1) * $limit;

        if ($offset >= $total && $total > 0) {
            $offset = 0;
            $page = 1;
        }

        $paginatedReleases = array_slice($allReleases, $offset, $limit);

        return [
            'releases' => $paginatedReleases,
            'total' => $total
        ];

        // return Cache::remember($cacheKey, now()->addHour(), function () use ($type, $page, $limit) {
            
        // });
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
            'X-Github-Api-Version' => $this->apiVersion,
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
            'timeout' => $this->timeout,
            'verify' => $this->verifySSL,
        ];

        // Create instance of HTTP client with options
        return Http::withOptions($options);
    }
}