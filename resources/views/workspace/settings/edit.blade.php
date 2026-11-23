<x-layouts.workspace title="Site settings">
    <x-ui.page-header title="Site settings" description="Content and configuration used across the public site, accounts and e-mail." />

    @php
        $tabs = ['general' => ['General & contact', 'building'], 'homepage' => ['Homepage', 'home'], 'mail' => ['E-mail delivery', 'mail'], 'system' => ['System', 'cog']];
        $active = in_array(request('tab'), array_keys($tabs), true) ? request('tab') : ($errors->hasAny(['mail_mailer', 'mail_host', 'mail_port', 'mail_from_address']) ? 'mail' : 'general');
    @endphp

    <div x-data="{ tab: @js($active) }">
        <nav class="flex flex-wrap gap-2 border-b border-slate-200" aria-label="Settings sections">
            @foreach ($tabs as $key => [$label, $icon])
                <button type="button" @click="tab = @js($key)"
                        class="-mb-px inline-flex items-center gap-2 border-b-2 px-3 pb-3 text-sm font-semibold"
                        :class="tab === @js($key) ? 'border-uew-navy text-uew-navy' : 'border-transparent text-slate-500 hover:text-slate-800'">
                    <x-ui.icon :name="$icon" class="size-4" /> {{ $label }}
                </button>
            @endforeach
        </nav>

        {{-- General + homepage share one form --}}
        <form method="POST" action="{{ route('workspace.settings.update') }}" class="mt-6 space-y-6" x-show="tab === 'general' || tab === 'homepage'">
            @csrf
            @method('PUT')

            <div class="grid gap-6 xl:grid-cols-3" x-show="tab === 'general'">
                <div class="space-y-6 xl:col-span-2">
                    <x-ui.card title="Contact details" description="Shown in the header, footer, contact page and e-mails.">
                        <div class="grid gap-5 sm:grid-cols-2">
                            <x-ui.input name="contact_email" type="email" label="Public e-mail" :value="$settings['contact_email']" required />
                            <x-ui.input name="contact_phone" label="Phone" :value="$settings['contact_phone']" />
                            <x-ui.input name="address" label="Address" :value="$settings['address']" required wrap="sm:col-span-2" />
                            <x-ui.input name="office_hours" label="Office hours" :value="$settings['office_hours']" />
                            <x-ui.input name="map_url" type="url" label="Map link" :value="$settings['map_url']" />
                        </div>
                    </x-ui.card>
                    <x-ui.card title="Social media" description="Leave blank to hide.">
                        <div class="grid gap-5 sm:grid-cols-2">
                            @foreach (['facebook' => 'Facebook', 'x' => 'X (Twitter)', 'linkedin' => 'LinkedIn', 'youtube' => 'YouTube', 'instagram' => 'Instagram'] as $key => $label)
                                <x-ui.input :name="'social_' . $key" type="url" :label="$label" :value="$settings['social_' . $key]" placeholder="https://" />
                            @endforeach
                        </div>
                    </x-ui.card>
                </div>
                <div class="space-y-6">
                    <x-ui.card title="Accounts">
                        <x-ui.checkbox name="registration_open" label="Allow new member sign-ups" :checked="$settings['registration_open']"
                            hint="Switch off to pause registrations. Existing members can still sign in." />
                    </x-ui.card>
                    <x-ui.card title="Announcement banner" description="A highlighted strip at the top of every public page.">
                        <div class="space-y-4">
                            <x-ui.checkbox name="announcement_enabled" label="Show the banner" :checked="$settings['announcement_enabled']" />
                            <x-ui.input name="announcement_text" label="Text" :value="$settings['announcement_text']" maxlength="200" placeholder="e.g. 2026 Fellowship applications close 31 March" />
                            <x-ui.input name="announcement_url" type="url" label="Link (optional)" :value="$settings['announcement_url']" />
                        </div>
                    </x-ui.card>
                    <x-ui.button type="submit" size="lg" icon="check" class="w-full">Save settings</x-ui.button>
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-3" x-show="tab === 'homepage'" x-cloak>
                <div class="space-y-6 xl:col-span-2">
                    <x-ui.card title="Hero" description="The first thing visitors see.">
                        <div class="space-y-5">
                            <x-ui.input name="hero_eyebrow" label="Small heading" :value="$settings['hero_eyebrow']" required maxlength="80" />
                            <x-ui.input name="hero_title" label="Headline" :value="$settings['hero_title']" required maxlength="140" />
                            <x-ui.textarea name="hero_subtitle" label="Introduction" :value="$settings['hero_subtitle']" rows="3" required />
                        </div>
                    </x-ui.card>
                    <x-ui.card title="Call to action" description="The panel near the bottom of the homepage.">
                        <div class="space-y-5">
                            <x-ui.input name="cta_title" label="Heading" :value="$settings['cta_title']" required maxlength="140" />
                            <x-ui.textarea name="cta_text" label="Text" :value="$settings['cta_text']" rows="3" required />
                        </div>
                    </x-ui.card>
                    <x-ui.card title="Footer">
                        <x-ui.textarea name="footer_about" label="About the Hub" :value="$settings['footer_about']" rows="3" required />
                    </x-ui.card>
                </div>
                <div class="space-y-6">
                    <x-ui.alert tone="info">Pages such as About, FAQ and Privacy are edited under <a href="{{ route('workspace.pages.index') }}" class="font-semibold underline">Pages</a>. Programmes, projects and news have their own sections.</x-ui.alert>
                    <x-ui.button type="submit" size="lg" icon="check" class="w-full">Save homepage</x-ui.button>
                </div>
            </div>
        </form>

        {{-- E-mail delivery --}}
        <div class="mt-6 grid gap-6 xl:grid-cols-3" x-show="tab === 'mail'" x-cloak>
            <form method="POST" action="{{ route('workspace.settings.mail') }}" class="space-y-6 xl:col-span-2" x-data="{ mailer: @js(old('mail_mailer', $settings['mail_mailer'] ?? 'env')) }">
                @csrf
                @method('PUT')
                @if (session('mail_error'))
                    <x-ui.alert tone="danger" title="Test failed">{{ session('mail_error') }}</x-ui.alert>
                @endif
                <x-ui.card title="Outgoing e-mail" description="Verification codes, application updates and broadcasts are sent with these settings.">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-ui.field label="Delivery method" for="mail_mailer" name="mail_mailer" class="sm:col-span-2">
                            <select id="mail_mailer" name="mail_mailer" x-model="mailer" class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                                @foreach ($mailers as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                            </select>
                        </x-ui.field>
                        <div class="grid gap-5 sm:col-span-2 sm:grid-cols-2" x-show="mailer === 'smtp'">
                            <x-ui.input name="mail_host" label="SMTP host" :value="$settings['mail_host']" placeholder="smtp.gmail.com" />
                            <x-ui.input name="mail_port" type="number" label="Port" :value="$settings['mail_port']" placeholder="587" />
                            <x-ui.select name="mail_encryption" label="Encryption" :options="$encryptions" :value="$settings['mail_encryption']" />
                            <x-ui.input name="mail_username" label="Username" :value="$settings['mail_username']" autocomplete="off" />
                            <x-ui.field label="Password or API key" for="mail_password" name="mail_password" class="sm:col-span-2"
                                :hint="$hasMailPassword ? 'A password is saved (encrypted). Leave blank to keep it.' : 'Stored encrypted. Never shown again after saving.'">
                                <input id="mail_password" name="mail_password" type="password" autocomplete="new-password" placeholder="{{ $hasMailPassword ? '•••••••••••• (saved)' : '' }}"
                                       class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-xs focus:border-uew-royal focus:ring-2 focus:ring-uew-royal/20 focus:outline-none">
                            </x-ui.field>
                            @if ($hasMailPassword)
                                <x-ui.checkbox name="clear_password" label="Remove the saved password" />
                            @endif
                        </div>
                        <x-ui.input name="mail_from_address" type="email" label="From address" :value="$settings['mail_from_address']" :placeholder="config('mail.from.address')" />
                        <x-ui.input name="mail_from_name" label="From name" :value="$settings['mail_from_name']" :placeholder="config('mail.from.name')" />
                    </div>
                    <x-slot:footer>
                        <div class="flex justify-end"><x-ui.button type="submit" icon="check">Save e-mail settings</x-ui.button></div>
                    </x-slot:footer>
                </x-ui.card>
            </form>
            <div class="space-y-6">
                <x-ui.card title="Send a test">
                    <p class="text-sm text-slate-600">Sends a test e-mail to <span class="font-semibold">{{ auth()->user()->email }}</span> using the saved settings.</p>
                    <form method="POST" action="{{ route('workspace.settings.mail.test') }}" class="mt-4">
                        @csrf
                        <x-ui.button type="submit" variant="secondary" icon="mail" class="w-full">Send test e-mail</x-ui.button>
                    </form>
                </x-ui.card>
                <x-ui.alert tone="info" title="Tips">
                    Gmail and Google Workspace need an <em>app password</em>. Services such as Brevo, Mailgun or Postmark give you SMTP credentials. Set up SPF and DKIM for the sending domain so e-mail is not marked as spam.
                </x-ui.alert>
                <x-ui.button :href="route('workspace.templates.index')" variant="ghost" icon="pencil" class="w-full">Edit e-mail wording</x-ui.button>
            </div>
        </div>

        {{-- System --}}
        <div class="mt-6 max-w-2xl" x-show="tab === 'system'" x-cloak>
            <x-ui.card title="System information" description="Read-only. Change these in the server environment.">
                <dl class="divide-y divide-slate-100 text-sm">
                    @foreach ($system as $label => $value)
                        <div class="flex justify-between gap-4 py-2.5"><dt class="text-slate-500">{{ $label }}</dt><dd class="font-mono text-xs font-semibold text-slate-900">{{ $value }}</dd></div>
                    @endforeach
                </dl>
            </x-ui.card>
        </div>
    </div>
</x-layouts.workspace>
