<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Transformers\ReleaseNotesTransformer;
use App\Services\ReleaseNoteService;
use Codeception\Util\HttpCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ReleaseNotesController extends Controller
{
    protected $releaseNoteService;
    protected $releaseNotesTransformer;

    public function __construct(
        ReleaseNoteService $releaseNoteService,
        ReleaseNotesTransformer $releaseNotesTransformer
    ) {
        $this->releaseNoteService = $releaseNoteService;
        $this->releaseNotesTransformer = $releaseNotesTransformer;
    }

    public function releasesNotes(Request $request) {
        $this->authorize('view', ReleaseNoteService::class);

        Log::info('Release notes endpoint called');

        try {
            // Validate request parameters
            $validated = $request->validate([
                'type' => ['sometimes', 'string', Rule::in(['ALL', 'BE', 'FE', 'all', 'be', 'fe'])],
                'page' => ['sometimes', 'integer', 'min:1'],
                'pageSize' => ['sometimes', 'integer', 'min:1', 'max:100'],
                'limit' => ['sometimes', 'integer', 'min:1', 'max:100'], // for backward compatibility
            ]);

            // Get with defaults - prioritize pageSize from frontend
            $type = strtoupper($validated['type'] ?? 'ALL');
            $page = (int) ($validated['page'] ?? 1);
            $pageSize = (int) ($validated['pageSize'] ?? $validated['limit'] ?? 10);

            Log::info("Release notes request", [
                'type' => $type,
                'page' => $page,
                'pageSize' => $pageSize,
            ]);

            $result = $this->releaseNoteService->getAllReleaseNotes($type, $page, $pageSize);

            Log::info('Release notes retrieved successfully', [
                'total' => $result['total'],
                'releases_count' => count($result['releases']),
            ]);

            return response()->json(
                $this->releaseNotesTransformer->transformReleaseNotes($result['releases'], $result['total'])
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Validation error in release notes: ', $e->errors());
            return response()->json(['error' => 'Invalid parameters'], HttpCode::UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            Log::error('Error retrieving release notes: ' . $e->getMessage());
            return response()->json(['error' => 'Internal server error'], HttpCode::INTERNAL_SERVER_ERROR);
        }
    }
}