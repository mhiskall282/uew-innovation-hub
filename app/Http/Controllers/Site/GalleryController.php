<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Album;
use App\Models\AlbumPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Public event gallery, and the endpoint that serves photo files. */
class GalleryController extends Controller
{
    public function index(): View
    {
        $albums = Album::published()->whereNotNull('cover_photo_id')
            ->with('cover')->withCount('photos')->latestEvents()->paginate(12);

        // Skip albums whose files are gone (see AlbumPhoto::isAvailable()).
        $albums->setCollection($albums->getCollection()->filter(fn (Album $a) => $a->cover?->isAvailable())->values());

        return view('site.gallery.index', ['albums' => $albums]);
    }

    public function show(Album $album): View
    {
        abort_unless($album->is_published, 404);

        $album->load('photos', 'program:id,title,slug,status');
        $album->setRelation('photos', $album->photos->filter(fn (AlbumPhoto $p) => $p->isAvailable())->values());

        return view('site.gallery.show', [
            'album' => $album,
            'more' => Album::published()->whereKeyNot($album->id)->whereNotNull('cover_photo_id')
                ->with('cover')->withCount('photos')->latestEvents()->take(6)->get()
                ->filter(fn (Album $a) => $a->cover?->isAvailable())->take(3)->values(),
        ]);
    }

    /**
     * Streams one rendition. Photos in unpublished albums are only shown to
     * staff who work on the gallery, and are never cached publicly.
     */
    public function photo(Request $request, AlbumPhoto $photo, string $size): StreamedResponse
    {
        $album = $photo->album;
        abort_unless($album, 404);

        $public = $album->is_published;
        abort_unless($public || ($request->user() && Gate::forUser($request->user())->allows('viewUnpublished', $album)), 404);

        $path = $photo->path($size);
        $disk = Storage::disk($photo->disk);
        abort_unless($disk->exists($path), 404);

        return $disk->response($path, null, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => $public ? 'public, max-age=31536000, immutable' : 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Disposition' => 'inline',
        ]);
    }
}
