@php
    // base64-embed a public-disk photo, centre-cropped square (DomPDF ບໍ່ຮອງຮັບ
    // object-fit → ຮູບ portrait ຈະຖືກ ບີບ ບ້ຽວ; crop ໃຫ້ ເປັນ ຈັດຕຸລັດ ກ່ອນ).
    $img = fn ($path) => \App\Support\PdfExport::thumb($path);
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
        table { width: 100%; border-collapse: collapse; }
        .fields td { border: 1px solid #555; padding: 4px 6px; }
        .info td { border: 1px solid #555; padding: 2px 5px; font-size: 9px; vertical-align: top; }
        .info td.lbl { white-space: nowrap; width: 1%; }
        .lbl { background: #f3f4f6; font-weight: bold; width: 18%; }
        .items { table-layout: fixed; }
        .items th { border: 1px solid #555; background: #f3f4f6; padding: 4px; word-wrap: break-word; }
        .items td { border: 1px solid #555; padding: 4px; vertical-align: top; word-wrap: break-word; }
        .center { text-align: center; }
        .ph { width: 40px; height: 40px; border: 1px solid #999; margin: 1px; display: inline-block; vertical-align: top; }
        .sig { margin-top: 34px; width: 100%; }
        .sig td { width: 50%; text-align: center; vertical-align: top; padding: 0 14px; }
        .sigbox { border: 1px solid #555; height: 80px; margin-top: 4px; }
        .muted { color: #666; font-size: 9px; }
    </style>
</head>
<body>
    <table style="margin-bottom:6px;">
        <tr>
            <td style="width:54%; vertical-align:top; padding-right:14px;">
                @include('pdf._letterhead_block')
            </td>
            <td style="width:46%; vertical-align:top; padding:30px 0 0 14px;">
                <div style="text-align:center; margin-bottom:7px;">
                    <div style="font-size:15px; font-weight:bold;">ໃບຝາກເຄື່ອງ</div>
                    <div style="font-size:9px; color:#555;">DEPOSIT RECORD · {{ $record->request_type === 'pre_request' ? 'Pre-request' : 'Walk-in' }}</div>
                </div>
                <table class="info">
                    <tr><td class="lbl">ໃບຝາກເລກທີ່</td><td>{{ $record->request_number }}</td><td class="lbl">ວັນທີຝາກ</td><td>{{ $fmt($record->deposit_date) }}</td></tr>
                    <tr><td class="lbl">ເຈົ້າຂອງ</td><td>{{ $record->owner_name }}</td><td class="lbl">ໜ່ວຍງານ</td><td>{{ $record->unit?->name ?? '—' }} / {{ $record->department?->name ?? '—' }}</td></tr>
                    <tr><td class="lbl">ປະເພດ</td><td>{{ $record->item_category ?? '—' }}</td><td class="lbl">ແຫຼ່ງທີ່ມາ</td><td>{{ $record->origin_source ?? '—' }}</td></tr>
                    <tr><td class="lbl">ໄລຍະເວລາ</td><td>{{ $record->expected_duration ?? '—' }}</td><td class="lbl">ບ່ອນເກັບ</td><td>{{ $storage }}</td></tr>
                    <tr><td class="lbl">ເຫດຜົນ</td><td colspan="3">{{ $record->deposit_reason ?? '—' }}</td></tr>
                    @if ($record->warehouse_instructions)<tr><td class="lbl">ຄຳແນະນຳ</td><td colspan="3">{{ $record->warehouse_instructions }}</td></tr>@endif
                </table>
            </td>
        </tr>
    </table>

    <div style="border-bottom:2px solid #1e3a5f; margin-bottom:12px;"></div>

    <table class="items" style="margin-top:10px">
        <thead><tr>
            <th style="width:5%">#</th><th style="width:39%">ລາຍລະອຽດເຄື່ອງ</th>
            <th style="width:24%">ຮູບ</th><th style="width:9%">ຈຳນວນ</th><th style="width:9%">ໜ່ວຍ</th><th style="width:14%">ມູນຄ່າ</th>
        </tr></thead>
        <tbody>
            @foreach ($record->items as $it)
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td>{{ $it->item_name }}@if ($it->asset_code || $it->fixed_asset_no)<div class="muted">@if ($it->asset_code)ທະບຽນເຄື່ອງ: {{ $it->asset_code }}@endif@if ($it->fixed_asset_no) · ຊັບສິນ: {{ $it->fixed_asset_no }}@endif</div>@endif@if ($it->description)<div class="muted">{{ $it->description }}</div>@endif</td>
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

    @include('pdf._footer')
</body>
</html>
