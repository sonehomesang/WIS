@php
    $fmt = fn ($d) => $d?->format('d/m/Y') ?? '—';
    $freqLo = \App\Models\AreaInspectionTemplate::FREQ_LABELS;
    $ckMark = ['C' => '✓ C', 'NC' => '✗ NC', 'NA' => 'N/A'];
    $ckCls = ['C' => 'ok', 'NC' => 'ng', 'NA' => ''];
    $checklist = collect($record->checklist ?? []);
    $inspectors = collect($record->inspectors ?? [])->filter()->values();

    $b64 = function ($path) {
        try {
            $abs = \Illuminate\Support\Facades\Storage::disk('public')->path($path);
            if (is_file($abs)) {
                $ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION)) === 'png' ? 'png' : 'jpeg';
                return "data:image/{$ext};base64,".base64_encode(file_get_contents($abs));
            }
        } catch (\Throwable $e) {
        }
        return null;
    };
    // ຮູບ ພາບ ລວມ + ຮູບ ຫຼັກຖານ ຂໍ້ NC.
    $overview = collect($record->overview_photos ?? [])->map($b64)->filter()->take(6);
    $evidence = $checklist->pluck('photo')->filter()->map($b64)->filter()->take(6);

    $lh = \App\Models\Setting::get('letterhead', []);
    $logoSrc = ! empty($lh['logo_path']) ? $b64($lh['logo_path']) : null;
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 14mm 12mm 20mm 12mm; }
        * { font-family: 'Phetsarath OT', DejaVu Sans, sans-serif; }
        body { font-size: 10px; color: #111; margin: 0; }
        table { width: 100%; border-collapse: collapse; }
        .info td { border: 1px solid #555; padding: 3px 5px; font-size: 9px; vertical-align: top; }
        .lbl { background: #f3f4f6; font-weight: bold; white-space: nowrap; }
        .items th { border: 1px solid #555; background: #f3f4f6; padding: 4px; font-size: 9px; }
        .items td { border: 1px solid #555; padding: 3px 4px; vertical-align: top; }
        .center { text-align: center; }
        .muted { color: #666; font-size: 8px; }
        .note { color: #185fa5; font-size: 8px; }
        .ng { color: #b91c1c; font-weight: bold; }
        .ok { color: #15803d; font-weight: bold; }
        .sig { margin-top: 20px; width: 100%; page-break-inside: avoid; }
        .sig td { text-align: center; vertical-align: top; padding: 0 10px; font-size: 9px; }
        .sigbox { border: 1px solid #555; height: 52px; margin-top: 4px; }
        .page-footer { position: fixed; bottom: -14mm; left: 0; right: 0; }
        .page-footer table { border-top: 1px solid #cbd5e1; }
        .page-footer td { padding-top: 4px; font-size: 8px; color: #6b7280; }
        .pagenum:before { content: counter(page); }
    </style>
</head>
<body>

    {{-- HEADER --}}
    <div style="text-align:center; padding-bottom:7px; border-bottom:2.5px solid #1e3a5f; margin-bottom:10px;">
        @if ($logoSrc)<div style="margin-bottom:3px;"><img src="{{ $logoSrc }}" style="height:52px;"></div>@endif
        <div style="font-size:15px; font-weight:bold; color:#1e3a5f; letter-spacing:1px;">SCU-WID</div>
        <div style="font-size:13px; font-weight:bold; margin-top:2px;">ໃບ ກວດ ສະຖານທີ່ ເຮັດ ວຽກ</div>
        <div style="font-size:8px; color:#6b7280;">Area / Workplace Inspection Checklist · C = Compliant · NC = Non-Compliant · NA = Not Applicable</div>
    </div>

    {{-- ຂໍ້ມູນ ການ ກວດ --}}
    <table class="info" style="margin-bottom:8px;">
        <tr>
            <td class="lbl">ເລກ</td><td>{{ $record->inspection_number ?? '—' }}</td>
            <td class="lbl">ສະຖານທີ່</td><td>{{ $record->locationName() }}</td>
        </tr>
        <tr>
            <td class="lbl">ວັນທີ / ເວລາ</td><td>{{ $fmt($record->inspected_on) }} {{ $record->inspected_time }}</td>
            <td class="lbl">ຮອບ</td><td>{{ $freqLo[$record->frequency] ?? $record->frequency }}</td>
        </tr>
        <tr>
            <td class="lbl">ຜູ້ ກວດ</td><td>{{ $inspectors->count() ? $inspectors->join(', ') : '—' }}</td>
            <td class="lbl">ຜົນ</td>
            <td class="{{ $record->result === 'has_nc' ? 'ng' : 'ok' }}">{{ $record->result === 'has_nc' ? ('ມີ NC · '.$record->ncCount().' ຂໍ້') : 'ຜ່ານ (C)' }}</td>
        </tr>
        <tr>
            <td class="lbl">ກວດ ຄັ້ງ ໜ້າ</td><td>{{ $fmt($record->next_due_date) }}</td>
            <td class="lbl">ແມ່ແບບ</td><td>{{ $record->template?->name ?? '—' }}</td>
        </tr>
    </table>

    {{-- ລາຍການ ກວດ --}}
    @if ($checklist->count())
        <table class="items">
            <thead>
                <tr>
                    <th style="width:5%">#</th>
                    <th style="width:22%">ລາຍການ</th>
                    <th>ເງື່ອນໄຂ / Requirement</th>
                    <th style="width:10%">ຜົນ</th>
                    <th style="width:24%">ໝາຍເຫດ / ແກ້ໄຂ</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($checklist as $ci => $c)
                    @php $cst = $c['status'] ?? 'C'; @endphp
                    <tr>
                        <td class="center">{{ $ci + 1 }}</td>
                        <td>{{ $c['label'] ?? '' }}</td>
                        <td class="muted">{{ $c['requirement'] ?? '' }}</td>
                        <td class="center {{ $ckCls[$cst] ?? '' }}">{{ $ckMark[$cst] ?? $cst }}</td>
                        <td class="note">{{ $c['observation'] ?? '' }}@if (! empty($c['photo'])) 📷@endif</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- ຮູບ ພາບ ລວມ + ຮູບ ຫຼັກຖານ NC --}}
    @if ($overview->count() || $evidence->count())
        <div style="margin-top:8px;">
            @if ($overview->count())
                <div class="muted" style="margin-bottom:3px;">ຮູບ ພາບ ລວມ ຂອງ ສະຖານທີ່:</div>
                <table><tr>
                    @foreach ($overview as $src)<td style="width:16.6%; padding:2px; vertical-align:top;"><img src="{{ $src }}" style="width:100%; height:70px; object-fit:cover; border:1px solid #ccc;"></td>@endforeach
                </tr></table>
            @endif
            @if ($evidence->count())
                <div class="muted" style="margin:5px 0 3px;">ຮູບ ຫຼັກຖານ ຂໍ້ ທີ່ ບໍ່ ຜ່ານ (NC):</div>
                <table><tr>
                    @foreach ($evidence as $src)<td style="width:16.6%; padding:2px; vertical-align:top;"><img src="{{ $src }}" style="width:100%; height:70px; object-fit:cover; border:1px solid #ccc;"></td>@endforeach
                </tr></table>
            @endif
        </div>
    @endif

    @if ($record->notes)
        <div style="margin-top:8px; font-size:9px;"><b>ໝາຍເຫດ ລວມ:</b> {{ $record->notes }}</div>
    @endif

    {{-- ລາຍເຊັນ — ຜູ້ ກວດ + ຜູ້ ຮັບຊາບ --}}
    <table class="sig">
        <tr>
            <td style="width:50%;">ຜູ້ ກວດ<div class="sigbox"></div><div class="muted">{{ $inspectors->count() ? $inspectors->join(' · ') : '............................' }} · {{ $fmt($record->inspected_on) }}</div></td>
            <td style="width:50%;">ຜູ້ ຮັບຊາບ (Manager / Leader)<div class="sigbox"></div><div class="muted">{{ $record->acknowledged_by_name ? $record->acknowledged_by_name.' · '.$fmt($record->acknowledged_at) : '............................' }}</div></td>
        </tr>
    </table>

    {{-- FOOTER --}}
    <div class="page-footer">
        <table>
            <tr>
                <td style="text-align:left;">ໃບ ກວດ ສະຖານທີ່ {{ $record->inspection_number }} · {{ $record->locationName() }} · {{ $fmt($record->inspected_on) }}</td>
                <td style="text-align:right; white-space:nowrap;">Page <span class="pagenum"></span> / {{ $totalPages ?? '' }}</td>
            </tr>
        </table>
    </div>

</body>
</html>
