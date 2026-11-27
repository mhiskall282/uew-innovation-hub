<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Album;
use App\Models\AlbumPhoto;
use App\Models\User;
use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Event albums and photo processing.
 *
 * Every upload is decoded and re-encoded with GD, never stored as sent:
 *  - metadata (EXIF, including GPS location from phones) is dropped;
 *  - files that only pretend to be images can't survive the re-encode;
 *  - two JPEG renditions are kept (1600px and 640px), the original is not;
 *  - phone photos are turned the right way up from the EXIF orientation tag.
 * Very large images are refused before decoding, so a small file that expands
 * to an enormous bitmap can't exhaust memory.
 */
final class GalleryService
{
    public const MAX_MEGAPIXELS = 30;

    public const JPEG_QUALITY = 82;

    public function __construct(private readonly AuditLogger $audit) {}

    public static function disk(): string
    {
        return (string) config('hub.gallery_disk', 'gallery');
    }

    /** @param array<string, mixed> $data */
    public function save(Album $album, array $data, User $user): Album
    {
        $isNew = ! $album->exists;
        $album->fill($data);

        if ($user->can('content.publish')) {
            $publish = (bool) ($data['is_published'] ?? false);
            if ($publish && ! $album->is_published) {
                $album->published_at = now();
            }
            $album->is_published = $publish;
            $album->is_featured = (bool) ($data['is_featured'] ?? false);
        }

        if ($isNew) {
            $album->created_by = $user->id;
        }

        $changes = $this->audit->diff($album);
        $album->save();

        $this->audit->record($isNew ? 'album.created' : 'album.updated', ($isNew ? 'Created' : 'Updated')." gallery album \"{$album->title}\"", $album, $isNew ? [] : $changes);

        return $album;
    }

    /**
     * @param  list<UploadedFile>  $files
     * @return int photos added
     */
    public function upload(Album $album, array $files, User $user): int
    {
        $added = 0;
        foreach ($files as $file) {
            $this->addFromPath($album, $file->getRealPath(), $user);
            $added++;
        }

        $this->audit->record('album.photos_added', "Added {$added} photo(s) to \"{$album->title}\"", $album);

        return $added;
    }

    /** Adds one image file (an upload or, for seeding, a local file). */
    public function addFromPath(Album $album, string $path, ?User $user = null, ?string $caption = null): AlbumPhoto
    {
        $image = $this->decode($path);
        $disk = Storage::disk(self::disk());
        $base = 'albums/'.$album->id.'/'.Str::uuid()->toString();
        $written = [];

        try {
            $large = $this->resize($image, AlbumPhoto::SIZES['large']);
            $written[] = $largePath = $base.'-large.jpg';
            $disk->put($largePath, $this->jpeg($large), 'private');

            $written[] = $thumbPath = $base.'-thumb.jpg';
            $disk->put($thumbPath, $this->jpeg($this->resize($large, AlbumPhoto::SIZES['thumb'])), 'private');

            return DB::transaction(function () use ($album, $large, $largePath, $thumbPath, $user, $caption, $disk) {
                $photo = new AlbumPhoto(['caption' => $caption]);
                $photo->forceFill([
                    'album_id' => $album->id,
                    'disk' => self::disk(),
                    'path_large' => $largePath,
                    'path_thumb' => $thumbPath,
                    'width' => imagesx($large),
                    'height' => imagesy($large),
                    'size_bytes' => (int) $disk->size($largePath),
                    'sort_order' => (int) $album->photos()->max('sort_order') + 1,
                    'uploaded_by' => $user?->id,
                ])->save();

                if (! $album->cover_photo_id) {
                    $album->forceFill(['cover_photo_id' => $photo->id])->save();
                }

                return $photo;
            });
        } catch (\Throwable $e) {
            $disk->delete($written); // no orphaned files

            throw $e;
        }
    }

    /**
     * Captions, order and cover in one save from the photo manager.
     *
     * @param  array<int, array{caption?: ?string, sort_order?: ?int}>  $photos  keyed by photo id
     */
    public function arrange(Album $album, array $photos, ?int $coverId): void
    {
        DB::transaction(function () use ($album, $photos, $coverId) {
            foreach ($album->photos()->get() as $photo) {
                if (! isset($photos[$photo->id])) {
                    continue;
                }
                $photo->caption = trim((string) ($photos[$photo->id]['caption'] ?? '')) ?: null;
                $photo->sort_order = (int) ($photos[$photo->id]['sort_order'] ?? $photo->sort_order);
                $photo->save();
            }

            if ($coverId && $album->photos()->whereKey($coverId)->exists()) {
                $album->forceFill(['cover_photo_id' => $coverId])->save();
            }
        });

        $this->audit->record('album.arranged', "Updated photos in \"{$album->title}\"", $album);
    }

    public function deletePhoto(AlbumPhoto $photo): void
    {
        $album = $photo->album;
        Storage::disk($photo->disk)->delete([$photo->path_large, $photo->path_thumb]);
        $photo->delete();

        if ($album && $album->cover_photo_id === $photo->id) {
            $album->forceFill(['cover_photo_id' => $album->photos()->value('id')])->save();
        }

        $this->audit->record('album.photo_deleted', 'Deleted a photo from "'.($album?->title ?? 'an album').'"', $album);
    }

    public function delete(Album $album): void
    {
        foreach ($album->photos()->get() as $photo) {
            Storage::disk($photo->disk)->delete([$photo->path_large, $photo->path_thumb]);
        }
        Storage::disk(self::disk())->deleteDirectory('albums/'.$album->id);

        $this->audit->record('album.deleted', "Deleted gallery album \"{$album->title}\"", $album);
        $album->delete();
    }

    private function decode(string $path): GdImage
    {
        $info = @getimagesize($path);
        $fail = fn (string $why) => throw ValidationException::withMessages(['photos' => $why]);

        if (! $info || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            $fail('One of the files is not a JPEG, PNG or WebP photo.');
        }
        if (self::MAX_MEGAPIXELS * 1_000_000 < $info[0] * $info[1]) {
            $fail('One of the photos is larger than '.self::MAX_MEGAPIXELS.' megapixels. Resize it and try again.');
        }

        $previous = ini_get('memory_limit');
        ini_set('memory_limit', '512M');
        $image = @imagecreatefromstring((string) file_get_contents($path));
        ini_set('memory_limit', (string) $previous);

        if (! $image) {
            $fail('One of the photos could not be read. It may be damaged.');
        }

        if ($info[2] === IMAGETYPE_JPEG) {
            $image = $this->orient($image, self::exifOrientation($path));
        }

        return $image;
    }

    private function resize(GdImage $image, int $max): GdImage
    {
        $w = imagesx($image);
        $h = imagesy($image);
        $scale = min(1, $max / max($w, $h));
        if ($scale === 1.0 || $scale === 1) {
            return $this->flatten($image, $w, $h);
        }

        return $this->flatten($image, max(1, (int) round($w * $scale)), max(1, (int) round($h * $scale)));
    }

    /** Copies onto a white canvas (JPEG has no transparency) at the target size. */
    private function flatten(GdImage $image, int $w, int $h): GdImage
    {
        $canvas = imagecreatetruecolor($w, $h);
        imagefill($canvas, 0, 0, (int) imagecolorallocate($canvas, 255, 255, 255));
        imagecopyresampled($canvas, $image, 0, 0, 0, 0, $w, $h, imagesx($image), imagesy($image));

        return $canvas;
    }

    private function jpeg(GdImage $image): string
    {
        imageinterlace($image, true); // progressive: shows early on slow mobile networks
        ob_start();
        imagejpeg($image, null, self::JPEG_QUALITY);

        return (string) ob_get_clean();
    }

    private function orient(GdImage $image, int $orientation): GdImage
    {
        $image = match ($orientation) {
            3, 4 => imagerotate($image, 180, 0),
            5, 6 => imagerotate($image, -90, 0),
            7, 8 => imagerotate($image, 90, 0),
            default => $image,
        };
        if (in_array($orientation, [2, 4, 5, 7], true)) {
            imageflip($image, IMG_FLIP_HORIZONTAL);
        }

        return $image;
    }

    /**
     * Reads the EXIF orientation tag (0x0112) from a JPEG without the exif
     * extension. Returns 1 (normal) when absent or unreadable.
     */
    public static function exifOrientation(string $path): int
    {
        $fh = @fopen($path, 'rb');
        if (! $fh) {
            return 1;
        }

        // Reads an unsigned integer only when enough bytes are there, so
        // truncated or malformed metadata (common in the wild) never throws.
        $read = static function (string $bytes, int $offset, int $size, bool $le): ?int {
            if ($offset < 0 || strlen($bytes) < $offset + $size) {
                return null;
            }
            $value = unpack($size === 2 ? ($le ? 'v' : 'n') : ($le ? 'V' : 'N'), substr($bytes, $offset, $size));

            return $value === false ? null : (int) $value[1];
        };

        try {
            if (fread($fh, 2) !== "\xFF\xD8") {
                return 1;
            }

            for ($segments = 0; $segments < 64 && ! feof($fh); $segments++) {
                $header = (string) fread($fh, 4);
                if (strlen($header) < 4 || $header[0] !== "\xFF") {
                    return 1;
                }
                $length = $read($header, 2, 2, false) ?? 0;
                if ($header[1] === "\xDA" || $length < 2) {
                    return 1; // image data starts; no orientation found
                }
                if ($header[1] !== "\xE1") {
                    fseek($fh, $length - 2, SEEK_CUR);

                    continue;
                }

                $data = (string) fread($fh, $length - 2);
                if (! str_starts_with($data, "Exif\0\0")) {
                    continue;
                }
                $tiff = substr($data, 6);
                $le = str_starts_with($tiff, 'II');
                $ifd = $read($tiff, 4, 4, $le);
                $entries = $ifd === null ? null : $read($tiff, $ifd, 2, $le);
                for ($i = 0; $i < min((int) $entries, 200); $i++) {
                    $entry = $ifd + 2 + $i * 12;
                    if ($read($tiff, $entry, 2, $le) === 0x0112) {
                        $value = $read($tiff, $entry + 8, 2, $le) ?? 1;

                        return $value >= 1 && $value <= 8 ? $value : 1;
                    }
                }

                return 1;
            }
        } catch (\Throwable) {
            return 1;
        } finally {
            fclose($fh);
        }

        return 1;
    }
}
