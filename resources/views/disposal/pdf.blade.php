@php
    $img = fn ($path) => \App\Support\PdfExport::thumb($path);
    $fmt = fn ($d) => $d?->format('d/m/Y') ?? '—';
    $signs = $record->signoffs->groupBy('role_key');
    $sigCell = function ($key, $label) use ($signs, $fmt) {
        $rows = $signs[$key] ?? collect();
        $names = $rows->map(fn ($s) => $s->name)->filter()->implode(' · ');
        $date = optional($rows->first())->signed_at;

        return ['label' => $label, 'names' => $names ?: '............................', 'date' => $rows->count() ? $fmt($date) : ''];
    };
    $cells = [
        $sigCell('preparer', 'ຜູ້ ເຮັດລິສ'),
        $sigCell('committee', 'ຄະນະກຳມະການ'),
        $sigCell('technical', 'ວິຊາການ/ວສ.'),
        $sigCell('manager', 'ຜູ້ ຈັດການ'),
        $sigCell('executive', 'ຜູ້ ບໍລິຫານ'),
    ];
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: 'Phetsarath OT', DejaVu Sans, sans-serif; }
        body { font-size: 11px; color: #111; margin: 0; }
        table { width: 100%; border-collapse: collapse; }
        .info td { border: 1px solid #555; padding: 2px 5px; font-size: 9px; vertical-align: top; }
        .lbl { background: #f3f4f6; font-weight: bold; white-space: nowrap; }
        .items { table-layout: fixed; }
        .items th { border: 1px solid #555; background: #f3f4f6; padding: 4px; font-size: 9px; word-wrap: break-word; }
        .items td { border: 1px solid #555; padding: 3px 4px; vertical-align: top; word-wrap: break-word; }
        .muted { color: #666; font-size: 8px; }
        .center { text-align: center; }
        .sig { margin-top: 26px; width: 100%; page-break-inside: avoid; }
        .sig td { width: 20%; text-align: center; vertical-align: top; padding: 0 4px; font-size: 9px; }
        .sigbox { border: 1px solid #555; height: 46px; margin: 3px 0; }
    </style>
</head>
<body>
    <table style="margin-bottom:6px;">
        <tr>
            <td style="width:54%; vertical-align:top; padding-right:14px;">@include('pdf._letterhead_block')</td>
            <td style="width:46%; vertical-align:top; padding:24px 0 0 14px;">
                <div style="text-align:center; margin-bottom:7px;">
                    <div style="font-size:15px; font-weight:bold;">ໃບ ຂໍ ຈຳໜ່າຍ ເຄື່ອງ</div>
                    <div style="font-size:9px; color:#555;">DISPOSAL RECORD</div>
                </div>
                <table class="info">
                    <tr><td class="lbl">ໃບ ເລກທີ່</td><td>{{ $record->request_number }}</td><td class="lbl">ວັນທີ</td><td>{{ $fmt($record->created_at) }}</td></tr>
                    <tr><td class="lbl">ຫົວຂໍ້</td><td colspan="3">{{ $record->title ?? '—' }}</td></tr>
                    <tr><td class="lbl">ພະແນກ</td><td>{{ $record->department?->name ?? '—' }}</td><td class="lbl">ຜູ້ ເຮັດລິສ</td><td>{{ $record->prepared_by_name ?? '—' }}</td></tr>
                    <tr><td class="lbl">ສະຖານະ</td><td colspan="3">{{ $record->statusLabel() }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <div style="border-bottom:2px solid #1e3a5f; margin-bottom:10px;"></div>

    <table class="items">
        <thead><tr>
            <th style="width:5%">#</th><th style="width:34%">ລາຍການ</th><th style="width:9%">ຈຳນວນ</th>
            <th style="width:29%">ເຫດຜົນ</th><th style="width:23%">ຄຳແນະນຳ</th>
        </tr></thead>
        <tbody>
            @foreach ($record->items as $it)
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td>
                        {{ $it->item_name }}
                        @if ($it->asset_code || $it->fixed_asset_no)<div class="muted">{{ $it->asset_code }}@if ($it->fixed_asset_no) · {{ $it->fixed_asset_no }}@endif</div>@endif
                        @if ($it->condition)<div class="muted">ສະພາບ: {{ $it->condition }}</div>@endif
                        @if (! empty($it->history))<div class="muted" style="color:#185fa5">ປະຫວັດ: @foreach ($it->history as $h){{ $h['date'] ?? '' }} {{ $h['problem'] ?? '' }}→{{ $h['action'] ?? '' }}@if (! $loop->last); @endif @endforeach</div>@endif
                    </td>
                    <td class="center">{{ $it->qty }} {{ $it->unit }}</td>
                    <td>{{ $it->reason ?: '—' }}@if ($it->reason_detail) · {{ $it->reason_detail }}@endif</td>
                    <td>{{ $it->recommendation ?: '—' }}@if ($it->recommendation_detail) · {{ $it->recommendation_detail }}@endif</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="sig">
        <tr>
            @foreach ($cells as $c)
                <td>{{ $c['label'] }}<div class="sigbox"></div><div class="muted">{{ $c['names'] }}@if ($c['date']) · {{ $c['date'] }}@endif</div></td>
            @endforeach
        </tr>
    </table>

    @include('pdf._footer')
</body>
</html>
