<?php
namespace App\Http\Transformers;

class ReleaseNotesTransformer {
    public function transformReleaseNotes($releases, $total = null) {
        return [
            'total' => $total ?? count($releases),
            'rows' => $releases
        ];
    }
}