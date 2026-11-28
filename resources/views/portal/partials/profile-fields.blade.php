@use('App\Support\Demographics')
{{-- Shared by onboarding and profile. Expects $user, $affiliations, $focusAreas, $programmeTypes --}}
@php $selected = old('interests', $user->interests ?? []); @endphp

<section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card sm:p-6">
    <h2 class="text-base font-bold text-uew-navy">Contact & personal details</h2>
    <p class="mt-1 text-sm text-slate-500">From your sign-up. Check they are correct; they are filled into your applications automatically.</p>
    <div class="mt-6 grid gap-5 sm:grid-cols-2">
        <x-ui.input name="name" label="Full name" :value="$user->name" required autocomplete="name" />
        <x-ui.input name="phone" type="tel" label="Phone number" :value="$user->phone" required autocomplete="tel" inputmode="tel" placeholder="+233 24 123 4567" />
        <x-ui.select name="gender" label="Gender" :options="Demographics::GENDERS" :value="$user->gender" placeholder="Choose" required />
        <x-ui.input name="date_of_birth" type="date" label="Date of birth" :value="$user->date_of_birth?->format('Y-m-d')" required autocomplete="bday"
            :max="now()->subYears(Demographics::MIN_AGE)->toDateString()" min="1920-01-02" />
        <x-ui.select name="region" label="Region you live in" :options="Demographics::REGIONS" :value="$user->region" placeholder="Choose" required wrap="sm:col-span-2" />
    </div>
</section>

<section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card sm:p-6">
    <h2 class="text-base font-bold text-uew-navy">Background</h2>
    <p class="mt-1 text-sm text-slate-500">Reviewers see your affiliation, institution and education with your applications.</p>
    <div class="mt-6 grid gap-5 sm:grid-cols-2">
        <x-ui.select name="affiliation" label="I am a…" :options="$affiliations" :value="$user->affiliation" placeholder="Choose one" required />
        <x-ui.select name="education_level" label="Highest education level" :options="Demographics::EDUCATION_LEVELS" :value="$user->education_level" placeholder="Choose one" required />
        <x-ui.input name="institution" label="Institution or organisation" :value="$user->institution" placeholder="e.g. University of Education, Winneba" autocomplete="organization" />
        <x-ui.input name="department" label="Department, faculty or programme of study" :value="$user->department" />
        <x-ui.select name="disability" label="Do you have a disability? (optional)" :options="Demographics::DISABILITY" :value="$user->disability" placeholder="Choose"
            hint="Optional. Helps us plan accessible programmes and report on inclusion. Never shown to reviewers." wrap="sm:col-span-2" />
        <x-ui.textarea name="bio" label="Short bio (optional)" :value="$user->bio" rows="3" wrap="sm:col-span-2" hint="A sentence or two about your work or what you hope to build. Up to 1,000 characters." />
    </div>
</section>

<section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card sm:p-6">
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

<section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card sm:p-6">
    <h2 class="text-base font-bold text-uew-navy">Communication</h2>
    <div class="mt-4">
        <x-ui.checkbox name="marketing_opt_in" :checked="old('marketing_opt_in', $user->marketing_opt_in)"
            label="E-mail me when new programmes and events that match my interests are launched"
            hint="Occasional announcements only. Unsubscribe from any e-mail. Messages about your own applications are always sent." />
    </div>
    <div class="mt-4">
        <x-ui.checkbox name="public_profile" :checked="old('public_profile', $user->public_profile)"
            label="Show me on the public community page and leaderboard"
            hint="Shows your name, affiliation, institution, bio, XP and badges. Your e-mail, phone and personal details are never shown." />
    </div>
</section>
