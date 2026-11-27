<?php

declare(strict_types=1);

namespace App\Http\Controllers\Workspace;

use App\Http\Controllers\Controller;
use App\Models\Album;
use App\Models\AlbumPhoto;
use App\Models\Program;
use App\Services\GalleryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Gallery albums and their photos. Routes are gated by can:content.draft; policies re-check. */
class AlbumController extends Controller
{
    /** Per upload request; keeps the request within the server's upload limit. */
    public const MAX_FILES = 10;

    public const MAX_FILE_KB = 10240;

    public function __construct(private readonly GalleryService $gallery) {}

    public function index(): View
    {
        Gate::authorize('viewAny', Album::class);

        return view('workspace.albums.index', [
            'albums' => Album::query()->with('cover')->withCount('photos')->latestEvents()->paginate(18),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Album::class);

        return view('workspace.albums.create', ['album' => new Album, 'programs' => $this->programs()]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Album::class);

        $album = $this->gallery->save(new Album, $this->validated($request), $request->user());

        return redirect()->route('workspace.albums.edit', $album)->with('status', 'Album created. Add photos below.');
    }

    public function edit(Album $album): View
    {
        Gate::authorize('update', $album);

        return view('workspace.albums.edit', [
            'album' => $album->load('photos'),
            'programs' => $this->programs(),
            'maxFiles' => self::MAX_FILES,
            'maxMb' => (int) (self::MAX_FILE_KB / 1024),
        ]);
    }

    public function update(Request $request, Album $album): RedirectResponse
    {
        Gate::authorize('update', $album);

        $this->gallery->save($album, $this->validated($request), $request->user());

        return redirect()->route('workspace.albums.edit', $album)->with('status', 'Album details saved.');
    }

    public function destroy(Album $album): RedirectResponse
    {
        Gate::authorize('delete', $album);

        $this->gallery->delete($album);

        return redirect()->route('workspace.albums.index')->with('status', "\"{$album->title}\" and its photos were deleted.");
    }

    public function upload(Request $request, Album $album): RedirectResponse
    {
        Gate::authorize('update', $album);

        $request->validate([
            'photos' => ['required', 'array', 'min:1', 'max:'.self::MAX_FILES],
            'photos.*' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.self::MAX_FILE_KB, 'dimensions:min_width=300,min_height=300'],
        ], [
            'photos.required' => 'Choose at least one photo.',
            'photos.max' => 'Upload up to '.self::MAX_FILES.' photos at a time.',
            'photos.*.image' => 'Only photos can be uploaded (JPEG, PNG or WebP).',
            'photos.*.mimes' => 'Only JPEG, PNG or WebP photos can be uploaded.',
            'photos.*.max' => 'Each photo must be '.(self::MAX_FILE_KB / 1024).' MB or smaller.',
            'photos.*.dimensions' => 'Photos must be at least 300 × 300 pixels.',
        ]);

        $count = $this->gallery->upload($album, $request->file('photos'), $request->user());

        return redirect()->route('workspace.albums.edit', $album)->with('status', "{$count} photo(s) added.");
    }

    public function arrange(Request $request, Album $album): RedirectResponse
    {
        Gate::authorize('update', $album);

        $data = $request->validate([
            'photos' => ['nullable', 'array'],
            'photos.*.caption' => ['nullable', 'string', 'max:300'],
            'photos.*.sort_order' => ['nullable', 'integer', 'between:0,10000'],
            'cover' => ['nullable', 'integer'],
        ]);

        $this->gallery->arrange($album, $data['photos'] ?? [], isset($data['cover']) ? (int) $data['cover'] : null);

        return redirect()->route('workspace.albums.edit', $album)->with('status', 'Captions, order and cover saved.');
    }

    public function destroyPhoto(Album $album, AlbumPhoto $photo): RedirectResponse
    {
        Gate::authorize('update', $album);
        abort_unless($photo->album_id === $album->id, 404);

        $this->gallery->deletePhoto($photo);

        return redirect()->route('workspace.albums.edit', $album)->with('status', 'Photo deleted.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'summary' => ['nullable', 'string', 'max:300'],
            'description' => ['nullable', 'string', 'max:10000'],
            'event_date' => ['nullable', 'date', 'after:2000-01-01', 'before_or_equal:'.now()->addYear()->toDateString()],
            'location' => ['nullable', 'string', 'max:160'],
            'program_id' => ['nullable', 'integer', Rule::exists('programs', 'id')],
            'is_published' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
        ]);

        return $data + ['is_published' => $request->boolean('is_published'), 'is_featured' => $request->boolean('is_featured')];
    }

    /** @return array<int, string> */
    private function programs(): array
    {
        return Program::query()->orderByDesc('id')->limit(100)->pluck('title', 'id')->all();
    }
}
