@use('App\Models\ProgressReport')
<x-layouts.portal :title="$report->exists ? 'Update report' : 'New progress report'" :heading="$report->exists ? 'Update ' . $report->period : 'New progress report'" :description="$application->program->title">
    @php
        $items = old('line_items', $report->line_items ?? $previous?->line_items ?? []);
        $items = array_values(array_map(fn ($i) => [
            'category' => $i['category'] ?? 'Other', 'description' => $i['description'] ?? '',
            'budgeted' => $i['budgeted'] ?? '', 'spent' => $i['spent'] ?? '',
        ], $items));
        $received = old('budget_received', $report->budget_received ?? $previous?->budget_received ?? 0);
        $spentManual = old('budget_spent', $report->budget_spent ?? '');
    @endphp

    <form method="POST" action="{{ $report->exists ? route('portal.reports.update', $report) : route('portal.reports.store', $application) }}" class="grid gap-6 lg:grid-cols-3"
          x-data="{
              items: @js($items),
              categories: @js(ProgressReport::CATEGORIES),
              received: @js((float) $received),
              manualSpent: @js($spentManual === '' ? '' : (float) $spentManual),
              add() { this.items.push({ category: 'Other', description: '', budgeted: '', spent: '' }); },
              remove(i) { this.items.splice(i, 1); },
              sum(key) { return this.items.reduce((t, i) => t + (parseFloat(i[key]) || 0), 0); },
              get spent() { return this.items.length ? this.sum('spent') : (parseFloat(this.manualSpent) || 0); },
              money(n) { return 'GH₵ ' + (n || 0).toLocaleString('en-GH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
              busy: false,
          }" @submit="busy = true">
        @csrf
        @if ($report->exists) @method('PUT') @endif

        <div class="space-y-6 lg:col-span-2">
            @if ($errors->any())
                <x-ui.alert tone="danger" title="Some details need attention">{{ $errors->first('report') ?: 'Fix the highlighted fields and submit again.' }}</x-ui.alert>
            @endif
            @if ($report->exists && $report->feedback)
                <div class="rounded-xl border-l-4 border-uew-gold bg-amber-50/60 p-4">
                    <p class="text-xs font-semibold tracking-wider text-amber-900 uppercase">Changes requested by the Hub</p>
                    <p class="mt-1 text-sm whitespace-pre-line text-slate-800">{{ $report->feedback }}</p>
                </div>
            @endif

            <x-ui.card title="Reporting period">
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-ui.input name="period" label="Period name" :value="$report->period" required maxlength="60" placeholder="e.g. Month 2 (March 2026)" />
                    <x-ui.input name="period_end" type="date" label="Period ends" :value="$report->period_end?->format('Y-m-d') ?? now()->format('Y-m-d')" required />
                </div>
            </x-ui.card>

            <x-ui.card title="Progress">
                <div class="space-y-5">
                    <x-ui.textarea name="summary" label="Summary" :value="$report->summary" rows="4" required hint="What happened this period, in a few sentences." />
                    <x-ui.textarea name="milestones" label="Milestones reached" :value="$report->milestones" rows="4" required hint="One per line, e.g. 'Prototype tested in 3 schools'." />
                    <x-ui.textarea name="challenges" label="Challenges" :value="$report->challenges" rows="3" hint="Anything slowing you down, and support you need from the Hub." />
                    <x-ui.textarea name="next_steps" label="Next steps" :value="$report->next_steps" rows="3" />
                    <x-ui.input name="beneficiaries" type="number" min="0" label="People reached so far" :value="$report->beneficiaries ?? $previous?->beneficiaries" hint="Students, teachers, farmers... whoever your project serves." wrap="sm:max-w-xs" />
                </div>
            </x-ui.card>

            <x-ui.card title="Budget" description="Add each spending line. Totals are calculated for you.">
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-ui.input name="budget_received" type="number" min="0" step="0.01" label="Funds received so far (GH₵)" required x-model.number="received" hint="Total from the Hub and other sources, to date." />
                    <div x-show="items.length === 0">
                        <x-ui.input name="budget_spent" type="number" min="0" step="0.01" label="Funds spent so far (GH₵)" x-model="manualSpent" hint="Or add line items below instead." />
                    </div>
                </div>

                <div class="mt-6 overflow-x-auto">
                    <table class="min-w-full text-sm" x-show="items.length">
                        <thead class="text-left text-[11px] font-semibold tracking-wider text-slate-500 uppercase">
                            <tr>
                                <th scope="col" class="pb-2 pr-2">Category</th>
                                <th scope="col" class="pb-2 pr-2">Item</th>
                                <th scope="col" class="pb-2 pr-2 text-right">Budgeted</th>
                                <th scope="col" class="pb-2 pr-2 text-right">Spent</th>
                                <th scope="col" class="pb-2"><span class="sr-only">Remove</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(item, i) in items" :key="i">
                                <tr class="align-top">
                                    <td class="py-1 pr-2">
                                        <label class="sr-only" :for="`li-cat-${i}`">Category</label>
                                        <select :id="`li-cat-${i}`" :name="`line_items[${i}][category]`" x-model="item.category" class="w-40 rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-xs focus:border-uew-royal focus:outline-none">
                                            <template x-for="c in categories" :key="c"><option :value="c" x-text="c" :selected="c === item.category"></option></template>
                                        </select>
                                    </td>
                                    <td class="py-1 pr-2">
                                        <label class="sr-only" :for="`li-desc-${i}`">Item description</label>
                                        <input :id="`li-desc-${i}`" type="text" :name="`line_items[${i}][description]`" x-model="item.description" maxlength="200" placeholder="e.g. Arduino kits" class="w-full min-w-40 rounded-lg border border-slate-300 px-2 py-1.5 text-xs focus:border-uew-royal focus:outline-none">
                                    </td>
                                    <td class="py-1 pr-2">
                                        <label class="sr-only" :for="`li-bud-${i}`">Budgeted</label>
                                        <input :id="`li-bud-${i}`" type="number" min="0" step="0.01" :name="`line_items[${i}][budgeted]`" x-model="item.budgeted" class="w-28 rounded-lg border border-slate-300 px-2 py-1.5 text-right font-mono text-xs focus:border-uew-royal focus:outline-none">
                                    </td>
                                    <td class="py-1 pr-2">
                                        <label class="sr-only" :for="`li-spent-${i}`">Spent</label>
                                        <input :id="`li-spent-${i}`" type="number" min="0" step="0.01" :name="`line_items[${i}][spent]`" x-model="item.spent" class="w-28 rounded-lg border border-slate-300 px-2 py-1.5 text-right font-mono text-xs focus:border-uew-royal focus:outline-none">
                                    </td>
                                    <td class="py-1">
                                        <button type="button" @click="remove(i)" class="rounded p-1.5 text-red-400 hover:bg-red-50 hover:text-red-600" title="Remove line"><x-ui.icon name="trash" class="size-4" /><span class="sr-only">Remove line</span></button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot class="border-t border-slate-200 font-mono text-xs font-semibold">
                            <tr>
                                <td colspan="2" class="pt-2 text-right font-sans text-slate-500">Totals</td>
                                <td class="pt-2 pr-2 text-right" x-text="money(sum('budgeted'))"></td>
                                <td class="pt-2 pr-2 text-right" x-text="money(sum('spent'))"></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                @foreach ($errors->get('line_items*') as $messages)
                    <p class="mt-2 text-xs font-medium text-red-600">{{ $messages[0] }}</p>
                @endforeach
                <x-ui.button type="button" variant="secondary" size="sm" icon="plus" class="mt-4" @click="add()">Add spending line</x-ui.button>
            </x-ui.card>
        </div>

        <aside>
            <div class="sticky top-24 space-y-6">
                <x-ui.card title="Budget at a glance">
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between"><dt class="text-slate-500">Received</dt><dd class="font-mono font-semibold text-slate-900" x-text="money(received)"></dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Spent</dt><dd class="font-mono font-semibold text-slate-900" x-text="money(spent)"></dd></div>
                        <div class="flex justify-between border-t border-slate-100 pt-3"><dt class="text-slate-500">Balance</dt><dd class="font-mono font-bold" :class="received - spent < 0 ? 'text-red-600' : 'text-emerald-700'" x-text="money(received - spent)"></dd></div>
                    </dl>
                    <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-100" role="presentation">
                        <div class="h-full rounded-full bg-gradient-to-r from-uew-royal to-uew-purple" :style="`width: ${received > 0 ? Math.min(100, spent / received * 100) : 0}%`"></div>
                    </div>
                    <p class="mt-2 text-xs text-slate-500" x-show="received > 0 && spent > received" x-cloak>Spending is above funds received. Explain why under Challenges.</p>
                    @if ($application->program->funding_amount)
                        <p class="mt-3 text-xs text-slate-500">Programme funding: GH₵ {{ number_format((float) $application->program->funding_amount, 2) }}</p>
                    @endif
                </x-ui.card>

                <x-ui.card>
                    <x-ui.button type="submit" size="lg" variant="gold" icon="send" class="w-full" ::class="busy && 'opacity-60 pointer-events-none'">{{ $report->exists ? 'Resubmit report' : 'Submit report' }}</x-ui.button>
                    <x-ui.button :href="route('portal.reports.index')" variant="ghost" class="mt-2 w-full">Cancel</x-ui.button>
                    <p class="mt-3 text-xs text-slate-500">After you submit, the Hub reviews the report. You can only edit it again if they ask for changes.</p>
                </x-ui.card>
            </div>
        </aside>
    </form>
</x-layouts.portal>
