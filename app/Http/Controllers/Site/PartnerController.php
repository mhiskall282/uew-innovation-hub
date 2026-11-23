<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\View\View;

class PartnerController extends Controller
{
    public function index(): View
    {
        $partners = Organization::query()
            ->withCount(['projects' => fn ($q) => $q->where('is_public', true)])
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'website']);

        return view('site.partners.index', [
            'groups' => $partners->groupBy('type')->sortKeys(),
            'total' => $partners->count(),
        ]);
    }
}
