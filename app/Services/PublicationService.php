<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Publication;
use Illuminate\Http\UploadedFile;

final class PublicationService
{
    public function __construct(
        private readonly DocumentStorage $documents,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Publication
    {
        $publication = new Publication(collect($data)->except(['document', 'remove_document'])->all());

        if (($data['document'] ?? null) instanceof UploadedFile) {
            $publication->pdf_file_path = $this->documents->store($data['document'], DocumentStorage::PUBLICATIONS, 'publication');
        }

        $publication->save();
        $this->audit->record('publication.created', "Added publication \"{$publication->title}\"", $publication);

        return $publication;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Publication $publication, array $data): Publication
    {
        $publication->fill(collect($data)->except(['document', 'remove_document'])->all());

        if (($data['document'] ?? null) instanceof UploadedFile) {
            $publication->pdf_file_path = $this->documents->replace(
                $publication->pdf_file_path, $data['document'], DocumentStorage::PUBLICATIONS, 'publication'
            );
        } elseif (! empty($data['remove_document']) && $publication->pdf_file_path) {
            $this->documents->delete($publication->pdf_file_path, DocumentStorage::PUBLICATIONS);
            $publication->pdf_file_path = null;
        }

        $changes = $this->audit->diff($publication);
        $publication->save();
        $this->audit->record('publication.updated', "Updated publication \"{$publication->title}\"", $publication, $changes);

        return $publication;
    }

    public function delete(Publication $publication): void
    {
        $publication->delete();
        $this->audit->record('publication.deleted', "Removed publication \"{$publication->title}\"", $publication);
    }

    /** Atomic counter so concurrent downloads are not lost. */
    public function recordDownload(Publication $publication): void
    {
        Publication::whereKey($publication->getKey())->increment('downloads_count');
    }
}
