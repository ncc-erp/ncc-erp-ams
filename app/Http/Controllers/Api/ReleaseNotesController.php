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
            // Get with defaults & sanitized - prioritize pageSize from frontend
            $type = $this->getValidReleaseType($request->get('type'));
            $page = $this->getValidPage($request->get('page'));
            $pageSize = $this->getValidPageSize(
                $request->get('pageSize') ?: $request->get('limit')
            );

            Log::info("Release notes request processed", [
                'original' => [
                    'type' => $request->get('type'),
                    'page' => $request->get('page'),
                    'pageSize' => $request->get('pageSize'),
                    'limit' => $request->get('limit'),
                ],
                'sanitized' => [
                    'type' => $type,
                    'page' => $page,
                    'pageSize' => $pageSize,
                ]
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
            Log::warning('Validation error in release notes', [
                'errors' => $e->errors(),
                'request_params' => $request->all()
            ]);
            return response()->json([
                'error' => 'Invalid request parameters', 
                'details' => $e->errors()
            ], HttpCode::UNPROCESSABLE_ENTITY);

        } catch (\Exception $e) {
            Log::error('Error retrieving release notes', [
                'message' => $e->getMessage(),
                'request_params' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Internal server error'
            ], HttpCode::INTERNAL_SERVER_ERROR);
        }
    }

    private function getValidReleaseType($type) {
        $defaultType = 'ALL';
        $validTypes = ['ALL', 'BE', 'FE'];

        if (
            is_null($type) ||
            !is_string($type) ||
            trim($type) === '' ||
            in_array(strtolower(trim($type)), ['null', 'undefined'], true)
        ) {
            return $defaultType;
        }
        
        $normalizedType = strtoupper(trim($type));

        return in_array($normalizedType, $validTypes) ? $normalizedType : $defaultType;
    }

    private function getValidPage($page) {
        $defaultPage = 1;

        // Handle null, empty, 'null', 'undefined' 
        if (
            is_null($page) || 
            $page === '' ||
            in_array(strtolower(trim((string)$page)), ['null', 'undefined'], true)
        ) {
            return $defaultPage;
        }

        // Handle non-numeric strings (like 'abc', '1.5abc', etc.)
        if (!is_numeric($page)) {
            return $defaultPage;
        }

        $intPage = (int) $page;

        return ($intPage > 0) ? $intPage : $defaultPage;
    }

    private function getValidPageSize($pageSize) {
        $defaultPageSize = 10;
        $maxPageSize = 50;
        $minPageSize = 1;

        // Handle null, empty, 'null', 'undefined'
        if (
            is_null($pageSize) || 
            $pageSize === '' || 
            in_array(strtolower(trim((string)$pageSize)), ['null', 'undefined'], true)
        ) {
            return $defaultPageSize;
        }

        // Handle non-numeric strings
        if (!is_numeric($pageSize)) {
            return $defaultPageSize;
        }

        $intPageSize = (int) $pageSize;

        if ($intPageSize < $minPageSize) {
            return $defaultPageSize;
        }

        if ($intPageSize > $maxPageSize) {
            return $maxPageSize;
        }

        return $intPageSize;
    }
}