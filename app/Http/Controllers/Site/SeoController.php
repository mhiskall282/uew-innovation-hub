<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Album;
use App\Models\Page;
use App\Models\Post;
use App\Models\Program;
use App\Models\Project;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/** robots.txt and sitemap.xml. Both follow the SEARCH_INDEXING setting. */
class SeoController extends Controller
{
    public function robots(): Response
    {
        $lines = config('hub.search_indexing')
            ? [
                'User-agent: *',
                'Disallow: /workspace',
                'Disallow: /portal',
                'Disallow: /notifications',
                'Disallow: /documents/',
                'Disallow: /unsubscribe/',
                'Disallow: /*/apply',
                '',
                'Sitemap: '.route('site.sitemap'),
            ]
            : ['# Indexing is switched off for this site (SEARCH_INDEXING=false).', 'User-agent: *', 'Disallow: /'];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8', 'Cache-Control' => 'public, max-age=3600']);
    }

    public function sitemap(): Response
    {
        $xml = Cache::remember('seo.sitemap', now()->addHour(), function () {
            $urls = collect([
                [route('site.home'), null, '1.0'],
                [route('site.programs.index'), null, '0.9'],
                [route('site.projects.index'), null, '0.8'],
                [route('site.publications.index'), null, '0.7'],
                [route('site.news.index'), null, '0.7'],
                [route('site.partners.index'), null, '0.5'],
                [route('site.gallery.index'), null, '0.6'],
                [route('site.community.index'), null, '0.5'],
                [route('site.contact'), null, '0.5'],
            ]);

            Program::visible()->latest('id')->take(500)->get(['id', 'slug', 'updated_at'])
                ->each(fn ($p) => $urls->push([route('site.programs.show', $p), $p->updated_at, '0.8']));
            Project::public()->latest('id')->take(1000)->get(['id', 'slug', 'updated_at'])
                ->each(fn ($p) => $urls->push([route('site.projects.show', $p), $p->updated_at, '0.6']));
            Post::published()->latest('published_at')->take(1000)->get(['id', 'slug', 'updated_at'])
                ->each(fn ($p) => $urls->push([route('site.news.show', $p), $p->updated_at, '0.6']));
            Album::published()->whereNotNull('cover_photo_id')->latest('id')->take(500)->get(['id', 'slug', 'updated_at'])
                ->each(fn ($a) => $urls->push([route('site.gallery.show', $a), $a->updated_at, '0.5']));
            Page::published()->get(['id', 'slug', 'updated_at'])
                ->each(fn ($p) => $urls->push([route('site.page', $p->slug), $p->updated_at, '0.4']));

            $body = $urls->map(fn ($u) => '  <url><loc>'.e($u[0]).'</loc>'
                .($u[1] ? '<lastmod>'.$u[1]->toAtomString().'</lastmod>' : '')
                .'<priority>'.$u[2].'</priority></url>')->join("\n");

            return '<?xml version="1.0" encoding="UTF-8"?>'."\n"
                .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n".$body."\n</urlset>\n";
        });

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8', 'Cache-Control' => 'public, max-age=3600']);
    }
}
