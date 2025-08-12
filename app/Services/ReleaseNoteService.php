<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ReleaseNoteService
{
    protected $baseUrl;
    protected $token;
    protected $apiAcceptFormat;
    protected $apiVersion;
    protected $timeout;
    protected $verifySSL;
    protected $repositories;
    protected $releaseNotesConfig;

    public function __construct() {
        $this->baseUrl = config('github.api.base_url');
        $this->token = config('github.token');
        $this->apiVersion = config('github.api.version');
        $this->apiAcceptFormat = config('github.api.accept_format');
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
        
        // Cache key by type, page, limit
        $cacheKey = $this->releaseNotesConfig['cache_key_prefix'] . "merged_{$type}_{$page}_{$limit}";

        $allReleases = Cache::remember(
            $cacheKey,
            now()->addHours($this->releaseNotesConfig['cache_duration_hours']),
            function () use ($type) {
                $releases = [];
                $fetchLimit = $this->releaseNotesConfig['fetch_limit_max'] ?? 300;

                Log::info("Using fetch limit for releases: {$fetchLimit}");

                try {
                    if ($type === 'FE' || $type === 'ALL') {
                        $frontend = $this->getReleaseNotes(
                            $this->repositories['frontend']['owner'],
                            $this->repositories['frontend']['repo'],
                            $fetchLimit
                        );
                        foreach ($frontend as $release) {
                            $release['type'] = 'FE';
                            $releases[] = $release;
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
                            $releases[] = $release;
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("Error fetching release notes: " . $e->getMessage());
                }

                // Sort by published date desc
                usort($releases, function ($a, $b) {
                    $dateA = isset($a['published_at']) ? $a['published_at'] : $a['created_at'];
                    $dateB = isset($b['published_at']) ? $b['published_at'] : $b['created_at'];
                    return strtotime($dateB) - strtotime($dateA);
                });

                Log::info("Total merged releases: " . count($releases));

                return $releases;
            }
        );

        // Apply pagination after getting all releases
        $total = count($allReleases);
        $offset = ($page - 1) * $limit;

        // If offset exceeds total, return last page
        if ($offset >= $total && $total > 0) {
            $page = ceil($total / $limit);
            $offset = ($page - 1) * $limit;
        }

        $paginatedReleases = array_slice($allReleases, $offset, $limit);

        return [
            'releases' => $paginatedReleases,
            'total' => $total,
        ];
    }

    public function getReleaseNotes($owner, $repo, $totalNeeded = null) {
        $totalNeeded = $totalNeeded ?: $this->releaseNotesConfig['default_page_size'];
        
        // Cache key by owner, repo, totalNeeded
        $cacheKey = $this->releaseNotesConfig['cache_key_prefix'] . "repo_{$owner}_{$repo}_{$totalNeeded}";

        return Cache::remember(
            $cacheKey,
            now()->addHours($this->releaseNotesConfig['cache_duration_hours']),
            function () use ($owner, $repo, $totalNeeded) {
                $allReleases = [];
                $page = 1;
                $perPage = 100;  // GitHub API max per page
                $header = $this->getHeaders();
                $url = "{$this->baseUrl}/repos/{$owner}/{$repo}/releases";

                Log::info("Fetching release notes from: {$url} (need {$totalNeeded} releases)");

                try {
                    $maxPages = $this->releaseNotesConfig['max_pages_to_fetch'] ?? 5;

                    while ($page <= $maxPages && count($allReleases) < $totalNeeded) {
                        Log::info("Fetching GitHub page {$page} for {$owner}/{$repo}");

                        try {
                            $response = $this->getHttpClient()
                                ->withHeaders($header)
                                ->get($url, [
                                    'per_page' => $perPage,
                                    'page' => $page
                                ]);

                            if ($response->failed()) {
                                Log::error('GitHub API error: ' . $response->status() . ' - ' . $response->body());
                                break;
                            }

                            $pageReleases = $response->json();

                            if (empty($pageReleases)) {
                                Log::info("No more releases found at page {$page}");
                                break;
                            }

                            $allReleases = array_merge($allReleases, $pageReleases);

                            Log::info("Got " . count($pageReleases) . " releases from page {$page} (total: " . count($allReleases) . ")");

                            // Stop when enough data is collected
                            if (count($allReleases) >= $totalNeeded) {
                                break;
                            }

                            // Stop when no more pages (last page returns less than perPage)
                            if (count($pageReleases) < $perPage) {
                                Log::info("Page {$page} returned less than {$perPage} releases, assuming last page");
                                break;
                            }

                            $page++;
                        } catch (\Exception $e) {
                            Log::error("Error fetching GitHub releases for {$owner}/{$repo} on page {$page}: " . $e->getMessage());
                            break;
                        }
                    }

                    if ($page > $maxPages) {
                        Log::warning("Reached maximum page limit ({$maxPages}) for {$owner}/{$repo}");
                    }

                    // Limit results as requested
                    if (count($allReleases) > $totalNeeded) {
                        $allReleases = array_slice($allReleases, 0, $totalNeeded);
                    }

                    Log::info("Total releases fetched for {$owner}/{$repo}: " . count($allReleases));
                } catch (\Exception $e) {
                    Log::error("Fatal error fetching GitHub releases for {$owner}/{$repo}: " . $e->getMessage());
                    $allReleases = [];
                }

                return $allReleases;
            }
        );
    }

    public function getHeaders() {
        // Prepare headers for GitHub API requests
        $header = [
            'Accept' => $this->apiAcceptFormat,
            'X-Github-Api-Version' => $this->apiVersion,
        ];

        // If a token is set, add it to the headers to upgrade github rate limit
        if ($this->token) {
            $header['Authorization'] = "Bearer {$this->token}";
        }

        return $header;
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