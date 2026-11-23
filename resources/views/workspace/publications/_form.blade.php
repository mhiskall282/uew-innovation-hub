{{-- Expects $publication, $types, $projects --}}
<div class="grid gap-6 xl:grid-cols-3">
    <x-ui.card title="Publication details" class="xl:col-span-2">
        <div class="grid gap-5 sm:grid-cols-2">
            <x-ui.input name="title" label="Title" :value="$publication->title" required wrap="sm:col-span-2" />
            <x-ui.select name="type" label="Type" :options="$types" :value="$publication->type" required />
            <x-ui.input name="publish_date" type="date" label="Publication date" :value="$publication->publish_date?->format('Y-m-d')" />
            <x-ui.input name="authors" label="Authors" :value="$publication->authors" wrap="sm:col-span-2" placeholder="e.g. Prof. S. Hayford, Dr. J. Okyere" />
            <x-ui.input name="journal_name" label="Journal or series" :value="$publication->journal_name" />
            <x-ui.input name="doi_url" type="url" label="DOI or external link" :value="$publication->doi_url" placeholder="https://doi.org/…" />
            <x-ui.select name="project_id" label="Related project" :options="$projects->pluck('title', 'id')->all()" :value="$publication->project_id" placeholder="None" wrap="sm:col-span-2" />
            <x-ui.textarea name="abstract" label="Abstract" :value="$publication->abstract" rows="5" wrap="sm:col-span-2" />
        </div>
    </x-ui.card>

    <div class="space-y-6">
        <x-ui.card title="Document">
            @if ($publication->hasDocument())
                <div class="mb-4 flex items-center justify-between gap-3 rounded-lg bg-slate-50 p-3 text-sm">
                    <span class="inline-flex items-center gap-2 font-medium text-slate-700"><x-ui.icon name="document" class="size-4 text-red-600" /> PDF on file</span>
                    <a href="{{ route('site.documents.publication', $publication) }}" target="_blank" rel="noopener" class="text-xs font-semibold text-uew-royal hover:underline">Open</a>
                </div>
                <x-ui.checkbox name="remove_document" label="Remove the current PDF" class="mb-4" />
            @endif
            <x-ui.input name="document" type="file" :label="$publication->hasDocument() ? 'Replace PDF' : 'Upload PDF'" accept="application/pdf"
                :hint="'Shown in the public library. PDF, up to ' . (int) floor(config('hub.max_upload_kb') / 1024) . ' MB.'" />
        </x-ui.card>

        <div class="flex flex-col gap-2">
            <x-ui.button type="submit" size="lg" icon="check">{{ $publication->exists ? 'Save changes' : 'Add publication' }}</x-ui.button>
            <x-ui.button :href="route('workspace.publications.index')" variant="ghost">Cancel</x-ui.button>
        </div>
    </div>
</div>
