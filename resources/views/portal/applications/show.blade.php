@use('App\Support\FormSchema')
@use('App\Enums\ApplicationStatus')
<x-layouts.portal :title="$application->program->title" heading="Application details">
    @php
        $steps = [ApplicationStatus::Submitted, ApplicationStatus::UnderReview, ApplicationStatus::Shortlisted];
        $order = [ApplicationStatus::Submitted->value => 1, ApplicationStatus::UnderReview->value => 2, ApplicationStatus::Shortlisted->value => 3, ApplicationStatus::Approved->value => 4, ApplicationStatus::Rejected->value => 4];
        $current = $order[$application->status->value] ?? 0;
    @endphp

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-ui.card>
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="font-mono text-xs font-semibold text-slate-500">{{ $application->reference }}</p>
                        <h2 class="mt-1 text-lg font-bold text-slate-900">{{ $application->program->title }}</h2>
                        <p class="mt-0.5 text-sm text-slate-500">{{ $application->submitted_at ? 'Submitted '.$application->submitted_at->format('j F Y, H:i') : 'Not submitted' }}</p>
                    </div>
                    <x-ui.badge :tone="$application->status->tone()" size="md" dot>{{ $application->status->label() }}</x-ui.badge>
                </div>

                {{-- Progress tracker --}}
                @unless ($application->status === ApplicationStatus::Withdrawn)
                    <ol class="mt-8 grid grid-cols-4 gap-2" aria-label="Application progress">
                        @foreach (['Submitted', 'Under review', 'Shortlisted', 'Decision'] as $i => $label)
                            @php $done = $current >= $i + 1; @endphp
                            <li class="text-center">
                                <div @class(['h-1.5 rounded-full', 'bg-gradient-to-r from-uew-royal to-uew-purple' => $done, 'bg-slate-200' => ! $done])></div>
                                <p @class(['mt-2 text-[11px] font-semibold', 'text-uew-navy' => $done, 'text-slate-400' => ! $done])>{{ $i === 3 && $application->status->isFinal() ? $application->status->label() : $label }}</p>
                            </li>
                        @endforeach
                    </ol>
                @endunless

                <p class="mt-6 rounded-xl bg-uew-canvas p-4 text-sm text-slate-700">{{ $application->status->applicantMessage() }}</p>

                @if ($application->decision_message && $application->status !== ApplicationStatus::Draft)
                    <div class="mt-4 rounded-xl border-l-4 border-uew-gold bg-amber-50/60 p-4">
                        <p class="text-xs font-semibold tracking-wider text-amber-900 uppercase">Message from the review panel</p>
                        <p class="mt-1 text-sm whitespace-pre-line text-slate-800">{{ $application->decision_message }}</p>
                    </div>
                @endif
            </x-ui.card>

            <x-ui.card title="Your answers">
                <dl class="divide-y divide-slate-100">
                    @foreach ($application->program->fields() as $field)
                        <div class="py-4 first:pt-0 last:pb-0">
                            <dt class="text-xs font-semibold text-slate-500">{{ $field['label'] }}</dt>
                            <dd class="mt-1 text-sm whitespace-pre-line text-slate-900">
                                @if ($field['type'] === 'file' && $application->answer($field['key']))
                                    <a href="{{ route('portal.applications.file', [$application, $field['key']]) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 font-semibold text-uew-royal hover:underline"><x-ui.icon name="document" class="size-4" /> Open PDF</a>
                                @else
                                    {{ FormSchema::display($field, $application->answer($field['key'])) }}
                                @endif
                            </dd>
                        </div>
                    @endforeach
                </dl>
            </x-ui.card>
        </div>

        <div class="space-y-6">
            <x-ui.card title="Programme">
                <p class="text-sm text-slate-600">{{ $application->program->summary }}</p>
                <x-ui.button :href="route('site.programs.show', $application->program)" variant="secondary" size="sm" class="mt-4" icon="external">Programme page</x-ui.button>
            </x-ui.card>

            @if ($application->status->canWithdraw() && ! $application->isEditable())
                <x-ui.card title="Withdraw">
                    <p class="text-sm text-slate-600">If you no longer want to be considered, you can withdraw. This cannot be undone.</p>
                    <x-ui.confirm-form class="mt-4 block" :action="route('portal.applications.withdraw', $application)" method="POST" message="Withdraw this application? You will not be able to reapply to this programme." label="Withdraw application" />
                </x-ui.card>
            @endif

            <x-ui.alert tone="info">Questions? <a href="{{ route('site.contact') }}" class="font-semibold underline">Contact the Hub</a> and quote {{ $application->reference }}.</x-ui.alert>
        </div>
    </div>
</x-layouts.portal>
