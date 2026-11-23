<x-layouts.workspace title="Staff accounts">
    <x-ui.page-header title="Staff accounts" description="Who can sign in to the workspace and what each person can do.">
        <x-slot:actions>
            <x-ui.button :href="route('workspace.users.create')" icon="plus">Add staff member</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    {{-- Role reference --}}
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
        @foreach ($roles as $role)
            <a href="{{ route('workspace.users.index', ['role' => $role->value]) }}" @class(['rounded-xl border bg-white p-4 shadow-card transition hover:shadow-card-hover', 'border-uew-royal ring-2 ring-uew-royal/20' => $filters['role'] === $role->value, 'border-slate-200' => $filters['role'] !== $role->value])>
                <x-ui.badge :tone="$role->tone()">{{ $role->label() }}</x-ui.badge>
                <p class="mt-2 text-xs leading-relaxed text-slate-500">{{ $role->description() }}</p>
            </a>
        @endforeach
    </div>

    <form method="GET" class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-3 shadow-card sm:flex-row" role="search">
        @if ($filters['role'])<input type="hidden" name="role" value="{{ $filters['role'] }}">@endif
        <label class="relative flex-1">
            <span class="sr-only">Search</span>
            <x-ui.icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
            <input type="search" name="search" value="{{ $filters['search'] }}" placeholder="Name, email or department"
                   class="block w-full rounded-lg border border-slate-300 py-2 pr-3 pl-9 text-sm focus:border-uew-royal focus:ring-2 focus:ring-uew-royal/20 focus:outline-none">
        </label>
        <x-ui.button type="submit" icon="search">Search</x-ui.button>
        @if (array_filter($filters))
            <x-ui.button :href="route('workspace.users.index')" variant="ghost">Clear</x-ui.button>
        @endif
    </form>

    <x-ui.card padding="p-0">
        @if ($users->isEmpty())
            <x-ui.empty title="No staff accounts match" icon="users" />
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50/80 text-left text-[11px] font-semibold tracking-wider text-slate-500 uppercase">
                        <tr>
                            <th scope="col" class="px-5 py-3">Name</th>
                            <th scope="col" class="px-3 py-3">Role</th>
                            <th scope="col" class="hidden px-3 py-3 md:table-cell">Last sign-in</th>
                            <th scope="col" class="px-3 py-3">Status</th>
                            <th scope="col" class="px-5 py-3"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($users as $member)
                            <tr @class(['hover:bg-slate-50/60', 'opacity-60' => ! $member->is_active])>
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-uew-navy to-uew-royal text-xs font-bold text-white">{{ $member->initials() }}</span>
                                        <div class="min-w-0">
                                            <p class="font-semibold text-slate-900">{{ $member->name }} @if ($member->is(auth()->user()))<span class="text-xs font-normal text-slate-400">(you)</span>@endif</p>
                                            <p class="truncate text-xs text-slate-500">{{ $member->email }}@if ($member->department) · {{ $member->department }}@endif</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-3.5"><x-ui.badge :tone="$member->role->tone()">{{ $member->role->label() }}</x-ui.badge></td>
                                <td class="hidden px-3 py-3.5 text-xs text-slate-500 md:table-cell">{{ $member->last_login_at?->diffForHumans() ?? 'Never' }}</td>
                                <td class="px-3 py-3.5">
                                    <x-ui.badge :tone="$member->is_active ? 'green' : 'slate'" dot>{{ $member->is_active ? 'Active' : 'Deactivated' }}</x-ui.badge>
                                </td>
                                <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                    <x-ui.button :href="route('workspace.users.edit', $member)" variant="ghost" size="xs" icon="pencil">Edit</x-ui.button>
                                    @can('deactivate', $member)
                                        <x-ui.confirm-form :action="route('workspace.users.toggle', $member)" method="PATCH"
                                            :message="$member->is_active ? 'Deactivate ' . $member->name . '? They will be signed out immediately.' : 'Re-activate ' . $member->name . '?'"
                                            :label="$member->is_active ? 'Deactivate' : 'Activate'" :variant="$member->is_active ? 'danger-ghost' : 'ghost'" size="xs" icon="power" />
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-5 py-3">{{ $users->links() }}</div>
        @endif
    </x-ui.card>
</x-layouts.workspace>
