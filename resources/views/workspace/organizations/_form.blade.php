{{-- Expects $organization, $types --}}
<x-ui.card class="max-w-3xl">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-ui.input name="name" label="Organisation name" :value="$organization->name" required wrap="sm:col-span-2" />
        <x-ui.select name="type" label="Type" :options="$types" :value="$organization->type" required />
        <x-ui.input name="contact_email" type="email" label="Contact email" :value="$organization->contact_email" hint="Internal only. Never shown publicly." />
        <x-ui.input name="website" type="url" label="Website" :value="$organization->website" placeholder="https://" wrap="sm:col-span-2" />
    </div>
    <x-slot:footer>
        <div class="flex items-center justify-end gap-2">
            <x-ui.button :href="route('workspace.organizations.index')" variant="ghost">Cancel</x-ui.button>
            <x-ui.button type="submit" icon="check">{{ $organization->exists ? 'Save changes' : 'Add partner' }}</x-ui.button>
        </div>
    </x-slot:footer>
</x-ui.card>
