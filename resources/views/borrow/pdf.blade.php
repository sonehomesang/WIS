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
        h2 { text-align: center; font-size: 15px; margin: 0 0 12px; }
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
    @include('pdf._letterhead', ['docTitle' => 'ບັນທຶກລາຍລະອຽດ ການຢືມເຄື່ອງ / BORROW RECORD'])

    <table class="fields">
        <tr>
            <td class="lbl">ໃບຢືມເລກທີ່</td><td style="width:32%">{{ $record->request_number }}</td>
            <td class="lbl">ຢືມ/ເບີກວັນທີ</td><td>{{ $fmt($record->borrow_date) }}</td>
        </tr>
        <tr><td class="lbl">ຜູ້ຢືມ</td><td colspan="3">{{ $record->borrower_name }} — {{ $record->unit?->name ?? '—' }} / {{ $record->department?->name ?? '—' }}</td></tr>
        <tr><td class="lbl">ຈຸດປະສົງ</td><td colspan="3">{{ $record->purpose ?? '—' }}</td></tr>
        <tr>
            <td class="lbl">ກຳນົດວັນທີສົ່ງ</td><td>{{ $fmt($record->planned_return_date) }}</td>
            <td class="lbl">ຂໍຕໍ່ອາຍຸ</td><td>{{ $ext }}</td>
        </tr>
    </table>

    <table class="items" style="margin-top:10px">
        <thead><tr>
            <th style="width:5%">#</th><th style="width:16%">ລະຫັດເຄື່ອງ</th><th>ລາຍລະອຽດການຢືມ</th>
            <th style="width:20%">ຮູບ</th><th style="width:9%">ຈຳນວນ</th><th style="width:9%">ໜ່ວຍ</th>
        </tr></thead>
        <tbody>
            @foreach ($record->items as $it)
                @php $photo = $it->inventoryItem?->primaryPhoto?->first(); @endphp
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
