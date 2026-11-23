<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FileStreamController extends Controller
{
    public function stream(Request $request, string $type, string $filename): BinaryFileResponse
    {
        // Enforce valid types and sanitize filename to prevent traversal
        if (!in_array($type, ['reports', 'publications'], true)) {
            abort(404, 'Invalid file category.');
        }

        $filename = basename($filename);
        if (!preg_match('/^[A-Za-z0-9._-]+\.pdf$/i', $filename)) {
            abort(400, 'Invalid filename format.');
        }

        // Applicant proposals contain personal data: reviewers and staff only.
        if (str_starts_with($filename, 'grant_')) {
            $user = $request->user();
            abort_unless($user && $user->canReviewApplications(), 403, 'Proposal documents are restricted to the review committee.');
        }

        $filePath = "{$type}/{$filename}";

        if (!Storage::disk('private')->exists($filePath)) {
            abort(404, 'The requested document does not exist.');
        }

        // Log file access telemetry
        ActivityLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'file.download',
            'description' => "Downloaded document: {$filename} ({$type})",
            'ip_address' => $request->ip(),
        ]);

        $fullPath = Storage::disk('private')->path($filePath);
        $disposition = $request->has('download') ? 'attachment' : 'inline';

        return response()->file($fullPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "{$disposition}; filename=\"{$filename}\"",
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=0, must-revalidate',
        ]);
    }
}
