<x-layouts.workspace title="Roles & permissions">
    <x-ui.page-header title="Roles & permissions" description="What each role can do. Give people the least access they need; you can change someone's role under Staff accounts.">
        <x-slot:actions>
            <x-ui.button :href="route('workspace.users.index')" variant="secondary" icon="users">Staff accounts</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($roles as $role)
            <div class="rounded-xl border border-slate-200/80 bg-white p-5 shadow-card">
                <div class="flex items-start justify-between gap-3">
                    <x-ui.badge :tone="$role->tone()" size="md">{{ $role->label() }}</x-ui.badge>
                    <span class="font-mono text-xs text-slate-500" title="Active accounts">{{ (int) ($counts[$role->value] ?? 0) }} active</span>
                </div>
                <p class="mt-3 text-sm text-slate-600">{{ $role->description() }}</p>
                <p class="mt-3 font-mono text-[11px] text-slate-500">{{ count($role->permissions()) }} of {{ count(\App\Enums\Permission::cases()) }} permissions</p>
            </div>
        @endforeach
    </div>

    <x-ui.card title="Permission matrix" description="Enforced on the server for every page and action, not just hidden in menus." padding="p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <caption class="sr-only">Which roles have each permission</caption>
                <thead class="border-b border-slate-200 bg-slate-50/80 text-left text-[11px] font-semibold tracking-wider text-slate-500 uppercase">
                    <tr>
                        <th scope="col" class="px-5 py-3">Permission</th>
                        @foreach ($roles as $role)
                            <th scope="col" class="px-3 py-3 text-center whitespace-nowrap">{{ $role->label() }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($groups as $group => $permissions)
                        <tr class="bg-slate-50/60">
                            <th scope="rowgroup" colspan="{{ count($roles) + 1 }}" class="px-5 py-2 text-left font-mono text-[10px] font-bold tracking-widest text-uew-navy uppercase">{{ $group }}</th>
                        </tr>
                        @foreach ($permissions as $permission)
                            <tr>
                                <th scope="row" class="px-5 py-2.5 text-left font-normal">
                                    <span class="text-slate-800">{{ $permission->label() }}</span>
                                    <span class="block font-mono text-[10px] text-slate-500">{{ $permission->value }}</span>
                                </th>
                                @foreach ($roles as $role)
                                    <td class="px-3 py-2.5 text-center">
                                        @if ($role->allows($permission))
                                            <span class="inline-flex size-6 items-center justify-center rounded-full bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200"><x-ui.icon name="check" class="size-3.5" /><span class="sr-only">Yes</span></span>
                                        @else
                                            <span class="text-slate-300" aria-hidden="true">—</span><span class="sr-only">No</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <x-ui.alert tone="info" title="Safeguards">
        Nobody can change their own role. The last active Hub Director can't be demoted, deactivated or deleted. Deactivating someone signs them out everywhere at once. Every role change is recorded in the audit log.
    </x-ui.alert>
</x-layouts.workspace>
