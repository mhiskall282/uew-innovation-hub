<x-layouts.workspace title="E-mail templates">
    <x-ui.page-header title="E-mail templates" description="Edit the wording of every automatic e-mail. The branded layout, buttons and unsubscribe links are added for you." />

    @foreach ($groups as $group => $templates)
        <x-ui.card :title="$group" padding="p-0">
            <ul class="divide-y divide-slate-100">
                @foreach ($templates as $template)
                    <li class="flex flex-wrap items-center gap-4 px-5 py-4">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-uew-navy/10 text-uew-navy"><x-ui.icon name="mail" class="size-5" /></span>
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('workspace.templates.edit', $template['key']) }}" class="font-semibold text-slate-900 hover:text-uew-royal">{{ $template['name'] }}</a>
                            <p class="text-xs text-slate-500">{{ $template['description'] }}</p>
                        </div>
                        <x-ui.badge :tone="$template['customised'] ? 'gold' : 'slate'">{{ $template['customised'] ? 'Customised' : 'Default' }}</x-ui.badge>
                        <x-ui.button :href="route('workspace.templates.edit', $template['key'])" variant="ghost" size="xs" icon="pencil">Edit</x-ui.button>
                    </li>
                @endforeach
            </ul>
        </x-ui.card>
    @endforeach
</x-layouts.workspace>
