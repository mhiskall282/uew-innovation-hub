<x-layouts.app title="User Accounts & Access Control">
    <x-slot:header>
        Access Control & Staff Directory
    </x-slot:header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Left 2 Cols: Users Table -->
        <div class="lg:col-span-2 space-y-4">
            <x-npontu.card title="Staff & Researcher Accounts" subtitle="Active users with institutional cockpit permissions" padding="p-0">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 border-b border-slate-200/80 text-slate-500 font-mono uppercase text-[10px] tracking-wider">
                            <tr>
                                <th class="py-3 px-4">User</th>
                                <th class="py-3 px-4">Role</th>
                                <th class="py-3 px-4">Department</th>
                                <th class="py-3 px-4 text-center">Projects</th>
                                <th class="py-3 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($users as $user)
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="py-3 px-4">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-7 h-7 rounded-full bg-[#102a72] text-white flex items-center justify-center font-bold text-xs">
                                                {{ substr($user->name, 0, 1) }}
                                            </div>
                                            <div>
                                                <span class="font-bold text-slate-900 block leading-tight">{{ $user->name }}</span>
                                                <span class="text-[11px] font-mono text-slate-400">{{ $user->email }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        @php
                                            $badgeVariant = match($user->role) {
                                                'super_admin' => 'gold',
                                                'officer' => 'active',
                                                'reviewer' => 'purple',
                                                default => 'neutral',
                                            };
                                        @endphp
                                        <x-npontu.badge :variant="$badgeVariant">
                                            {{ strtoupper(str_replace('_', ' ', $user->role)) }}
                                        </x-npontu.badge>
                                    </td>
                                    <td class="py-3 px-4 text-slate-600">
                                        {{ $user->department ?? 'General Directorate' }}
                                    </td>
                                    <td class="py-3 px-4 text-center font-mono font-bold text-slate-700">
                                        {{ $user->projects_count }}
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        @if ($user->id !== Auth::id())
                                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Are you sure you want to revoke access for this user?');" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-1 rounded text-slate-400 hover:text-rose-600" title="Revoke Account">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-[10px] font-mono text-slate-400 italic">Current Session</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-npontu.card>
        </div>

        <!-- Right 1 Col: Provision Account Form -->
        <div>
            <x-npontu.card title="Provision Staff Account" subtitle="Create role-authorized user account">
                <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Full Name <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" required placeholder="e.g. Dr. K. Mensah" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-[#3157d5] outline-hidden">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Institutional Email <span class="text-rose-500">*</span></label>
                        <input type="email" name="email" required placeholder="user@uew.edu.gh" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-[#3157d5] outline-hidden font-mono">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Access Role <span class="text-rose-500">*</span></label>
                        <select name="role" required class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-[#3157d5] outline-hidden font-mono">
                            <option value="officer">Officer (Project Manager)</option>
                            <option value="reviewer">Reviewer (Academic Dean / Reader)</option>
                            <option value="viewer">Viewer (Read-Only Affiliate)</option>
                            <option value="super_admin">Super Admin (Hub Director)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Faculty / Unit</label>
                        <input type="text" name="department" placeholder="e.g. Faculty of Science Education" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-[#3157d5] outline-hidden">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Initial Password <span class="text-rose-500">*</span></label>
                        <input type="password" name="password" required minlength="8" placeholder="Minimum 8 characters" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-[#3157d5] outline-hidden font-mono">
                    </div>

                    <div class="pt-2">
                        <x-npontu.button type="submit" variant="primary" class="w-full">
                            <span>Provision User Account</span>
                        </x-npontu.button>
                    </div>
                </form>
            </x-npontu.card>
        </div>

    </div>
</x-layouts.app>
