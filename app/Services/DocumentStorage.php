<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Stores and serves uploaded PDF documents.
 *
 * - Files never live under public/; they are streamed through controllers
 *   that run authorization first.
 * - Stored names are generated server-side, so client filenames can never
 *   cause path traversal or overwrite another file.
 * - The disk is configurable (hub.documents_disk), so the same code works
 *   with local storage or any S3-compatible bucket.
 *
 * Paths are stored relative to the disk root, e.g. "reports/project_ab12.pdf".
 * Legacy rows that stored only a filename are resolved against their folder.
 */
final class DocumentStorage
{
    public const REPORTS = 'reports';

    public const PUBLICATIONS = 'publications';

    public const PROPOSALS = 'proposals';

    /** Older releases stored grant proposals alongside project reports. */
    private const LEGACY_FOLDERS = [self::PROPOSALS => self::REPORTS];

    public function disk(): FilesystemAdapter
    {
        /** @var FilesystemAdapter */
        return Storage::disk(config('hub.documents_disk'));
    }

    public function store(UploadedFile $file, string $folder, string $prefix): string
    {
        $name = $prefix.'_'.now()->format('Ymd').'_'.Str::lower(Str::random(16)).'.pdf';

        $this->disk()->putFileAs($folder, $file, $name);

        return "{$folder}/{$name}";
    }

    public function replace(?string $existing, UploadedFile $file, string $folder, string $prefix): string
    {
        $path = $this->store($file, $folder, $prefix);

        if ($existing) {
            $this->delete($existing, $folder);
        }

        return $path;
    }

    public function delete(?string $path, string $folder): void
    {
        if ($resolved = $this->resolve($path, $folder)) {
            $this->disk()->delete($resolved);
        }
    }

    public function exists(?string $path, string $folder): bool
    {
        return $this->resolve($path, $folder) !== null;
    }

    public function download(?string $path, string $folder, string $downloadName, bool $inline = true): StreamedResponse
    {
        $resolved = $this->resolve($path, $folder);

        abort_if($resolved === null, 404, 'The requested document is not available.');

        $safeName = Str::slug(pathinfo($downloadName, PATHINFO_FILENAME)) ?: 'document';

        return $this->disk()->response($resolved, "{$safeName}.pdf", [
            'Content-Type' => 'application/pdf',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=0, must-revalidate',
        ], $inline ? 'inline' : 'attachment');
    }

    /**
     * Returns the on-disk path if the file exists and stays inside the folder.
     */
    private function resolve(?string $path, string $folder): ?string
    {
        if (empty($path)) {
            return null;
        }

        $path = str_replace('\\', '/', $path);

        if (str_contains($path, '..')) {
            return null;
        }

        $allowed = array_values(array_filter([$folder, self::LEGACY_FOLDERS[$folder] ?? null]));
        $candidates = str_contains($path, '/') ? [$path] : array_map(fn ($f) => "{$f}/{$path}", $allowed);

        foreach ($candidates as $candidate) {
            $inAllowedFolder = collect($allowed)->contains(fn ($f) => str_starts_with($candidate, "{$f}/"));

            if ($inAllowedFolder && $this->disk()->exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
