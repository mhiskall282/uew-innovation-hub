@use('App\Support\Demographics')
@php
    // Images are embedded as data URIs: remote loading is disabled in the renderer.
    $img = fn (string $path) => is_file(public_path($path)) ? 'data:image/' . pathinfo($path, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents(public_path($path))) : null;
    $uew = $img('images/uew/uew-logo.png');
    $hub = $img('images/innovation_hub_logo.jpg');
    $has = fn (string $s) => in_array($s, $sections, true);
    $n = fn ($v) => number_format((float) $v);
    $pct = fn ($part, $whole) => $whole > 0 ? round($part / $whole * 100) . '%' : '—';
    $bar = fn ($value, $max, $color = '#3157d5') => '<div class="bar"><span style="width:' . ($max > 0 ? min(100, round($value / $max * 100)) : 0) . '%;background:' . $color . '"></span></div>';
    $gTotal = $genders->sum();
    $aTotal = $ageBands->sum();
    $maxMonth = max(1, $months->max() ?? 1);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>UEW Innovation Hub report · {{ $range->describe() }}</title>
<style>
    @page { margin: 26mm 16mm 20mm 16mm; }
    * { box-sizing: border-box; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 9.5pt; color: #1e293b; line-height: 1.45; }
    header { position: fixed; top: -20mm; left: 0; right: 0; height: 14mm; border-bottom: 2px solid #102a72; }
    header td { vertical-align: middle; }
    footer { position: fixed; bottom: -14mm; left: 0; right: 0; height: 9mm; border-top: 1px solid #e2e8f0; font-size: 7.5pt; color: #64748b; }
    .pagenum:before { content: counter(page); }
    h1 { font-size: 20pt; color: #102a72; margin: 0 0 2mm; }
    h2 { font-size: 12pt; color: #102a72; margin: 8mm 0 3mm; padding-bottom: 1.5mm; border-bottom: 1px solid #fbbf24; page-break-after: avoid; }
    h3 { font-size: 9.5pt; color: #334155; margin: 4mm 0 2mm; }
    .muted { color: #64748b; }
    .mono { font-family: 'DejaVu Sans Mono', monospace; }
    .cover { background: #0a1128; color: #fff; padding: 7mm 8mm; border-bottom: 3px solid #f59e0b; }
    .cover .eyebrow { color: #fbbf24; font-size: 7.5pt; letter-spacing: 2px; text-transform: uppercase; font-weight: bold; }
    .cover h1 { color: #fff; margin-top: 2mm; }
    .cover .meta { color: #cbd5e1; font-size: 8.5pt; }
    table { width: 100%; border-collapse: collapse; }
    table.data { margin-top: 1mm; page-break-inside: auto; }
    table.data th { background: #f1f5f9; color: #475569; font-size: 7.5pt; text-transform: uppercase; letter-spacing: .5px; text-align: left; padding: 2mm 2.5mm; border-bottom: 1px solid #cbd5e1; }
    table.data td { padding: 2mm 2.5mm; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
    table.data tr { page-break-inside: avoid; }
    .r { text-align: right; }
    .kpis td { width: 20%; padding: 3mm; border: 1px solid #e2e8f0; background: #f8fafc; vertical-align: top; }
    .kpis .v { font-family: 'DejaVu Sans Mono', monospace; font-size: 14pt; font-weight: bold; color: #102a72; }
    .kpis .l { font-size: 7pt; text-transform: uppercase; letter-spacing: .5px; color: #64748b; }
    .kpis .h { font-size: 7.5pt; color: #64748b; }
    .bar { height: 2.2mm; background: #e2e8f0; border-radius: 1mm; overflow: hidden; }
    .bar span { display: block; height: 2.2mm; border-radius: 1mm; }
    .two td.col { width: 50%; vertical-align: top; padding-right: 4mm; }
    .note { font-size: 7.5pt; color: #64748b; margin-top: 2mm; }
    .red { color: #dc2626; font-weight: bold; }
    .keep { page-break-inside: avoid; }
    thead { display: table-header-group; }
    table.fixed { table-layout: fixed; }
    .green { color: #047857; }
    .chart td { vertical-align: bottom; text-align: center; padding: 0 .6mm; font-size: 6.5pt; color: #64748b; }
    .chart .col { background: #3157d5; width: 100%; }
</style>
</head>
<body>
<header>
    <table>
        <tr>
            <td style="width: 13mm">@if ($uew)<img src="{{ $uew }}" style="height: 11mm" alt="UEW crest">@endif</td>
            <td style="width: 13mm">@if ($hub)<img src="{{ $hub }}" style="height: 11mm" alt="Innovation Hub crest">@endif</td>
            <td>
                <div style="font-weight: bold; color: #102a72; font-size: 10.5pt">UEW Innovation Hub</div>
                <div class="muted" style="font-size: 7.5pt">University of Education, Winneba</div>
            </td>
            <td class="r muted" style="font-size: 7.5pt">Hub report<br>{{ $range->describe() }}</td>
        </tr>
    </table>
</header>
<footer>
    <table>
        <tr>
            <td>Confidential · for UEW and its partners · Demographic figures are anonymous totals (Data Protection Act, 2012, Act 843)</td>
            <td class="r">Page <span class="pagenum"></span></td>
        </tr>
    </table>
</footer>

<div class="cover">
    <div class="eyebrow">Hub report · {{ $range->label() }}</div>
    <h1>Programmes, people and impact</h1>
    <div class="meta">
        Period: <strong>{{ $range->describe() }}</strong><br>
        Generated {{ $generated_at->format('j F Y, H:i') }} by {{ $preparedBy->name }} ({{ $preparedBy->role->label() }})<br>
        Sections: {{ collect($sections)->map(fn ($s) => $sectionLabels[$s])->join(', ') }}
    </div>
</div>

<h2>Summary</h2>
<table class="kpis">
    <tr>
        <td><div class="l">New members</div><div class="v">{{ $n($totals['members_new']) }}</div><div class="h">{{ $n($totals['members_total']) }} in total</div></td>
        <td><div class="l">Applications</div><div class="v">{{ $n($totals['applications']) }}</div><div class="h">submitted</div></td>
        <td><div class="l">Accepted</div><div class="v">{{ $n($totals['approved']) }}</div><div class="h">{{ $totals['acceptance'] !== null ? $totals['acceptance'] . '% of decisions' : 'no decisions' }}</div></td>
        <td><div class="l">Disbursed</div><div class="v">{{ $n($totals['disbursed']) }}</div><div class="h">GH₵ · {{ $n($totals['spent']) }} spent</div></td>
        <td><div class="l">People reached</div><div class="v">{{ $n($totals['reached']) }}</div><div class="h">by funded teams</div></td>
    </tr>
</table>
<p class="note">Applications count when submitted in the period, acceptances when decided in it; membership and inclusion describe members who joined in it; budgets use the latest report per team whose period ended in it.</p>

@if ($has('programmes'))
    <h2>Programmes and applications</h2>
    @if ($programs->isEmpty())
        <p class="muted">No programme activity in this period.</p>
    @else
        <table class="data fixed">
            <thead><tr><th style="width: 34%">Programme</th><th>Type</th><th class="r">Applications</th><th class="r">Pending</th><th class="r">Accepted</th><th class="r">Not selected</th><th class="r">Acceptance</th></tr></thead>
            <tbody>
                @foreach ($programs as $row)
                    <tr>
                        <td><strong>{{ $row->program->title }}</strong><br><span class="muted" style="font-size: 7.5pt">{{ $row->program->status->label() }}{{ $row->program->closes_at ? ' · closes ' . $row->program->closes_at->format('j M Y') : '' }}</span></td>
                        <td>{{ $row->program->type->label() }}</td>
                        <td class="r mono">{{ $row->total }}{{ $row->program->capacity ? ' / ' . $row->program->capacity : '' }}</td>
                        <td class="r mono">{{ $row->pending }}</td>
                        <td class="r mono green">{{ $row->approved }}</td>
                        <td class="r mono">{{ $row->rejected }}</td>
                        <td class="r mono">{{ $row->acceptance !== null ? $row->acceptance . '%' : '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endif

@if ($has('membership'))
    <h2>Membership and interests</h2>
    <h3>New members per month</h3>
    <table class="chart" style="width: {{ min(100, max(20, count($months) * 7)) }}%">
        <tr style="height: 32mm">
            @foreach ($months as $month => $count)
                <td style="height: 32mm; width: {{ round(100 / max(1, count($months)), 2) }}%">
                    <div class="mono" style="font-size: 6.5pt; color: #334155">{{ $count }}</div>
                    <div class="col" style="height: {{ max(0.6, round($count / $maxMonth * 26, 1)) }}mm"></div>
                </td>
            @endforeach
        </tr>
        <tr>
            @foreach ($months as $month => $count)
                <td>{{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $month)->format(count($months) > 12 ? 'M y' : 'M') }}</td>
            @endforeach
        </tr>
    </table>

    <table class="two" style="margin-top: 4mm">
        <tr>
            <td class="col">
                <h3>Affiliation</h3>
                <table class="data">
                    <thead><tr><th>Affiliation</th><th class="r">Members</th><th class="r">Share</th></tr></thead>
                    <tbody>
                        @forelse ($affiliations as $aff => $count)
                            <tr><td>{{ \App\Enums\Affiliation::tryFrom($aff)?->label() ?? $aff }}</td><td class="r mono">{{ $count }}</td><td class="r mono">{{ $pct($count, $affiliations->sum()) }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="muted">No completed profiles.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </td>
            <td class="col">
                <h3>Interests</h3>
                <table class="data">
                    <thead><tr><th>Interest</th><th class="r">Members</th></tr></thead>
                    <tbody>
                        @foreach (array_slice($interests, 0, 10, true) as $interest => $count)
                            <tr><td>{{ $interest }}</td><td class="r mono">{{ $count }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </td>
        </tr>
    </table>
@endif

@if ($has('inclusion'))
    <h2>Inclusion (anonymous)</h2>
    <table class="two">
        <tr>
            <td class="col">
                <h3>Gender</h3>
                <table class="data">
                    <thead><tr><th style="width: 38%">Gender</th><th class="r">Members</th><th class="r">Share</th><th style="width: 24%"></th></tr></thead>
                    <tbody>
                        @foreach (Demographics::GENDERS + ['' => 'Not given'] as $key => $label)
                            @php $c = (int) ($genders[$key] ?? 0); @endphp
                            <tr><td>{{ $label }}</td><td class="r mono">{{ $c }}</td><td class="r mono">{{ $pct($c, $gTotal) }}</td><td>{!! $bar($c, $gTotal) !!}</td></tr>
                        @endforeach
                    </tbody>
                </table>
                <h3>Age</h3>
                <table class="data">
                    <thead><tr><th style="width: 38%">Age band</th><th class="r">Members</th><th class="r">Share</th><th style="width: 24%"></th></tr></thead>
                    <tbody>
                        @foreach ($ageBands as $band => $c)
                            <tr><td>{{ $band }}</td><td class="r mono">{{ $c }}</td><td class="r mono">{{ $pct($c, $aTotal) }}</td><td>{!! $bar($c, $aTotal, '#f59e0b') !!}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </td>
            <td class="col">
                <h3>Region</h3>
                <table class="data">
                    <thead><tr><th>Region</th><th class="r">Members</th><th class="r">Share</th></tr></thead>
                    <tbody>
                        @forelse ($regions as $region => $c)
                            <tr><td>{{ $region }}</td><td class="r mono">{{ $c }}</td><td class="r mono">{{ $pct($c, $regions->sum()) }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="muted">No regions recorded.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </td>
        </tr>
    </table>
    <p class="note">Counts only. No individual's demographic data appears in this report.</p>
@endif

@if ($has('incubation'))
    <div class="keep"><h2>Incubation budgets</h2>
    @if ($budgets->isEmpty())
        <p class="muted">No accepted teams yet.</p>
    @else
        <table class="data">
            <thead><tr><th style="width: 26%">Programme</th><th class="r">Teams reporting</th><th class="r">Funding (GH₵)</th><th class="r">Received</th><th class="r">Spent</th><th class="r">Balance</th><th class="r">Burn</th><th class="r">Reached</th></tr></thead>
            <tbody>
                @foreach ($budgets as $row)
                    <tr>
                        <td><strong>{{ $row->program->title }}</strong><br><span class="muted" style="font-size: 7.5pt">{{ $row->reports }} {{ Str::plural('report', $row->reports) }}</span></td>
                        <td class="r mono">{{ $row->reporting }} / {{ $row->accepted }}</td>
                        <td class="r mono">{{ $row->funding ? $n($row->funding) : '—' }}</td>
                        <td class="r mono">{{ $n($row->received) }}</td>
                        <td class="r mono">{{ $n($row->spent) }}</td>
                        <td class="r mono {{ $row->received - $row->spent < 0 ? 'red' : '' }}">{{ $n($row->received - $row->spent) }}</td>
                        <td class="r mono {{ ($row->burn ?? 0) > 100 ? 'red' : '' }}">{{ $row->burn !== null ? $row->burn . '%' : '—' }}</td>
                        <td class="r mono">{{ $n($row->reached) }}</td>
                    </tr>
                @endforeach
                <tr>
                    <td><strong>Total</strong></td><td></td>
                    <td class="r mono"><strong>{{ $n($budgets->sum('funding')) }}</strong></td>
                    <td class="r mono"><strong>{{ $n($totals['disbursed']) }}</strong></td>
                    <td class="r mono"><strong>{{ $n($totals['spent']) }}</strong></td>
                    <td class="r mono"><strong>{{ $n($totals['disbursed'] - $totals['spent']) }}</strong></td>
                    <td class="r mono"><strong>{{ $pct($totals['spent'], $totals['disbursed']) }}</strong></td>
                    <td class="r mono"><strong>{{ $n($totals['reached']) }}</strong></td>
                </tr>
            </tbody>
        </table>
        <p class="note">Figures are self-reported by teams and reviewed by Hub staff. Spending totals are recalculated from each report's spending lines.</p>
    @endif
    </div>
@endif

@if ($has('engagement'))
    <div class="keep"><h2>Most active members</h2>
    @if ($leaders->isEmpty())
        <p class="muted">No XP earned in this period.</p>
    @else
        <table class="data">
            <thead><tr><th style="width: 8mm">#</th><th>Member</th><th>Level</th><th class="r">Badges</th><th class="r">{{ $range->isAllTime() ? 'XP (all time)' : 'XP in period' }}</th></tr></thead>
            <tbody>
                @foreach ($leaders as $i => $leader)
                    <tr><td class="mono">{{ $i + 1 }}</td><td>{{ $leader->user->name }}</td><td>{{ $leader->user->level()['name'] }}</td><td class="r mono">{{ $leader->badges }}</td><td class="r mono"><strong>{{ $n($leader->xp) }}</strong></td></tr>
                @endforeach
            </tbody>
        </table>
    @endif
    </div>
@endif

<p class="note" style="margin-top: 8mm">Portfolio at time of export: {{ $n($portfolio['projects']['total'] ?? 0) }} projects, {{ $n($portfolio['beneficiaries']['reached'] ?? 0) }} beneficiaries reached.</p>
</body>
</html>
