{{-- Expects $broadcast, $interests, $affiliations, $count --}}
@php
    $selectedInterests = old('interests', $broadcast->interests());
    $selectedAffiliations = old('affiliations', $broadcast->affiliations());
@endphp
<div class="grid gap-6 xl:grid-cols-3"
     x-data="{
        count: {{ (int) $count }},
        loading: false,
        async refresh() {
            this.loading = true;
            const params = new URLSearchParams();
            this.$root.querySelectorAll('input[name=\'interests[]\']:checked').forEach(i => params.append('interests[]', i.value));
            this.$root.querySelectorAll('input[name=\'affiliations[]\']:checked').forEach(i => params.append('affiliations[]', i.value));
            const res = await fetch(@js(route('workspace.broadcasts.audience')) + '?' + params.toString(), { headers: { 'Accept': 'application/json' } });
            this.count = (await res.json()).count;
            this.loading = false;
        }
     }">
    <div class="space-y-6 xl:col-span-2">
        <x-ui.card title="Message">
            <div class="space-y-5">
                <x-ui.input name="subject" label="Subject" :value="$broadcast->subject" required maxlength="200" />
                <x-ui.textarea name="body" label="Message" :value="$broadcast->body" rows="14" required class="font-mono text-[13px]"
                    :hint="'Markdown supported. Use ' . \App\Support\Format::placeholder('first_name') . ' or ' . \App\Support\Format::placeholder('name') . ' to personalise. An unsubscribe link is added automatically.'" />
            </div>
        </x-ui.card>
    </div>

    <div class="space-y-6">
        <x-ui.card title="Audience" description="Only verified members who opted in to announcements are included.">
            <div class="space-y-5" @change="refresh()">
                <fieldset>
                    <legend class="text-xs font-semibold text-slate-700">Interested in (any of)</legend>
                    <div class="mt-2 max-h-56 space-y-1.5 overflow-y-auto pr-1">
                        @foreach ($interests as $interest)
                            <label class="flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" name="interests[]" value="{{ $interest }}" @checked(in_array($interest, $selectedInterests, true)) class="size-4 rounded border-slate-300 text-uew-navy"> {{ $interest }}</label>
                        @endforeach
                    </div>
                </fieldset>
                <fieldset>
                    <legend class="text-xs font-semibold text-slate-700">Affiliation</legend>
                    <div class="mt-2 space-y-1.5">
                        @foreach ($affiliations as $value => $label)
                            <label class="flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" name="affiliations[]" value="{{ $value }}" @checked(in_array($value, $selectedAffiliations, true)) class="size-4 rounded border-slate-300 text-uew-navy"> {{ $label }}</label>
                        @endforeach
                    </div>
                </fieldset>
                <p class="text-xs text-slate-500">Leave everything unticked to send to every subscribed member.</p>
            </div>
            <x-slot:footer>
                <p class="flex items-center justify-between text-sm"><span class="text-slate-600">Recipients</span><span class="font-mono text-lg font-bold text-uew-navy" x-text="loading ? '…' : count.toLocaleString()">{{ number_format($count) }}</span></p>
            </x-slot:footer>
        </x-ui.card>

        <div class="flex flex-col gap-2">
            <x-ui.button type="submit" name="intent" value="send" variant="gold" size="lg" icon="bell"
                x-on:click="if (! confirm('Send this broadcast to ' + count + ' member(s) now? This cannot be undone.')) $event.preventDefault()">Send now</x-ui.button>
            <x-ui.button type="submit" name="intent" value="draft" variant="secondary">Save draft</x-ui.button>
            <x-ui.button :href="route('workspace.broadcasts.index')" variant="ghost">Cancel</x-ui.button>
        </div>
    </div>
</div>
