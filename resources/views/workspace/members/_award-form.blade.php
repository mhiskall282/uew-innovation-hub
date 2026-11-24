{{-- Award a badge. Expects $member, $types; optional $programId (fixed) or $programs (choice list) --}}
<form method="POST" action="{{ route('workspace.achievements.store', $member) }}" class="space-y-4" x-data="{ type: @js(old('type', 'winner')) }">
    @csrf
    <label class="block">
        <span class="text-xs font-semibold text-slate-700">Badge</span>
        <select name="type" x-model="type" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-uew-royal focus:ring-2 focus:ring-uew-royal/20 focus:outline-none">
            @foreach ($types as $value => $label)
                <option value="{{ $value }}">{{ $label }} (+{{ \App\Enums\AchievementType::from($value)->xp() }} XP)</option>
            @endforeach
        </select>
    </label>
    @error('type')<p class="text-xs font-medium text-red-600">{{ $message }}</p>@enderror

    @if (! empty($programId))
        <input type="hidden" name="program_id" value="{{ $programId }}">
    @elseif (! empty($programs) && count($programs))
        <x-ui.select name="program_id" label="Programme" :options="$programs" placeholder="Not linked to a programme" />
    @endif

    <x-ui.input name="title" label="Title" placeholder="Leave empty for e.g. 'Winner: Programme name'" maxlength="150" hint="Required for a custom recognition." />
    <x-ui.input name="description" label="Note on the profile" maxlength="500" placeholder="e.g. First place, 2026 Pitch Day" />
    <div x-show="type === 'custom'" x-cloak>
        <x-ui.input name="xp" type="number" min="0" max="1000" label="XP" value="50" />
    </div>
    <x-ui.button type="submit" icon="trophy" class="w-full" variant="gold">Award badge</x-ui.button>
</form>
