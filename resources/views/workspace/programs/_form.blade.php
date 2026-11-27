{{-- Programme editor with application form builder.
     Expects $program, $types, $statuses, $focusAreas, $fieldTypes, $withOptions, $hasApplications --}}
@php
    $fields = old('fields', collect($program->fields())->map(fn ($f) => [
        'key' => $f['key'], 'label' => $f['label'], 'type' => $f['type'],
        'required' => $f['required'], 'help' => $f['help'], 'options' => implode("\n", $f['options'] ?? []),
        'prefill' => $f['prefill'] ?? '',
    ])->all());
    $fields = array_values(array_map(fn ($f) => [
        'key' => $f['key'] ?? '', 'label' => $f['label'] ?? '', 'type' => $f['type'] ?? 'short_text',
        'required' => filter_var($f['required'] ?? false, FILTER_VALIDATE_BOOL), 'help' => $f['help'] ?? '', 'options' => $f['options'] ?? '',
        'prefill' => $f['prefill'] ?? '',
    ], $fields));
    $prefillSources ??= \App\Support\Demographics::prefillSources();
    $dt = fn ($value) => $value?->format('Y-m-d\TH:i');
@endphp

<div class="grid gap-6 xl:grid-cols-3">
    <div class="space-y-6 xl:col-span-2">
        <x-ui.card title="Programme details">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-ui.input name="title" label="Title" :value="$program->title" required wrap="sm:col-span-2" placeholder="e.g. 2026 Student Innovation Fellowship" />
                <x-ui.select name="type" label="Type" :options="$types" :value="$program->type" required />
                <x-ui.select name="focus_area" label="Focus area" :options="$focusAreas" :value="$program->focus_area" placeholder="Any / several" hint="Members interested in this area are notified when you announce." />
                <x-ui.textarea name="summary" label="Summary" :value="$program->summary" rows="2" required wrap="sm:col-span-2" hint="One or two sentences for cards, e-mails and search. Up to 500 characters." />
                <x-ui.textarea name="description" label="Full description" :value="$program->description" rows="10" wrap="sm:col-span-2" class="font-mono text-[13px]"
                    hint="Markdown: ## heading, **bold**, - lists, [link](https://…). What it offers, timeline, selection process." />
                <x-ui.textarea name="eligibility" label="Who can apply" :value="$program->eligibility" rows="4" wrap="sm:col-span-2" hint="Markdown supported." />
            </div>
        </x-ui.card>

        {{-- Form builder --}}
        <x-ui.card title="Application form" description="The questions applicants answer. Name, e-mail and phone come from their profile automatically.">
            @if ($hasApplications)
                <x-ui.alert tone="warning" class="mb-5">People have already applied. Renaming questions is safe; removing a question hides its answers from new reviews.</x-ui.alert>
            @endif

            <div x-data="{
                    fields: @js($fields),
                    withOptions: @js($withOptions),
                    add(type = 'short_text') { this.fields.push({ key: '', label: '', type, required: false, help: '', options: '', prefill: '' }); this.$nextTick(() => this.$refs.list.lastElementChild?.querySelector('input')?.focus()); },
                    remove(i) { this.fields.splice(i, 1); },
                    move(i, d) { const j = i + d; if (j < 0 || j >= this.fields.length) return; [this.fields[i], this.fields[j]] = [this.fields[j], this.fields[i]]; },
                }">
                <ol class="space-y-3" x-ref="list">
                    <template x-for="(field, i) in fields" :key="i">
                        <li class="rounded-xl border border-slate-200 bg-slate-50/50 p-4">
                            <div class="flex items-start gap-3">
                                <span class="mt-2 flex size-6 shrink-0 items-center justify-center rounded-full bg-uew-navy font-mono text-[11px] font-bold text-white" x-text="i + 1"></span>
                                <div class="grid flex-1 gap-3 sm:grid-cols-12">
                                    <input type="hidden" :name="`fields[${i}][key]`" x-model="field.key">
                                    <label class="sm:col-span-7">
                                        <span class="sr-only">Question</span>
                                        <input type="text" :name="`fields[${i}][label]`" x-model="field.label" required maxlength="200" placeholder="Question"
                                               class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium focus:border-uew-royal focus:ring-2 focus:ring-uew-royal/20 focus:outline-none">
                                    </label>
                                    <label class="sm:col-span-5">
                                        <span class="sr-only">Answer type</span>
                                        <select :name="`fields[${i}][type]`" x-model="field.type" class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-uew-royal focus:ring-2 focus:ring-uew-royal/20 focus:outline-none">
                                            @foreach ($fieldTypes as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                    <label class="sm:col-span-12">
                                        <span class="sr-only">Help text</span>
                                        <input type="text" :name="`fields[${i}][help]`" x-model="field.help" maxlength="300" placeholder="Help text shown under the question (optional)"
                                               class="block w-full rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs text-slate-600 focus:border-uew-royal focus:outline-none">
                                    </label>
                                    <label class="sm:col-span-12" x-show="field.type !== 'file'">
                                        <span class="text-xs font-semibold text-slate-600">Pre-fill from the applicant's profile</span>
                                        <select :name="`fields[${i}][prefill]`" x-model="field.prefill" class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs focus:border-uew-royal focus:outline-none sm:max-w-xs">
                                            <option value="">Don't pre-fill</option>
                                            @foreach ($prefillSources as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                    <label class="sm:col-span-12" x-show="withOptions.includes(field.type)">
                                        <span class="text-xs font-semibold text-slate-600">Choices, one per line</span>
                                        <textarea :name="`fields[${i}][options]`" x-model="field.options" rows="3" placeholder="Option A&#10;Option B"
                                                  class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-uew-royal focus:outline-none"></textarea>
                                    </label>
                                    <div class="flex items-center justify-between gap-3 sm:col-span-12">
                                        <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-700">
                                            <input type="checkbox" :name="`fields[${i}][required]`" value="1" x-model="field.required" class="size-4 rounded border-slate-300 text-uew-navy focus:ring-uew-royal"> Required
                                        </label>
                                        <div class="flex items-center gap-1">
                                            <button type="button" @click="move(i, -1)" class="rounded p-1 text-slate-500 hover:bg-white hover:text-slate-700" title="Move up"><x-ui.icon name="chevron-down" class="size-4 rotate-180" /></button>
                                            <button type="button" @click="move(i, 1)" class="rounded p-1 text-slate-500 hover:bg-white hover:text-slate-700" title="Move down"><x-ui.icon name="chevron-down" class="size-4" /></button>
                                            <button type="button" @click="remove(i)" class="rounded p-1 text-red-400 hover:bg-red-50 hover:text-red-600" title="Remove question"><x-ui.icon name="trash" class="size-4" /></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                    </template>
                </ol>

                <p x-show="fields.length === 0" class="rounded-xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500">No questions yet. Applicants will only confirm their profile.</p>

                <div class="mt-4 flex flex-wrap gap-2">
                    <x-ui.button type="button" variant="secondary" size="sm" icon="plus" @click="add()">Add question</x-ui.button>
                    <x-ui.button type="button" variant="ghost" size="sm" @click="add('long_text')">+ Paragraph</x-ui.button>
                    <x-ui.button type="button" variant="ghost" size="sm" @click="add('file')">+ PDF upload</x-ui.button>
                    <x-ui.button type="button" variant="ghost" size="sm" @click="add('select')">+ Dropdown</x-ui.button>
                </div>
                @foreach ($errors->get('fields*') as $messages)
                    <p class="mt-2 text-xs font-medium text-red-600">{{ $messages[0] }}</p>
                @endforeach
            </div>
        </x-ui.card>
    </div>

    <div class="space-y-6">
        <x-ui.card title="Publishing">
            <div class="space-y-5">
                <x-ui.select name="status" label="Status" :options="$statuses" :value="$program->status" required
                    hint="Draft: hidden. Published: visible and open in its dates. Closed: visible, no new applications. Archived: hidden." />
                <x-ui.checkbox name="is_featured" label="Feature on the homepage" :checked="$program->is_featured" />
                @if (! $program->exists || $program->status?->value !== 'published')
                    <x-ui.checkbox name="notify_members" label="E-mail matching members when published"
                        hint="Sends a launch announcement to members who opted in and follow this focus area." />
                @endif
            </div>
        </x-ui.card>

        <x-ui.card title="Dates & capacity">
            <div class="space-y-5">
                <x-ui.input name="opens_at" type="datetime-local" label="Applications open" :value="$dt($program->opens_at)" hint="Leave empty to open as soon as it is published." />
                <x-ui.input name="closes_at" type="datetime-local" label="Applications close" :value="$dt($program->closes_at)" hint="Leave empty for a rolling deadline." />
                <x-ui.input name="starts_at" type="datetime-local" label="Programme or event starts" :value="$dt($program->starts_at)" />
                <x-ui.input name="location" label="Location" :value="$program->location" placeholder="e.g. Innovation Hub, North Campus" />
                <x-ui.input name="capacity" type="number" min="1" label="Maximum applications" :value="$program->capacity" hint="Applications close automatically when reached." />
                <x-ui.input name="funding_amount" type="number" min="0" step="0.01" label="Funding available (GH₵)" :value="$program->funding_amount" />
            </div>
        </x-ui.card>

        <div class="flex flex-col gap-2">
            <x-ui.button type="submit" size="lg" icon="check">{{ $program->exists ? 'Save programme' : 'Create programme' }}</x-ui.button>
            <x-ui.button :href="$program->exists ? route('workspace.programs.show', $program) : route('workspace.programs.index')" variant="ghost">Cancel</x-ui.button>
        </div>
    </div>
</div>
