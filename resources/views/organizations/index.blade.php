<x-layouts.app title="Partner Organizations">
    <x-slot:header>
        Strategic Partner Organizations
    </x-slot:header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Left 2 Cols: Organizations Table -->
        <div class="lg:col-span-2">
            <x-npontu.card title="Collaborating Agencies & Funders" subtitle="External organizations registered in the Hub ecosystem" padding="p-0">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 border-b border-slate-200/80 text-slate-500 font-mono uppercase text-[10px] tracking-wider">
                            <tr>
                                <th class="py-3 px-4">Organization</th>
                                <th class="py-3 px-4">Classification</th>
                                <th class="py-3 px-4">Contact / Web</th>
                                <th class="py-3 px-4 text-center">Projects</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($organizations as $org)
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="py-3 px-4 font-bold text-slate-900">
                                        {{ $org->name }}
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-mono font-semibold bg-blue-50 text-[#102a72] border border-blue-200">
                                            {{ $org->type }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 font-mono text-[11px] text-slate-500">
                                        @if ($org->website)
                                            <a href="{{ $org->website }}" target="_blank" class="text-[#3157d5] hover:underline block truncate max-w-[180px]">
                                                {{ $org->website }}
                                            </a>
                                        @endif
                                        <span>{{ $org->contact_email ?? '—' }}</span>
                                    </td>
                                    <td class="py-3 px-4 text-center font-mono font-bold text-slate-800">
                                        {{ $org->projects_count }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-8 text-center text-slate-500">No organizations registered.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($organizations->hasPages())
                    <div class="px-6 py-4 border-t border-slate-100">
                        {{ $organizations->links() }}
                    </div>
                @endif
            </x-npontu.card>
        </div>

        <!-- Right 1 Col: Add Organization Form -->
        <div>
            @auth
                <x-npontu.card title="Register New Organization" subtitle="Add strategic partner or donor agency">
                    <form method="POST" action="{{ route('organizations.store') }}" class="space-y-4">
                        @csrf

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Organization Name <span class="text-rose-500">*</span></label>
                            <input type="text" name="name" required placeholder="e.g. World Bank Education Directorate" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-[#3157d5] outline-hidden">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Classification Type <span class="text-rose-500">*</span></label>
                            <select name="type" required class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-[#3157d5] outline-hidden">
                                <option value="Funding Partner">Funding Partner</option>
                                <option value="Government Agency">Government Agency</option>
                                <option value="Academic Institution">Academic Institution</option>
                                <option value="NGO">NGO / Civil Society</option>
                                <option value="Industry Partner">Industry Partner</option>
                                <option value="Partner">General Partner</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Official Website</label>
                            <input type="url" name="website" placeholder="https://..." class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-[#3157d5] outline-hidden font-mono">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Liaison Email</label>
                            <input type="email" name="contact_email" placeholder="contact@..." class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-[#3157d5] outline-hidden font-mono">
                        </div>

                        <div class="pt-2">
                            <x-npontu.button type="submit" variant="primary" class="w-full">
                                <span>Save Partner Agency</span>
                            </x-npontu.button>
                        </div>
                    </form>
                </x-npontu.card>
            @else
                <div class="p-6 rounded-2xl bg-white border border-slate-200 text-center">
                    <p class="text-xs text-slate-500">Sign in with an authorized account to register new partner organizations.</p>
                </div>
            @endauth
        </div>

    </div>
</x-layouts.app>
