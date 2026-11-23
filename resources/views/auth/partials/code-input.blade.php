{{-- Six-digit one-time code input --}}
<x-ui.field label="Verification code" for="code" name="code" required>
    <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9 ]{6,7}" maxlength="7" required autofocus
           placeholder="••••••" value="{{ old('code') }}"
           class="block w-full rounded-xl border bg-white px-4 py-3 text-center font-mono text-3xl font-bold tracking-[0.5em] text-uew-navy shadow-xs focus:border-uew-royal focus:ring-2 focus:ring-uew-royal/20 focus:outline-none {{ $errors->has('code') ? 'border-red-400' : 'border-slate-300' }}">
</x-ui.field>

@if (session('dev_code'))
    <x-ui.alert tone="warning" class="mt-4" title="Development mode">
        Mail is only being logged, so here is the code: <span class="font-mono font-bold">{{ session('dev_code') }}</span>
    </x-ui.alert>
@endif
