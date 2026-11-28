<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use App\Http\Resources\Api\V2\AlbumResource;
use App\Models\Album;
use App\Models\AlbumPhoto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Published event albums from the gallery. Unpublished albums return 404. */
class AlbumController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $this->validateList($request);

        $query = Album::published()
            ->whereNotNull('cover_photo_id')
            ->with('cover', 'program:id,title,slug,status')
            ->withCount('photos');

        return $this->paginate($request, $query, AlbumResource::class);
    }

    public function show(Album $album): JsonResponse
    {
        abort_unless($album->is_published, 404);

        $album->load('cover', 'photos', 'program:id,title,slug,status')->loadCount('photos');
        $album->photos->each(fn (AlbumPhoto $p) => $p->setRelation('album', $album));

        return $this->respond(new AlbumResource($album));
    }
}
