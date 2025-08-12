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
    protected $releaseNotesConfig;

    public function __construct() {
        $this->baseUrl = config('github.api.base_url');
        $this->token = config('github.token');
        $this->apiVersion = config('github.api.version');
        $this->timeout = config('github.api.timeout');
        $this->verifySSL = config('github.verify_ssl');
        $this->repositories = config('github.repositories');
        $this->releaseNotesConfig = config('enum.release_notes');
    }

    public function getAllReleaseNotes($type = null, $page = null, $limit = null) {
        // Use config defaults if not provided
        $type = $type ?: $this->releaseNotesConfig['default_type'];
        $page = max(1, (int) ($page ?: $this->releaseNotesConfig['default_page']));
        $limit = $limit ? max(
            $this->releaseNotesConfig['min_page_size'], 
            min($this->releaseNotesConfig['max_page_size'], (int) $limit)
        ) : $this->releaseNotesConfig['default_page_size'];
        
        // Key for caching all releases by type, page, limit
        $cacheKey = "github_releases_{$type}_{$page}_{$limit}";
        
        return Cache::remember($cacheKey, now()->addHour(), function () use ($type, $page, $limit) {
            $allReleases = [];

            // Calculate fetch limit based on config
            $fetchLimit = min(
                $this->releaseNotesConfig['fetch_limit_max'],
                max(
                    $this->releaseNotesConfig['fetch_limit_min'],
                    $limit * $this->releaseNotesConfig['fetch_limit_multiplier']
                )
            );

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
        });
    }

    public function getReleaseNotes($owner, $repo, $perPage = null) {
        $perPage = $perPage ?: $this->releaseNotesConfig['default_page_size'];
        $cacheKey = "github_releases_{$owner}_{$repo}_{$perPage}";

        return Cache::remember($cacheKey, now()->addHour(), function () use ($owner, $repo, $perPage) {
            $header = $this->getHeaders();
            $url = "{$this->baseUrl}/repos/{$owner}/{$repo}/releases";
            Log::info("Fetching release notes from: {$url} with perPage: {$perPage}");
            
            $response = $this->getHttpClient()
                ->withHeaders($header)
                ->get($url, [
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

    public function clearCache($owner = null, $repo = null, $perPage = null) {
        $perPage = $perPage ?: $this->releaseNotesConfig['default_page_size'];
        
        // Clear the cache for release notes
        if ($owner && $repo) {
            $cacheKey = "github_releases_{$owner}_{$repo}_{$perPage}";
            Cache::forget($cacheKey);
        } else {
            $cacheKey = "github_all_releases_{$perPage}";
            Cache::forget($cacheKey);
        }
    }

    public function getSupportedTypes() {
        return $this->releaseNotesConfig['valid_types'];
    }

    private function getHttpClient() {
        $options = [
            'timeout' => $this->timeout,
            'verify' => $this->verifySSL,
        ];

        return Http::withOptions($options);
    }
}