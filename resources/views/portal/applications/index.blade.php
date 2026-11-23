<x-layouts.portal title="My applications" description="Every programme you have applied to, and drafts you have saved.">
    <x-ui.card padding="p-0">
        @if ($applications->isEmpty())
            <x-ui.empty title="No applications yet" icon="inbox">
                <x-slot:action><x-ui.button :href="route('site.programs.index')" size="sm">Browse programmes</x-ui.button></x-slot:action>
            </x-ui.empty>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50/80 text-left text-[11px] font-semibold tracking-wider text-slate-500 uppercase">
                        <tr>
                            <th scope="col" class="px-5 py-3">Programme</th>
                            <th scope="col" class="px-3 py-3">Reference</th>
                            <th scope="col" class="px-3 py-3">Status</th>
                            <th scope="col" class="hidden px-3 py-3 md:table-cell">Submitted</th>
                            <th scope="col" class="px-5 py-3"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($applications as $application)
                            <tr class="hover:bg-slate-50/60">
                                <td class="px-5 py-3.5">
                                    <p class="font-semibold text-slate-900">{{ $application->program->title }}</p>
                                    <p class="text-xs text-slate-500">{{ $application->program->type->label() }}</p>
                                </td>
                                <td class="px-3 py-3.5 font-mono text-xs text-slate-600">{{ $application->reference }}</td>
                                <td class="px-3 py-3.5"><x-ui.badge :tone="$application->status->tone()" dot>{{ $application->status->label() }}</x-ui.badge></td>
                                <td class="hidden px-3 py-3.5 text-xs text-slate-500 md:table-cell">{{ $application->submitted_at?->format('j M Y') ?? '—' }}</td>
                                <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                    @if ($application->isEditable())
                                        <x-ui.button :href="route('portal.apply', $application->program)" size="xs" icon="pencil">Continue</x-ui.button>
                                    @else
                                        <x-ui.button :href="route('portal.applications.show', $application)" variant="ghost" size="xs" icon-right="arrow-right">View</x-ui.button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-5 py-3">{{ $applications->links() }}</div>
        @endif
    </x-ui.card>
</x-layouts.portal>
