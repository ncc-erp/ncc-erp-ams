<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Services\ReleaseNoteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReleaseNotesController extends Controller
{
    protected $releaseNoteService;

    public function __construct(
        ReleaseNoteService $releaseNoteService
    ) {
        $this->releaseNoteService = $releaseNoteService;
    }

    public function releasesNotes(Request $request) {
        $this->authorize('view', ReleaseNoteService::class);

        Log::info('Release notes endpoint called');

        try {
            $perPage = $request->input('per_page', 30);

            $releaseNotes = $this->releaseNoteService->getAllReleaseNotes($perPage);

            return response()->json(
                $releaseNotes
            );
        } catch (\Exception $e) {
            Log::error('Error retrieving release notes: ' . $e->getMessage());
            return response()->json([]);
        }
    }
}