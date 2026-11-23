{{-- Expects $user, $roles --}}
@php $selectedRole = old('role', $user->role?->value ?? 'viewer'); @endphp
<div class="grid gap-6 xl:grid-cols-3">
    <div class="space-y-6 xl:col-span-2">
        <x-ui.card title="Profile">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-ui.input name="name" label="Full name" :value="$user->name" required />
                <x-ui.input name="email" type="email" label="Email address" :value="$user->email" required hint="Used to sign in." />
                <x-ui.input name="department" label="Department or office" :value="$user->department" wrap="sm:col-span-2" />
            </div>
        </x-ui.card>

        <x-ui.card title="Role" description="Choose the least access this person needs.">
            <fieldset class="grid gap-3 sm:grid-cols-2">
                <legend class="sr-only">Role</legend>
                @foreach ($roles as $role)
                    <label class="flex cursor-pointer gap-3 rounded-xl border border-slate-200 p-4 has-[:checked]:border-uew-royal has-[:checked]:bg-blue-50/50">
                        <input type="radio" name="role" value="{{ $role->value }}" @checked($selectedRole === $role->value) class="mt-0.5 size-4 border-slate-300 text-uew-navy focus:ring-uew-royal">
                        <span>
                            <x-ui.badge :tone="$role->tone()">{{ $role->label() }}</x-ui.badge>
                            <span class="mt-1.5 block text-xs leading-relaxed text-slate-500">{{ $role->description() }}</span>
                        </span>
                    </label>
                @endforeach
            </fieldset>
            @error('role')<p class="mt-2 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
        </x-ui.card>
    </div>

    <div class="space-y-6">
        <x-ui.card :title="$user->exists ? 'Reset password' : 'Temporary password'">
            <div class="space-y-4">
                <x-ui.input name="password" type="password" :label="$user->exists ? 'New password' : 'Password'" :required="! $user->exists" autocomplete="new-password"
                    :hint="$user->exists ? 'Leave blank to keep the current password. Changing it signs the person out of other devices.' : 'Share it with the person directly. They can change it under My account.'" />
                <x-ui.input name="password_confirmation" type="password" label="Confirm password" :required="! $user->exists" autocomplete="new-password" />
            </div>
        </x-ui.card>

        <div class="flex flex-col gap-2">
            <x-ui.button type="submit" size="lg" icon="check">{{ $user->exists ? 'Save changes' : 'Create account' }}</x-ui.button>
            <x-ui.button :href="route('workspace.users.index')" variant="ghost">Cancel</x-ui.button>
        </div>
    </div>
</div>
