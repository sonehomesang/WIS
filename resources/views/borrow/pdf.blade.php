@php
    // base64-embed a public-disk photo, centre-cropped square (DomPDF ບໍ່ຮອງຮັບ
    // object-fit → ຮູບ portrait ຈະຖືກ ບີບ ບ້ຽວ; crop ໃຫ້ ເປັນ ຈັດຕຸລັດ ກ່ອນ).
    $img = fn ($path) => \App\Support\PdfExport::thumb($path);
    $fmt = fn ($d) => $d?->format('d/m/Y') ?? '—';
    $ext = match ($record->extension_status) {
        'pending' => '⏳ '.$fmt($record->extension_proposed_date),
        'approved' => '✓ '.$fmt($record->extension_proposed_date),
        'rejected' => '✗ ປະຕິເສດ', default => '—',
    };
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
                    <div style="font-size:15px; font-weight:bold;">ບັນທຶກການຢືມເຄື່ອງ</div>
                    <div style="font-size:9px; color:#555;">BORROW RECORD</div>
                </div>
                <table class="info">
                    <tr><td class="lbl">ໃບຢືມເລກທີ່</td><td>{{ $record->request_number }}</td><td class="lbl">ວັນທີຢືມ</td><td>{{ $fmt($record->borrow_date) }}</td></tr>
                    <tr><td class="lbl">ຜູ້ຢືມ</td><td>{{ $record->borrower_name }}</td><td class="lbl">ໜ່ວຍງານ</td><td>{{ $record->unit?->name ?? '—' }} / {{ $record->department?->name ?? '—' }}</td></tr>
                    <tr><td class="lbl">ຈຸດປະສົງ</td><td colspan="3">{{ $record->purpose ?? '—' }}</td></tr>
                    <tr><td class="lbl">ກຳນົດສົ່ງ</td><td>{{ $fmt($record->planned_return_date) }}</td><td class="lbl">ຕໍ່ອາຍຸ</td><td>{{ $ext }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <div style="border-bottom:2px solid #1e3a5f; margin-bottom:12px;"></div>

    <table class="items" style="margin-top:10px">
        <thead><tr>
            <th style="width:5%">#</th><th style="width:16%">ລະຫັດເຄື່ອງ</th><th style="width:39%">ລາຍລະອຽດການຢືມ</th>
            <th style="width:22%">ຮູບ</th><th style="width:9%">ຈຳນວນ</th><th style="width:9%">ໜ່ວຍ</th>
        </tr></thead>
        <tbody>
            @foreach ($record->items as $it)
                @php $photo = $it->inventoryItem?->primaryPhoto; @endphp
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td>{{ $it->inventoryItem?->slug ?? '—' }}</td>
                    <td>{{ $it->item_name }}</td>
                    <td>
                        @if ($photo && $src = $img($photo->path))<img class="ph" src="{{ $src }}">@endif
                        @foreach ($it->photos->where('kind', 'take') as $p)@if ($src = $img($p->path))<img class="ph" src="{{ $src }}">@endif @endforeach
                    </td>
                    <td class="center">{{ $it->qty }}</td>
                    <td class="center">{{ $it->inventoryItem?->unit ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="sig">
        <tr>
            <td>ລາຍເຊັນ ຜູ້ຢືມ<div class="sigbox"></div><div class="muted">{{ $record->borrower_name }}</div></td>
            <td>ລາຍເຊັນ ທີມສາງ / ຜູ້ເບີກ<div class="sigbox"></div><div class="muted">{{ $record->warehouse_staff_name ?? '' }}</div></td>
        </tr>
    </table>

    @include('pdf._footer')
</body>
</html>
