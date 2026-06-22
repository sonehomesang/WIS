@php
    // base64-embed a public-disk photo (DomPDF ບໍ່ໂຫລດ URL — ໃຊ້ data URI).
    $img = function ($path) {
        try {
            $abs = \Illuminate\Support\Facades\Storage::disk('public')->path($path);
            if (is_file($abs)) {
                return 'data:image/jpeg;base64,'.base64_encode(file_get_contents($abs));
            }
        } catch (\Throwable $e) {
        }

        return null;
    };
    $fmt = fn ($d) => $d?->format('d/m/Y') ?? '—';
    $storage = collect([$record->storage_location, $record->storage_shelf_label])->filter()->implode(' / ') ?: '—';
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: 'Phetsarath OT', DejaVu Sans, sans-serif; }
        body { font-size: 11px; color: #111; margin: 0; }
        h2 { text-align: center; font-size: 15px; margin: 0 0 4px; }
        .sub { text-align: center; color: #666; font-size: 9px; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        .fields td { border: 1px solid #555; padding: 4px 6px; }
        .lbl { background: #f3f4f6; font-weight: bold; width: 18%; }
        .items th { border: 1px solid #555; background: #f3f4f6; padding: 4px; }
        .items td { border: 1px solid #555; padding: 4px; vertical-align: top; }
        .center { text-align: center; }
        .ph { width: 40px; height: 40px; object-fit: cover; border: 1px solid #999; margin: 1px; }
        .sig { margin-top: 34px; width: 100%; }
        .sig td { width: 50%; text-align: center; vertical-align: top; padding: 0 14px; }
        .sigbox { border: 1px solid #555; height: 80px; margin-top: 4px; }
        .muted { color: #666; font-size: 9px; }
    </style>
</head>
<body>
    <h2>ໃບຝາກເຄື່ອງ / DEPOSIT RECORD</h2>
    <div class="sub">NAM THEUN 2 — WAREHOUSE INFORMATION SYSTEM · {{ $record->request_type === 'pre_request' ? 'Pre-request' : 'Walk-in' }}</div>

    <table class="fields">
        <tr>
            <td class="lbl">ໃບຝາກເລກທີ່</td><td style="width:32%">{{ $record->request_number }}</td>
            <td class="lbl">ວັນທີຝາກ</td><td>{{ $fmt($record->deposit_date) }}</td>
        </tr>
        <tr><td class="lbl">ເຈົ້າຂອງ</td><td colspan="3">{{ $record->owner_name }} — {{ $record->unit?->name ?? '—' }} / {{ $record->department?->name ?? '—' }}</td></tr>
        <tr>
            <td class="lbl">ປະເພດ</td><td>{{ $record->item_category ?? '—' }}</td>
            <td class="lbl">ແຫຼ່ງທີ່ມາ</td><td>{{ $record->origin_source ?? '—' }}</td>
        </tr>
        <tr>
            <td class="lbl">ໄລຍະເວລາ</td><td>{{ $record->expected_duration ?? '—' }}</td>
            <td class="lbl">ບ່ອນເກັບ</td><td>{{ $storage }}</td>
        </tr>
        <tr><td class="lbl">ເຫດผົน</td><td colspan="3">{{ $record->deposit_reason ?? '—' }}</td></tr>
        @if ($record->warehouse_instructions)<tr><td class="lbl">ຄຳແນະນຳ</td><td colspan="3">{{ $record->warehouse_instructions }}</td></tr>@endif
    </table>

    <table class="items" style="margin-top:10px">
        <thead><tr>
            <th style="width:5%">#</th><th>ລາຍລະອຽດເຄື່ອງ</th>
            <th style="width:24%">ຮູບ</th><th style="width:9%">ຈຳນວນ</th><th style="width:9%">ໜ່ວຍ</th><th style="width:14%">ມູນຄ່າ</th>
        </tr></thead>
        <tbody>
            @foreach ($record->items as $it)
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td>{{ $it->item_name }}@if ($it->description)<div class="muted">{{ $it->description }}</div>@endif</td>
                    <td>
                        @foreach ($it->photos as $p)@if ($src = $img($p->path))<img class="ph" src="{{ $src }}">@endif @endforeach
                    </td>
                    <td class="center">{{ $it->qty }}</td>
                    <td class="center">{{ $it->unit ?? '—' }}</td>
                    <td class="center">{{ $it->estimated_value ? number_format($it->estimated_value, 2).' '.$it->currency : '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="sig">
        <tr>
            <td>ລາຍເຊັນ ເຈົ້າຂອງ<div class="sigbox"></div><div class="muted">{{ $record->owner_name }}</div></td>
            <td>ລາຍເຊັນ ທີມສາງ<div class="sigbox"></div><div class="muted">{{ $record->warehouse_staff_name ?? '' }}</div></td>
        </tr>
    </table>
</body>
</html>
