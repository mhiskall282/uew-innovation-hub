{{-- Shared by onboarding and profile. Expects $user, $affiliations, $focusAreas, $programmeTypes --}}
@php $selected = old('interests', $user->interests ?? []); @endphp

<section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-card" data-step="about">
    <h2 class="text-base font-bold text-uew-navy">About you</h2>
    <p class="mt-1 text-sm text-slate-500">Reviewers see this with your applications.</p>
    <div class="mt-6 grid gap-5 sm:grid-cols-2">
        <x-ui.input name="name" label="Full name" :value="$user->name" required autocomplete="name" />
        <x-ui.input name="phone" type="tel" label="Phone number" :value="$user->phone" required autocomplete="tel" placeholder="+233 24 000 0000" />
        <x-ui.select name="affiliation" label="I am a…" :options="$affiliations" :value="$user->affiliation" placeholder="Choose one" required />
        <x-ui.input name="institution" label="Institution or organisation" :value="$user->institution" placeholder="e.g. University of Education, Winneba" />
        <x-ui.input name="department" label="Department, faculty or programme of study" :value="$user->department" wrap="sm:col-span-2" />
        <x-ui.textarea name="bio" label="Short bio (optional)" :value="$user->bio" rows="3" wrap="sm:col-span-2" hint="A sentence or two about your work or what you hope to build. Up to 1,000 characters." />
    </div>
</section>

<section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-card" data-step="interests">
    <h2 class="text-base font-bold text-uew-navy">Your interests</h2>
    <p class="mt-1 text-sm text-slate-500">We use these to recommend programmes and send you relevant announcements. Choose as many as you like.</p>

    @foreach (['Focus areas' => $focusAreas, 'Types of opportunity' => $programmeTypes] as $group => $options)
        <fieldset class="mt-6">
            <legend class="text-xs font-semibold tracking-wider text-slate-500 uppercase">{{ $group }}</legend>
            <div class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($options as $option)
                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700 transition hover:border-uew-royal/50 has-[:checked]:border-uew-royal has-[:checked]:bg-blue-50/60 has-[:checked]:text-uew-navy">
                        <input type="checkbox" name="interests[]" value="{{ $option }}" @checked(in_array($option, $selected, true)) class="size-4 rounded border-slate-300 text-uew-navy focus:ring-uew-royal">
                        {{ $option }}
                    </label>
                @endforeach
            </div>
        </fieldset>
    @endforeach
    @error('interests')<p class="mt-3 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
</section>

<section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-card" data-step="preferences">
    <h2 class="text-base font-bold text-uew-navy">Communication</h2>
    <div class="mt-4">
        <x-ui.checkbox name="marketing_opt_in" :checked="$user->marketing_opt_in || ! $user->onboarded_at"
            label="E-mail me when new programmes and events that match my interests are launched"
            hint="Occasional announcements only. You can unsubscribe from any e-mail. Messages about your own applications are always sent." />
    </div>
</section>
