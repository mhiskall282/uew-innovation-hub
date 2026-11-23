<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\View\View;

class PageController extends Controller
{
    public function __invoke(Page $page): View
    {
        abort_unless($page->is_published, 404);

        return view('site.page', ['page' => $page]);
    }
}
