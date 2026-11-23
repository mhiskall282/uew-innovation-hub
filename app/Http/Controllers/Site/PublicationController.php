<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Publication;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicationController extends Controller
{
    public function index(Request $request): View
    {
        $type = in_array($request->query('type'), config('hub.publication_types'), true) ? $request->query('type') : null;

        return view('site.publications.index', [
            'publications' => Publication::query()
                ->with('project:id,title,slug,is_public')
                ->when($type, fn ($q) => $q->where('type', $type))
                ->search($request->query('search'), ['title', 'authors', 'journal_name'])
                ->latest('publish_date')
                ->paginate(12)
                ->withQueryString(),
            'types' => config('hub.publication_types'),
            'activeType' => $type,
            'search' => $request->query('search'),
        ]);
    }
}
