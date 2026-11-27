{{-- Album details. Expects $album, $programs --}}
<div class="grid gap-6 xl:grid-cols-3">
    <x-ui.card title="Event details" class="xl:col-span-2">
        <div class="grid gap-5 sm:grid-cols-2">
            <x-ui.input name="title" label="Album title" :value="$album->title" required maxlength="160" wrap="sm:col-span-2" placeholder="e.g. 2026 Pitch Day" />
            <x-ui.input name="event_date" type="date" label="Event date" :value="$album->event_date?->format('Y-m-d')" />
            <x-ui.input name="location" label="Location" :value="$album->location" maxlength="160" placeholder="e.g. Innovation Hub, North Campus" />
            <x-ui.textarea name="summary" label="Short summary" :value="$album->summary" rows="2" maxlength="300" wrap="sm:col-span-2" hint="Shown on gallery cards. Up to 300 characters." />
            <x-ui.textarea name="description" label="About the event" :value="$album->description" rows="5" wrap="sm:col-span-2" hint="Optional. Who took part, what happened, winners..." />
            <x-ui.select name="program_id" label="Related programme" :options="$programs" :value="$album->program_id" placeholder="None" wrap="sm:col-span-2" hint="Links the album to a programme page." />
        </div>
    </x-ui.card>

    <div class="space-y-6">
        <x-ui.card title="Publishing">
            @can('content.publish')
                <div class="space-y-4">
                    <x-ui.checkbox name="is_published" label="Published on the website" :checked="$album->is_published" hint="Albums need at least one photo to show in the gallery." />
                    <x-ui.checkbox name="is_featured" label="Feature on the homepage" :checked="$album->is_featured" hint="Featured albums appear first in “Moments from the Hub”." />
                </div>
            @else
                <x-ui.alert tone="info">You can create albums and upload photos. A Programme Officer publishes them.</x-ui.alert>
                <p class="mt-3 text-sm text-slate-600">Status: <x-ui.badge :tone="$album->is_published ? 'green' : 'slate'">{{ $album->is_published ? 'Published' : 'Draft' }}</x-ui.badge></p>
            @endcan
        </x-ui.card>
        <div class="flex flex-col gap-2">
            <x-ui.button type="submit" size="lg" icon="check">{{ $album->exists ? 'Save details' : 'Create album' }}</x-ui.button>
            <x-ui.button :href="route('workspace.albums.index')" variant="ghost">Cancel</x-ui.button>
        </div>
    </div>
</div>
