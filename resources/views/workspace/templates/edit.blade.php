@use('App\Support\Format')
<x-layouts.workspace :title="$definition['name']">
    <x-ui.page-header :title="$definition['name']" :description="$definition['description']" :back="route('workspace.templates.index')" back-label="E-mail templates">
        <x-slot:actions>
            <x-ui.badge :tone="$content['customised'] ? 'gold' : 'slate'" size="md">{{ $content['customised'] ? 'Customised' : 'Default wording' }}</x-ui.badge>
            @if ($content['customised'])
                <x-ui.confirm-form :action="route('workspace.templates.reset', $key)" message="Discard your changes and restore the default wording?" label="Restore default" variant="secondary" icon="arrow-left" />
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-6 xl:grid-cols-2"
         x-data="{
            subject: @js(old('subject', $content['subject'])),
            body: @js(old('body', $content['body'])),
            src: '',
            timer: null,
            base: @js(route('workspace.templates.preview', $key)),
            refresh() { clearTimeout(this.timer); this.timer = setTimeout(() => { this.src = this.base + '?' + new URLSearchParams({ subject: this.subject, body: this.body }).toString(); }, 400); },
            insert(token) { const el = this.$refs.body; const s = el.selectionStart ?? this.body.length; this.body = this.body.slice(0, s) + token + this.body.slice(el.selectionEnd ?? s); this.$nextTick(() => { el.focus(); el.selectionStart = el.selectionEnd = s + token.length; }); this.refresh(); },
         }"
         x-init="refresh()">
        <form method="POST" action="{{ route('workspace.templates.update', $key) }}" class="space-y-6">
            @csrf
            @method('PUT')
            <x-ui.card title="Content">
                <div class="space-y-5">
                    <x-ui.field label="Subject" for="subject" name="subject" required>
                        <input id="subject" name="subject" x-model="subject" @input="refresh()" required maxlength="200"
                               class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-xs focus:border-uew-royal focus:ring-2 focus:ring-uew-royal/20 focus:outline-none">
                    </x-ui.field>
                    <x-ui.field label="Message" for="body" name="body" required hint="Markdown: **bold**, ## heading, - lists, [link](https://…). A single line starting with # shows as a large code box.">
                        <textarea id="body" name="body" x-ref="body" x-model="body" @input="refresh()" rows="16" required
                                  class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 font-mono text-[13px] leading-6 shadow-xs focus:border-uew-royal focus:ring-2 focus:ring-uew-royal/20 focus:outline-none"></textarea>
                    </x-ui.field>
                </div>
                <x-slot:footer>
                    <div class="flex justify-end"><x-ui.button type="submit" icon="check">Save template</x-ui.button></div>
                </x-slot:footer>
            </x-ui.card>

            <x-ui.card title="Placeholders" description="Click to insert. They are replaced for each recipient.">
                <ul class="grid gap-2 sm:grid-cols-2">
                    @foreach ($definition['placeholders'] as $name => $meaning)
                        <li>
                            <button type="button" @click="insert(@js(Format::placeholder($name)))" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-left hover:border-uew-royal hover:bg-blue-50/50">
                                <span class="block font-mono text-xs font-semibold text-uew-navy">{{ Format::placeholder($name) }}</span>
                                <span class="block text-[11px] text-slate-500">{{ $meaning }}</span>
                            </button>
                        </li>
                    @endforeach
                </ul>
                @if ($definition['action'])
                    <p class="mt-4 text-xs text-slate-500">A "<strong>{{ $definition['action'] }}</strong>" button is added below your message automatically.</p>
                @endif
            </x-ui.card>
        </form>

        <x-ui.card title="Live preview" description="With sample data" padding="p-0" class="xl:sticky xl:top-20 xl:self-start">
            <iframe :src="src" title="E-mail preview" class="h-[720px] w-full rounded-b-xl bg-slate-50" sandbox="allow-same-origin"></iframe>
        </x-ui.card>
    </div>
</x-layouts.workspace>
