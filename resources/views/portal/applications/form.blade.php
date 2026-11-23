<x-layouts.portal :title="'Apply: ' . $program->title" :heading="$program->title" :description="'Application · ' . $program->type->label() . ($program->closes_at ? ' · closes ' . $program->closes_at->format('j F Y, H:i') : '')">
    @php
        $fields = $program->fields();
        $answers = $application->answers ?? [];
        $valueFor = fn ($key) => old("answers.{$key}", $answers[$key] ?? null);
        $inputClass = 'block w-full rounded-lg border bg-white px-3 py-2 text-sm text-slate-900 shadow-xs placeholder:text-slate-400 focus:border-uew-royal focus:ring-2 focus:ring-uew-royal/20 focus:outline-none';
        $maxMb = (int) floor(config('hub.max_upload_kb') / 1024);
    @endphp

    <form method="POST" action="{{ route('portal.apply.store', $program) }}" enctype="multipart/form-data" class="grid gap-6 lg:grid-cols-3" x-data="{ busy: false }" @submit="busy = true" novalidate>
        @csrf

        <div class="space-y-6 lg:col-span-2">
            @if ($errors->any())
                <x-ui.alert tone="danger" title="Some answers need attention">Fix the highlighted questions, then submit again. Your other answers are kept.</x-ui.alert>
            @endif

            <x-ui.card title="Applicant">
                <dl class="grid gap-4 text-sm sm:grid-cols-3">
                    <div><dt class="text-xs text-slate-500">Name</dt><dd class="mt-0.5 font-medium text-slate-900">{{ auth()->user()->name }}</dd></div>
                    <div><dt class="text-xs text-slate-500">E-mail</dt><dd class="mt-0.5 truncate font-medium text-slate-900">{{ auth()->user()->email }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Phone</dt><dd class="mt-0.5 font-medium text-slate-900">{{ auth()->user()->phone ?? '—' }}</dd></div>
                </dl>
                <p class="mt-4 text-xs text-slate-500">Sent from your profile. <a href="{{ route('portal.profile.edit') }}" class="font-semibold text-uew-royal hover:underline">Update profile</a></p>
            </x-ui.card>

            <x-ui.card title="Application questions" :description="count($fields) . ' ' . Str::plural('question', count($fields)) . ' · required answers are marked *'">
                <div class="space-y-6">
                    @forelse ($fields as $i => $field)
                        @php
                            $key = $field['key'];
                            $name = $field['type'] === 'file' ? "files[{$key}]" : "answers[{$key}]";
                            $errorKey = $field['type'] === 'file' ? "files.{$key}" : "answers.{$key}";
                            $id = "q-{$key}";
                            $invalid = $errors->has($errorKey) || $errors->has("{$errorKey}.*");
                            $value = $valueFor($key);
                            $border = $invalid ? 'border-red-400' : 'border-slate-300';
                        @endphp
                        <div class="rounded-xl border border-slate-100 bg-slate-50/40 p-4">
                            <label for="{{ $id }}" class="flex gap-3 text-sm font-semibold text-slate-900">
                                <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-uew-navy text-[11px] font-bold text-white">{{ $i + 1 }}</span>
                                <span>{{ $field['label'] }} @if ($field['required'])<span class="text-red-500">*</span>@endif</span>
                            </label>
                            @if ($field['help'])<p class="mt-1 ml-9 text-xs text-slate-500">{{ $field['help'] }}</p>@endif

                            <div class="mt-3 ml-9">
                                @switch($field['type'])
                                    @case('long_text')
                                        <textarea id="{{ $id }}" name="{{ $name }}" rows="6" maxlength="10000" class="{{ $inputClass }} {{ $border }}">{{ $value }}</textarea>
                                        @break
                                    @case('number')
                                        <input id="{{ $id }}" name="{{ $name }}" type="number" min="0" step="any" value="{{ $value }}" class="{{ $inputClass }} {{ $border }} max-w-xs">
                                        @break
                                    @case('email')
                                        <input id="{{ $id }}" name="{{ $name }}" type="email" value="{{ $value }}" class="{{ $inputClass }} {{ $border }}">
                                        @break
                                    @case('phone')
                                        <input id="{{ $id }}" name="{{ $name }}" type="tel" value="{{ $value }}" class="{{ $inputClass }} {{ $border }} max-w-xs">
                                        @break
                                    @case('url')
                                        <input id="{{ $id }}" name="{{ $name }}" type="url" placeholder="https://" value="{{ $value }}" class="{{ $inputClass }} {{ $border }}">
                                        @break
                                    @case('date')
                                        <input id="{{ $id }}" name="{{ $name }}" type="date" value="{{ $value }}" class="{{ $inputClass }} {{ $border }} max-w-xs">
                                        @break
                                    @case('select')
                                        <select id="{{ $id }}" name="{{ $name }}" class="{{ $inputClass }} {{ $border }}">
                                            <option value="">Choose one</option>
                                            @foreach ($field['options'] as $option)
                                                <option value="{{ $option }}" @selected($value === $option)>{{ $option }}</option>
                                            @endforeach
                                        </select>
                                        @break
                                    @case('radio')
                                        <div class="space-y-2" id="{{ $id }}">
                                            @foreach ($field['options'] as $option)
                                                <label class="flex items-center gap-2.5 text-sm text-slate-700"><input type="radio" name="{{ $name }}" value="{{ $option }}" @checked($value === $option) class="size-4 border-slate-300 text-uew-navy focus:ring-uew-royal"> {{ $option }}</label>
                                            @endforeach
                                        </div>
                                        @break
                                    @case('checkboxes')
                                        <div class="grid gap-2 sm:grid-cols-2" id="{{ $id }}">
                                            @foreach ($field['options'] as $option)
                                                <label class="flex items-center gap-2.5 text-sm text-slate-700"><input type="checkbox" name="{{ $name }}[]" value="{{ $option }}" @checked(in_array($option, (array) $value, true)) class="size-4 rounded border-slate-300 text-uew-navy focus:ring-uew-royal"> {{ $option }}</label>
                                            @endforeach
                                        </div>
                                        @break
                                    @case('yes_no')
                                        <div class="flex gap-4" id="{{ $id }}">
                                            @foreach (['yes' => 'Yes', 'no' => 'No'] as $v => $l)
                                                <label class="flex items-center gap-2 text-sm text-slate-700"><input type="radio" name="{{ $name }}" value="{{ $v }}" @checked($value === $v) class="size-4 border-slate-300 text-uew-navy focus:ring-uew-royal"> {{ $l }}</label>
                                            @endforeach
                                        </div>
                                        @break
                                    @case('file')
                                        @if (! empty($answers[$key]))
                                            <p class="mb-2 inline-flex items-center gap-2 rounded-lg bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700"><x-ui.icon name="check-circle" class="size-4" /> PDF uploaded. Choose a new file only to replace it.</p>
                                        @endif
                                        <input id="{{ $id }}" name="{{ $name }}" type="file" accept="application/pdf" class="{{ $inputClass }} {{ $border }} file:mr-3 file:rounded-md file:border-0 file:bg-uew-navy/10 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-uew-navy">
                                        <p class="mt-1 text-xs text-slate-500">PDF only, up to {{ $maxMb }} MB.</p>
                                        @break
                                    @default
                                        <input id="{{ $id }}" name="{{ $name }}" type="text" maxlength="255" value="{{ $value }}" class="{{ $inputClass }} {{ $border }}">
                                @endswitch

                                @error($errorKey)<p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                                @error("{$errorKey}.*")<p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">This programme has no extra questions. Submit to register your interest.</p>
                    @endforelse
                </div>
            </x-ui.card>
        </div>

        <aside class="space-y-6">
            <div class="sticky top-24 space-y-6">
                <x-ui.card title="Submit">
                    <x-ui.checkbox name="declaration" label="I confirm that the information in this application is accurate and my own work." />
                    <div class="mt-5 flex flex-col gap-2">
                        {{-- Not disabled on submit: a disabled button would drop its "intent" value. --}}
                        <x-ui.button type="submit" name="intent" value="submit" variant="gold" size="lg" icon="check" ::class="busy && 'opacity-60 pointer-events-none'">Submit application</x-ui.button>
                        <x-ui.button type="submit" name="intent" value="draft" variant="secondary" ::class="busy && 'opacity-60 pointer-events-none'">Save draft</x-ui.button>
                    </div>
                    <p class="mt-4 text-xs text-slate-500">After you submit, answers can no longer be changed. Drafts are not reviewed.</p>
                    @if ($application->exists)
                        <p class="mt-2 text-xs text-slate-400">Last saved {{ $application->updated_at->diffForHumans() }} · {{ $application->reference }}</p>
                    @endif
                </x-ui.card>

                <x-ui.card title="About this programme">
                    <p class="text-sm text-slate-600">{{ $program->summary }}</p>
                    <a href="{{ route('site.programs.show', $program) }}" target="_blank" class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-uew-royal hover:underline">Full details <x-ui.icon name="external" class="size-3" /></a>
                </x-ui.card>
            </div>
        </aside>
    </form>
</x-layouts.portal>
