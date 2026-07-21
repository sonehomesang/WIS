@php
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
        .lbl { background: #f3f4f6; font-weight: bold; width: 20%; }
        .items th { border: 1px solid #555; background: #f3f4f6; padding: 4px; }
        .items td { border: 1px solid #555; padding: 4px; }
        .center { text-align: center; }
        .right { text-align: right; }
        .ph { width: 80px; height: 80px; object-fit: cover; border: 1px solid #999; margin: 2px; }
        .sig { margin-top: 24px; width: 100%; }
        .sig td { width: 33%; text-align: center; vertical-align: top; padding: 0 10px; }
        .sigbox { border: 1px solid #555; height: 56px; margin-top: 4px; }
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
                    <div style="font-size:15px; font-weight:bold;">ໃບສົ່ງເຄື່ອງອອກ</div>
                    <div style="font-size:9px; color:#555;">OUTWARDS GOODS ADVICE{{ $record->source_da_number ? ' · from '.$record->source_da_number : '' }}</div>
                </div>
                <table class="info">
                    <tr><td class="lbl">OGA No.</td><td>{{ $record->oga_number }}</td><td class="lbl">ວັນທີ</td><td>{{ $fmt($record->date) }}</td></tr>
                    <tr><td class="lbl">ປາຍທາງ</td><td>{{ $record->dispatch_to_name ?? '—' }}</td><td class="lbl">Ship via</td><td>{{ strtoupper($record->ship_via ?? '—') }}</td></tr>
                    <tr><td class="lbl">ສິນຄ້າ</td><td colspan="3">{{ $record->goods_consigned ?? '—' }}</td></tr>
                    <tr><td class="lbl">ຄົນຂັບ / ລົດ</td><td>{{ $record->driver_name ?? '—' }} · {{ $record->truck_plate_number ?? '—' }}</td><td class="lbl">ນ້ຳໜັກ</td><td>{{ $record->total_weight_kg ?? $record->gross_weight_kg ?? '—' }} kg</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <div style="border-bottom:2px solid #1e3a5f; margin-bottom:12px;"></div>

    <table class="items" style="margin-top:10px">
        <thead><tr><th>#</th><th>Description</th><th>Unit</th><th>Qty</th><th>Weight</th></tr></thead>
        <tbody>
            @foreach ($record->items as $it)
                <tr><td class="center">{{ $loop->iteration }}</td><td>{{ $it->description }}</td><td class="center">{{ $it->unit ?? '—' }}</td><td class="center">{{ $it->qty }}</td><td class="right">{{ $it->total_weight_kg ?? '—' }}</td></tr>
            @endforeach
        </tbody>
    </table>

    @if ($record->photos->count())
        @foreach ($record->photos as $p)@if ($src = $img($p->path))<img class="ph" src="{{ $src }}">@endif @endforeach
    @endif

    <table class="sig">
        <tr>
            <td>Consigned by<div class="sigbox"></div><div class="muted">{{ $record->consign_by_name }}</div></td>
            <td>Authorized by<div class="sigbox"></div><div class="muted">{{ $record->authorized_by_name }} · {{ $fmt($record->authorized_at) }}</div></td>
            <td>Delivered/Completed<div class="sigbox"></div><div class="muted">{{ $record->completed_by_name }} · {{ $fmt($record->completed_at) }}</div></td>
        </tr>
    </table>

    @include('pdf._footer')
</body>
</html>
