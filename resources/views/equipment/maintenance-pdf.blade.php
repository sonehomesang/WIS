@php
    $fmt = fn ($d) => $d?->format('d/m/Y') ?? '—';
    $eq = $record->equipment;
    $tLabels = \App\Models\EquipmentMaintenance::TYPES;
    $sLabels = \App\Models\EquipmentMaintenance::STATUSES;
    $fLabels = \App\Models\EquipmentMaintenance::FREQ_LABELS;
    $groups = \App\Models\MaintenanceTemplate::GROUPS;
    $checklist = collect($record->checklist ?? []);
    $statusMark = ['ok' => '✓', 'ng' => '✗', 'na' => '—'];

    // ໂລໂກ້ ຈາກ letterhead setting (admin ອັບໂຫຼດ) → base64 ໃຫ້ DomPDF.
    $lh = \App\Models\Setting::get('letterhead', []);
    $logoSrc = null;
    if (! empty($lh['logo_path'])) {
        try {
            $abs = \Illuminate\Support\Facades\Storage::disk('public')->path($lh['logo_path']);
            if (is_file($abs)) {
                $ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION)) === 'png' ? 'png' : 'jpeg';
                $logoSrc = "data:image/{$ext};base64,".base64_encode(file_get_contents($abs));
            }
        } catch (\Throwable $e) {
        }
    }
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
        .grp { background: #eef2f7; font-weight: bold; color: #1e3a5f; font-size: 9px; }
        .center { text-align: center; }
        .muted { color: #666; font-size: 8px; }
        .note { color: #185fa5; font-size: 8px; }
        .ng { color: #b91c1c; font-weight: bold; }
        .ok { color: #15803d; font-weight: bold; }
        .sig { margin-top: 22px; width: 100%; page-break-inside: avoid; }
        .sig td { width: 50%; text-align: center; vertical-align: top; padding: 0 14px; font-size: 9px; }
        .sigbox { border: 1px solid #555; height: 58px; margin-top: 4px; }
        .page-footer { position: fixed; bottom: -14mm; left: 0; right: 0; }
        .page-footer table { border-top: 1px solid #cbd5e1; }
        .page-footer td { padding-top: 4px; font-size: 8px; color: #6b7280; }
        .pagenum:before { content: counter(page); }
    </style>
</head>
<body>

    {{-- HEADER: ໂລໂກ້ ທາງກາງ + SCU-WID + ຊື່ ຟອມ ວຽກ · ຂັ້ນ ດ້ວຍ ເສັ້ນ --}}
    <div style="text-align:center; padding-bottom:7px; border-bottom:2.5px solid #1e3a5f; margin-bottom:10px;">
        @if ($logoSrc)<div style="margin-bottom:3px;"><img src="{{ $logoSrc }}" style="height:52px;"></div>@endif
        <div style="font-size:15px; font-weight:bold; color:#1e3a5f; letter-spacing:1px;">SCU-WID</div>
        <div style="font-size:13px; font-weight:bold; margin-top:2px;">{{ $record->title }}</div>
        <div style="font-size:8px; color:#6b7280;">ໃບ ບຳລຸງຮັກສາ / ກວດກາ ເຄື່ອງຈັກ · Maintenance / Inspection Form</div>
    </div>

    {{-- ຂໍ້ມູນ ບັນທຶກ --}}
    <table class="info" style="margin-bottom:8px;">
        <tr>
            <td class="lbl">ເຄື່ອງ</td><td>{{ $eq?->asset_code }} · {{ $eq?->name }}</td>
            <td class="lbl">ປະເພດ</td><td>{{ $tLabels[$record->type] ?? $record->type }}</td>
        </tr>
        <tr>
            <td class="lbl">ວັນທີ</td><td>{{ $fmt($record->maintenance_date) }}</td>
            <td class="lbl">ຮອບ</td><td>{{ $record->frequency ? ($fLabels[$record->frequency] ?? $record->frequency) : '—' }}</td>
        </tr>
        <tr>
            <td class="lbl">ຜູ້ ເຮັດ</td><td>{{ $record->performed_by ?: '—' }}</td>
            <td class="lbl">ສະຖານະ</td><td>{{ $sLabels[$record->status] ?? $record->status }}</td>
        </tr>
        <tr>
            <td class="lbl">ຄ່າ (ກີບ)</td><td>{{ $record->cost !== null ? number_format($record->cost) : '—' }}</td>
            <td class="lbl">Service ໜ້າ</td><td>{{ $fmt($record->next_service_date) }}</td>
        </tr>
        @if ($record->description)
            <tr><td class="lbl">ລາຍລະອຽດ</td><td colspan="3">{{ $record->description }}</td></tr>
        @endif
    </table>

    {{-- ເຊັກລິສ ແຍກ ໝວດ --}}
    @if ($checklist->count())
        <table class="items">
            <thead>
                <tr>
                    <th style="width:5%">#</th>
                    <th>ລາຍການ ກວດ / ໝາຍເຫດ</th>
                    <th style="width:8%">ກ/ປ</th>
                    <th style="width:9%">ຜົນ</th>
                    <th style="width:14%">ຫຼັກຖານ</th>
                </tr>
            </thead>
            <tbody>
                @php $n = 0; @endphp
                @foreach (array_keys($groups) as $gk)
                    @php $gItems = $checklist->where('group', $gk); @endphp
                    @if ($gItems->count())
                        <tr><td colspan="5" class="grp">{{ $groups[$gk] }} · {{ $gItems->count() }} ຂໍ້</td></tr>
                        @foreach ($gItems as $c)
                            @php $n++; $st = $c['status'] ?? 'na'; @endphp
                            <tr>
                                <td class="center">{{ $n }}</td>
                                <td>
                                    {{ $c['label'] ?? '' }}
                                    @if (! empty($c['remark']))<div class="muted">{{ $c['remark'] }}</div>@endif
                                    @if (! empty($c['note']))<div class="note">ໝາຍເຫດ: {{ $c['note'] }}</div>@endif
                                </td>
                                <td class="center">{{ $c['action'] ?? '' }}</td>
                                <td class="center {{ $st === 'ng' ? 'ng' : ($st === 'ok' ? 'ok' : '') }}">{{ $statusMark[$st] ?? '—' }}</td>
                                <td class="center">{{ ! empty($c['photo_problem']) ? '📷 ມີ ຮູບ/ຄລິບ' : '' }}</td>
                            </tr>
                        @endforeach
                    @endif
                @endforeach
            </tbody>
        </table>
        <div class="muted" style="margin-top:3px;">ກ/ປ: C=ກວດ · X=ປ່ຽນ · ຜົນ: ✓=ແລ້ວ · ✗=ບັນຫາ · —=ຍັງບໍ່ໝາຍ</div>
    @endif

    @if ($record->notes)
        <div style="margin-top:8px; font-size:9px;"><b>ໝາຍເຫດ ລວມ:</b> {{ $record->notes }}</div>
    @endif

    {{-- ລາຍເຊັນ — ຜູ້ ເຮັດ + ຜູ້ ຮັບຊາບ (ໄວ້ ໃສ່ e-signature ຕອນ export) --}}
    <table class="sig">
        <tr>
            <td>ຜູ້ ເຮັດ / ຜູ້ ກວດ<div class="sigbox"></div><div class="muted">{{ $record->performed_by ?: '............................' }} · {{ $fmt($record->maintenance_date) }}</div></td>
            <td>ຜູ້ ຮັບຊາບ (Manager / Leader)<div class="sigbox"></div><div class="muted">{{ $record->acknowledged_by_name ? $record->acknowledged_by_name.' · '.$fmt($record->acknowledged_at) : '............................' }}</div></td>
        </tr>
    </table>

    {{-- FOOTER: ວັນທີກວດ + ເວລາ · ຜູ້ກວດ · ເລກໜ້າ · ຂັ້ນ ດ້ວຍ ເສັ້ນ --}}
    <div class="page-footer">
        <table>
            <tr>
                <td style="text-align:left;">ວັນທີກວດ: {{ $fmt($record->maintenance_date) }} {{ $record->created_at?->format('H:i') }} · ຜູ້ກວດ: {{ $record->performed_by ?: '—' }}</td>
                <td style="text-align:right; white-space:nowrap;">Page <span class="pagenum"></span> / {{ $totalPages ?? '' }}</td>
            </tr>
        </table>
    </div>

</body>
</html>
