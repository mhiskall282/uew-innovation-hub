<x-layouts.workspace title="Pages">
    <x-ui.page-header title="Pages" description="About, FAQ, privacy, terms and any other page on the public site. Written in Markdown.">
        <x-slot:actions><x-ui.button :href="route('workspace.pages.create')" icon="plus">New page</x-ui.button></x-slot:actions>
    </x-ui.page-header>

    <x-ui.card padding="p-0">
        @if ($pages->isEmpty())
            <x-ui.empty title="No pages yet" icon="document" />
        @else
            <ul class="divide-y divide-slate-100">
                @foreach ($pages as $page)
                    <li class="flex flex-wrap items-center gap-4 px-5 py-4">
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('workspace.pages.edit', $page) }}" class="font-semibold text-slate-900 hover:text-uew-royal">{{ $page->title }}</a>
                            <p class="font-mono text-xs text-slate-500">/{{ $page->slug }} · updated {{ $page->updated_at->diffForHumans() }}@if ($page->editor) by {{ $page->editor->name }}@endif</p>
                        </div>
                        @if ($page->show_in_footer)<x-ui.badge>In footer</x-ui.badge>@endif
                        <x-ui.badge :tone="$page->is_published ? 'green' : 'slate'" dot>{{ $page->is_published ? 'Published' : 'Hidden' }}</x-ui.badge>
                        @if ($page->is_published)
                            <x-ui.button :href="route('site.page', $page)" variant="ghost" size="xs" icon="external" target="_blank" rel="noopener">View</x-ui.button>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </x-ui.card>
</x-layouts.workspace>
