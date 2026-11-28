<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Models\AlbumPhoto;
use App\Models\Organization;
use App\Models\Post;
use App\Models\Program;
use App\Models\Project;
use App\Models\Publication;
use App\Services\MetricsService;
use App\Services\SettingsService;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(MetricsService $metrics, SettingsService $settings): View
    {
        $open = Program::open()->orderByDesc('is_featured')->orderByRaw('CASE WHEN closes_at IS NULL THEN 1 ELSE 0 END')->orderBy('closes_at');

        $focusAreas = Project::public()
            ->select('category', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('category')
            ->pluck('aggregate', 'category');

        return view('site.home', [
            'content' => $settings->all(),
            'programs' => (clone $open)->take(3)->get(),
            'openCount' => (clone $open)->count(),
            'stats' => $metrics->publicStats(),
            'focusAreas' => collect(config('hub.focus_areas'))->mapWithKeys(fn ($area) => [$area => (int) ($focusAreas[$area] ?? 0)]),
            'featuredProjects' => Project::public()
                ->with('organizations:id,name')
                ->where('status', ProjectStatus::Active)
                ->latest('start_date')
                ->take(3)
                ->get(),
            'posts' => Post::published()->with('author:id,name')->latest('published_at')->take(3)->get(),
            'publications' => Publication::latest('publish_date')->take(3)->get(),
            'partners' => Organization::orderBy('name')->take(12)->get(['id', 'name', 'type', 'website']),
            // Up to 8 photos from published albums, featured albums and newest events first.
            'moments' => AlbumPhoto::query()
                ->join('albums', 'albums.id', '=', 'album_photos.album_id')
                ->where('albums.is_published', true)
                ->orderByDesc('albums.is_featured')
                ->orderByRaw('CASE WHEN albums.event_date IS NULL THEN 1 ELSE 0 END')
                ->orderByDesc('albums.event_date')
                ->orderBy('album_photos.sort_order')
                ->select('album_photos.*')
                ->with('album:id,slug,title,event_date')
                ->take(24)
                ->get()
                ->filter(fn (AlbumPhoto $photo) => $photo->isAvailable())
                ->take(8)
                ->values(),
        ]);
    }
}
