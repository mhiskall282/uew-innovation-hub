<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\DocumentStorage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Copies PDFs from the legacy system's reports/ and publications/ folders
 * (kept at the repository root) onto the configured documents disk, so
 * existing database rows that reference them keep working.
 *
 * Idempotent: files already on the disk are skipped. Works with local
 * storage and S3-compatible buckets alike.
 */
class ImportLegacyDocuments extends Command
{
    protected $signature = 'hub:import-documents {--source= : Folder containing reports/ and publications/ (defaults to the project root)}';

    protected $description = 'Copy legacy report and publication PDFs onto the documents disk';

    public function handle(DocumentStorage $documents): int
    {
        $source = rtrim((string) ($this->option('source') ?: base_path()), '/\\');
        $disk = $documents->disk();
        $copied = 0;
        $skipped = 0;

        foreach ([DocumentStorage::REPORTS, DocumentStorage::PUBLICATIONS] as $folder) {
            $directory = $source . DIRECTORY_SEPARATOR . $folder;

            if (! File::isDirectory($directory)) {
                continue;
            }

            foreach (File::files($directory) as $file) {
                if (strtolower($file->getExtension()) !== 'pdf') {
                    continue;
                }

                $target = $folder . '/' . $file->getFilename();

                if ($disk->exists($target)) {
                    $skipped++;

                    continue;
                }

                $stream = fopen($file->getPathname(), 'rb');
                $disk->writeStream($target, $stream);
                is_resource($stream) && fclose($stream);
                $copied++;
            }
        }

        $this->components->info("Documents imported: {$copied} copied, {$skipped} already present.");

        return self::SUCCESS;
    }
}
