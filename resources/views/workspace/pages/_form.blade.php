{{-- Expects $page --}}
<div class="grid gap-6 xl:grid-cols-3">
    <x-ui.card class="xl:col-span-2">
        <div class="space-y-5">
            <x-ui.input name="title" label="Title" :value="$page->title" required />
            <x-ui.textarea name="summary" label="Summary" :value="$page->summary" rows="2" hint="Shown under the title and in search results. Up to 300 characters." />
            <x-ui.textarea name="body" label="Content" :value="$page->body" rows="22" required class="font-mono text-[13px]"
                hint="Markdown: ## heading, **bold**, - lists, [link](https://…), > quote. HTML is not allowed." />
        </div>
    </x-ui.card>
    <div class="space-y-6">
        <x-ui.card title="Settings">
            <div class="space-y-5">
                <x-ui.field label="Web address" for="slug" name="slug" hint="Lowercase letters, numbers and hyphens.">
                    <div class="flex items-center rounded-lg border border-slate-300 bg-white text-sm shadow-xs focus-within:border-uew-royal focus-within:ring-2 focus-within:ring-uew-royal/20">
                        <span class="pl-3 text-slate-500">{{ parse_url(url('/'), PHP_URL_HOST) }}/</span>
                        <input id="slug" name="slug" value="{{ old('slug', $page->slug) }}" class="block w-full rounded-lg border-0 bg-transparent py-2 pr-3 pl-0.5 text-sm focus:ring-0 focus:outline-none" placeholder="about">
                    </div>
                </x-ui.field>
                <x-ui.checkbox name="is_published" label="Published" :checked="$page->is_published" />
                <x-ui.checkbox name="show_in_footer" label="Link in the site footer" :checked="$page->show_in_footer" />
                <x-ui.input name="sort_order" type="number" min="0" label="Footer order" :value="$page->sort_order" />
            </div>
        </x-ui.card>
        <div class="flex flex-col gap-2">
            <x-ui.button type="submit" size="lg" icon="check">{{ $page->exists ? 'Save page' : 'Create page' }}</x-ui.button>
            <x-ui.button :href="route('workspace.pages.index')" variant="ghost">Cancel</x-ui.button>
        </div>
    </div>
</div>
