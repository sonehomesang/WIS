@php
    $fmt = fn ($d) => $d?->format('d/m/Y') ?? '—';
    $eq = $record->equipment;
    $resultLo = ['pass' => 'ຜ່ານ', 'fail' => 'ບໍ່ຜ່ານ — ຫ້າມ ໃຊ້ງານ', 'follow_up' => 'ຕ້ອງ ຕິດຕາມ'];
    $resultCls = ['pass' => 'ok', 'fail' => 'ng', 'follow_up' => 'warn'];
    $ckMark = ['pass' => '✓ OK', 'fail' => '✗ NG', 'na' => 'N/A'];
    $ckCls = ['pass' => 'ok', 'fail' => 'ng', 'na' => ''];
    $fuelLo = ['ev' => 'ໄຟຟ້າ (EV)', 'engine' => 'ນ້ຳມັນ (Engine)'];
    $freqLo = \App\Models\InspectionTemplate::FREQ_LABELS;
    $checklist = collect($record->checklist ?? []);

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
    $photos = collect(method_exists($record, 'allPhotos') ? $record->allPhotos() : ($record->photos ?? []))
        ->map($b64)->filter()->take(6);

    // ໂລໂກ້ ຈາກ letterhead setting (admin ອັບໂຫຼດ) — ຄືກັບ ບຳລຸງ + PDF ອື່ນ.
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
        .warn { color: #b45309; font-weight: bold; }
        .page-footer { position: fixed; bottom: -14mm; left: 0; right: 0; }
        .page-footer table { border-top: 1px solid #cbd5e1; }
        .page-footer td { padding-top: 4px; font-size: 8px; color: #6b7280; }
        .pagenum:before { content: counter(page); }
    </style>
</head>
<body>

    {{-- HEADER: ໂລໂກ້ ທາງກາງ + SCU-WID + ຊື່ ຟອມ · ຂັ້ນ ດ້ວຍ ເສັ້ນ --}}
    <div style="text-align:center; padding-bottom:7px; border-bottom:2.5px solid #1e3a5f; margin-bottom:10px;">
        @if ($logoSrc)<div style="margin-bottom:3px;"><img src="{{ $logoSrc }}" style="height:52px;"></div>@endif
        <div style="font-size:15px; font-weight:bold; color:#1e3a5f; letter-spacing:1px;">SCU-WID</div>
        <div style="font-size:13px; font-weight:bold; margin-top:2px;">ໃບ ກວດ ສະພາບ ເຄື່ອງ</div>
        <div style="font-size:8px; color:#6b7280;">Equipment Inspection Form</div>
    </div>

    {{-- ຂໍ້ມູນ ການ ກວດ --}}
    <table class="info" style="margin-bottom:8px;">
        <tr>
            <td class="lbl">ເຄື່ອງ</td><td>{{ $eq?->asset_code }} · {{ $eq?->name }}</td>
            <td class="lbl">ປະເພດ ເຄື່ອງ</td><td>{{ $eq?->category ?? '—' }}</td>
        </tr>
        <tr>
            <td class="lbl">ຜູ້ ກວດ</td><td>{{ $record->inspector_name ?: '—' }}</td>
            <td class="lbl">ວັນທີ/ເວລາ</td><td>{{ $record->inspected_at?->format('d/m/Y H:i') ?? '—' }}</td>
        </tr>
        <tr>
            <td class="lbl">ປະເພດ ລົດ</td><td>{{ $record->fuel_type ? ($fuelLo[$record->fuel_type] ?? $record->fuel_type) : '—' }}</td>
            <td class="lbl">ຮອບ ກວດ</td><td>{{ $record->frequency ? ($freqLo[$record->frequency] ?? $record->frequency) : '—' }}</td>
        </tr>
        <tr>
            <td class="lbl">ຜົນ</td>
            <td class="{{ $resultCls[$record->result] ?? '' }}">{{ $resultLo[$record->result] ?? $record->result }}@if (! is_null($record->score)) · {{ $record->score }}%@endif</td>
            <td class="lbl">ກວດ ຄັ້ງ ໜ້າ</td><td>{{ $fmt($record->next_due_date) }}</td>
        </tr>
        @if ($record->template)
            <tr><td class="lbl">ແມ່ແບບ</td><td colspan="3">{{ $record->template->name }}</td></tr>
        @endif
    </table>

    {{-- ລາຍການ ກວດ --}}
    @if ($checklist->count())
        <table class="items">
            <thead>
                <tr><th style="width:6%">#</th><th>ລາຍການ ກວດ / ໝາຍເຫດ</th><th style="width:16%">ຜົນ</th></tr>
            </thead>
            <tbody>
                @foreach ($checklist as $ci => $c)
                    @php $cst = $c['status'] ?? 'pass'; @endphp
                    <tr>
                        <td class="center">{{ $ci + 1 }}</td>
                        <td>
                            {{ $c['label'] ?? '' }}
                            @if (! empty($c['note']))<div class="note">ໝາຍເຫດ: {{ $c['note'] }}</div>@endif
                        </td>
                        <td class="center {{ $ckCls[$cst] ?? '' }}">{{ $ckMark[$cst] ?? $cst }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- ຮູບ ຫຼັກຖານ --}}
    @if ($photos->count())
        <div style="margin-top:8px;">
            <div class="muted" style="margin-bottom:3px;">ຮູບ ຫຼັກຖານ:</div>
            <table><tr>
                @foreach ($photos as $src)
                    <td style="width:16.6%; padding:2px; vertical-align:top;"><img src="{{ $src }}" style="width:100%; height:70px; object-fit:cover; border:1px solid #ccc;"></td>
                @endforeach
            </tr></table>
        </div>
    @endif

    @if ($record->notes)
        <div style="margin-top:8px; font-size:9px;"><b>ໝາຍເຫດ ລວມ:</b> {{ $record->notes }}</div>
    @endif

    {{-- FOOTER: ວັນທີກວດ + ເວລາ · ຜູ້ກວດ · ເລກໜ້າ · ຂັ້ນ ດ້ວຍ ເສັ້ນ --}}
    <div class="page-footer">
        <table>
            <tr>
                <td style="text-align:left;">ວັນທີກວດ: {{ $record->inspected_at?->format('d/m/Y H:i') ?? '—' }} · ຜູ້ກວດ: {{ $record->inspector_name ?: '—' }}</td>
                <td style="text-align:right; white-space:nowrap;">Page <span class="pagenum"></span> / {{ $totalPages ?? '' }}</td>
            </tr>
        </table>
    </div>

</body>
</html>
