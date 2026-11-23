<x-layouts.guest title="Staff Login">
    <div class="mb-5">
        <h3 class="text-lg font-bold text-slate-900">Institutional Access</h3>
        <p class="text-xs text-slate-500 mt-1">Authenticate with your role-assigned credentials to access Cockpit operations.</p>
    </div>

    @if ($errors->any())
        <div class="mb-4 p-3 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-xs">
            {{ $errors->first() }}
        </div>
    @endif

    <div x-data="{
        email: '{{ old('email', 'admin@uew.edu.gh') }}',
        password: 'password',
        setRole(roleEmail) {
            this.email = roleEmail;
            this.password = 'password';
        }
    }">
        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <div>
                <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">Staff Email Address</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    x-model="email"
                    required 
                    autofocus
                    class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-[#3157d5] focus:border-transparent transition-all outline-hidden font-mono"
                    placeholder="name@uew.edu.gh"
                >
            </div>

            <div>
                <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">Secure Passkey</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    x-model="password"
                    required 
                    class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-[#3157d5] focus:border-transparent transition-all outline-hidden font-mono"
                    placeholder="••••••••••••"
                >
            </div>

            <div class="flex items-center justify-between text-xs pt-1">
                <label class="flex items-center gap-2 cursor-pointer text-slate-600">
                    <input type="checkbox" name="remember" class="rounded border-slate-300 text-[#102a72] focus:ring-[#3157d5]">
                    <span>Remember session</span>
                </label>
                <span class="text-slate-400 font-mono text-[11px]">Pass: password</span>
            </div>

            <div class="pt-2">
                <x-npontu.button type="submit" variant="primary" class="w-full py-2.5">
                    <span>Enter Cockpit</span>
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                </x-npontu.button>
            </div>
        </form>

        <!-- Fast Role Switcher for Testing / Demonstration -->
        <div class="mt-6 pt-5 border-t border-slate-200">
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-2.5">Click to Switch Role Credentials:</p>
            <div class="grid grid-cols-2 gap-2 text-left">
                
                <!-- Super Admin -->
                <button 
                    type="button"
                    @click="setRole('admin@uew.edu.gh')"
                    :class="email === 'admin@uew.edu.gh' ? 'ring-2 ring-[#3157d5] bg-blue-50/80 border-[#3157d5]' : 'bg-slate-50 border-slate-200 hover:bg-slate-100'"
                    class="p-2.5 rounded-xl border text-[11px] transition-all"
                >
                    <span class="font-bold text-[#102a72] block leading-tight">Hub Director</span>
                    <span class="text-[10px] text-amber-700 font-mono font-semibold block">Super Admin (All Privileges)</span>
                </button>

                <!-- Project Officer -->
                <button 
                    type="button"
                    @click="setRole('officer@uew.edu.gh')"
                    :class="email === 'officer@uew.edu.gh' ? 'ring-2 ring-[#3157d5] bg-blue-50/80 border-[#3157d5]' : 'bg-slate-50 border-slate-200 hover:bg-slate-100'"
                    class="p-2.5 rounded-xl border text-[11px] transition-all"
                >
                    <span class="font-bold text-[#3157d5] block leading-tight">Research Officer</span>
                    <span class="text-[10px] text-slate-500 font-mono block">Manage Projects & Reviews</span>
                </button>

                <!-- Academic Reviewer -->
                <button 
                    type="button"
                    @click="setRole('reviewer@uew.edu.gh')"
                    :class="email === 'reviewer@uew.edu.gh' ? 'ring-2 ring-[#3157d5] bg-blue-50/80 border-[#3157d5]' : 'bg-slate-50 border-slate-200 hover:bg-slate-100'"
                    class="p-2.5 rounded-xl border text-[11px] transition-all"
                >
                    <span class="font-bold text-purple-700 block leading-tight">Academic Dean</span>
                    <span class="text-[10px] text-slate-500 font-mono block">Grant Proposals Review</span>
                </button>

                <!-- Intern / NSS Personnel -->
                <button 
                    type="button"
                    @click="setRole('intern@uew.edu.gh')"
                    :class="email === 'intern@uew.edu.gh' ? 'ring-2 ring-[#3157d5] bg-blue-50/80 border-[#3157d5]' : 'bg-slate-50 border-slate-200 hover:bg-slate-100'"
                    class="p-2.5 rounded-xl border text-[11px] transition-all"
                >
                    <span class="font-bold text-emerald-700 block leading-tight">NSS / Intern</span>
                    <span class="text-[10px] text-slate-500 font-mono block">Draft CMS News & Posts</span>
                </button>

            </div>
        </div>
    </div>
</x-layouts.guest>
