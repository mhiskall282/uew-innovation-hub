<?php

declare(strict_types=1);

namespace App\Http\Controllers\Workspace;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** CMS pages (About, FAQ, Privacy, Terms...). Routes are gated by can:pages.manage. */
class PageController extends Controller
{
    /** Slugs that would clash with site routes. */
    private const RESERVED = [
        'projects', 'publications', 'news', 'partners', 'contact', 'programmes', 'programs', 'apply', 'track',
        'sign-in', 'sign-up', 'register', 'login', 'logout', 'verify-email', 'forgot-password', 'reset-password',
        'onboarding', 'portal', 'workspace', 'api', 'documents', 'unsubscribe', 'up', 'build', 'images',
        'community', 'notifications', 'signin', 'signup', 'admin', 'news-and-blogs', 'gallery', 'media', 'email', 'password', 'robots', 'sitemap',
    ];

    public function __construct(private readonly AuditLogger $audit) {}

    public function index(): View
    {
        return view('workspace.pages.index', ['pages' => Page::with('editor:id,name')->orderBy('sort_order')->orderBy('title')->get()]);
    }

    public function create(): View
    {
        return view('workspace.pages.create', ['page' => new Page(['is_published' => true, 'show_in_footer' => true, 'sort_order' => 0])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $page = new Page($this->validated($request));
        $page->updated_by = $request->user()->id;
        $page->save();
        $this->audit->record('page.created', "Created page \"{$page->title}\"", $page);

        return redirect()->route('workspace.pages.edit', $page)->with('status', 'Page created.');
    }

    public function edit(Page $page): View
    {
        return view('workspace.pages.edit', ['page' => $page]);
    }

    public function update(Request $request, Page $page): RedirectResponse
    {
        $page->fill($this->validated($request, $page));
        $page->updated_by = $request->user()->id;
        $changes = $this->audit->diff($page);
        unset($changes['body'], $changes['updated_by']);
        $page->save();
        $this->audit->record('page.updated', "Updated page \"{$page->title}\"", $page, $changes);

        return back()->with('status', 'Page saved.');
    }

    public function destroy(Page $page): RedirectResponse
    {
        $page->delete();
        $this->audit->record('page.deleted', "Deleted page \"{$page->title}\"", $page);

        return redirect()->route('workspace.pages.index')->with('status', "\"{$page->title}\" was deleted.");
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?Page $page = null): array
    {
        $request->merge([
            'slug' => Str::slug((string) ($request->input('slug') ?: $request->input('title'))),
            'is_published' => $request->boolean('is_published'),
            'show_in_footer' => $request->boolean('show_in_footer'),
        ]);

        return $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9-]+$/', Rule::notIn(self::RESERVED), Rule::unique('pages', 'slug')->ignore($page?->id)],
            'summary' => ['nullable', 'string', 'max:300'],
            'body' => ['required', 'string', 'max:100000'],
            'is_published' => ['boolean'],
            'show_in_footer' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'between:0,1000'],
        ], ['slug.not_in' => 'That address is used by another part of the site. Choose a different one.']);
    }
}
