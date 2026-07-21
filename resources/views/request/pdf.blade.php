@php
    $fmt = fn ($d) => $d?->format('d/m/Y') ?? '—';
    $vatLabel = $record->vat_enabled ? rtrim(rtrim(number_format($record->vat_rate, 2), '0'), '.').'%' : '(ປິດ)';
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
        .lbl { background: #f3f4f6; font-weight: bold; white-space: nowrap; }
        .items th { border: 1px solid #555; background: #f3f4f6; padding: 4px; }
        .items td { border: 1px solid #555; padding: 4px; vertical-align: top; }
        .center { text-align: center; }
        .right { text-align: right; }
        .sig { margin-top: 30px; width: 100%; }
        .sig td { width: 33%; text-align: center; vertical-align: top; padding: 0 10px; }
        .sigbox { border: 1px solid #555; height: 64px; margin-top: 4px; }
        .muted { color: #666; font-size: 9px; }
    </style>
</head>
<body>
    <table style="margin-bottom:12px;">
        <tr>
            <td style="width:54%; vertical-align:top; padding-right:14px;">
                @include('pdf._letterhead_block')
            </td>
            <td style="width:46%; vertical-align:top; padding-left:14px; border-left:1px solid #cbd5e1;">
                <div style="text-align:center; margin-bottom:7px;">
                    <div style="font-size:15px; font-weight:bold;">ໃບຂໍເບີກເຄື່ອງຈາກຮ້ານຄ້າ</div>
                    <div style="font-size:9px; color:#555;">MATERIAL REQUEST</div>
                </div>
                <table class="info">
                    <tr><td class="lbl">ເລກທີ່</td><td>{{ $record->request_number }}</td><td class="lbl">ວັນທີ</td><td>{{ $fmt($record->created_at) }}</td></tr>
                    <tr><td class="lbl">ຜູ້ເບີກ</td><td>{{ $record->requester_name }}</td><td class="lbl">ໜ່ວຍງານ</td><td>{{ $record->unit?->name ?? '—' }} / {{ $record->department?->name ?? '—' }}</td></tr>
                    <tr><td class="lbl">ຈຸດປະສົງ</td><td colspan="3">{{ $record->purpose ?? '—' }}</td></tr>
                    <tr><td class="lbl">ກ່ຽວຂ້ອງກັບ</td><td>{{ $record->request_type ?? '—' }} {{ $record->wo_e_form }}</td><td class="lbl">ຮ້ານຄ້າ</td><td>{{ $record->supplier?->name ?? '—' }}</td></tr>
                    <tr><td class="lbl">ສະຖານະ</td><td colspan="3">{{ strtoupper($record->status) }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead><tr><th style="width:5%">#</th><th>ລາຍລະອຽດ</th><th style="width:10%">ໜ່ວຍ</th><th style="width:10%">ຈຳນວນ</th><th style="width:15%">ລາຄາ</th><th style="width:15%">ລວມ</th></tr></thead>
        <tbody>
            @foreach ($record->items as $it)
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td>{{ $it->description }}@if ($it->material_nbr)<div class="muted">{{ $it->material_nbr }}</div>@endif</td>
                    <td class="center">{{ $it->unit ?? '—' }}</td>
                    <td class="center">{{ $it->quantity }}</td>
                    <td class="right">{{ number_format($it->unit_price, 2) }}</td>
                    <td class="right">{{ number_format($it->line_total, 2) }}</td>
                </tr>
            @endforeach
            <tr><td class="right" colspan="5">ລວມ (net)</td><td class="right">{{ number_format($record->total, 2) }}</td></tr>
            <tr><td class="right" colspan="5">VAT {{ $vatLabel }}</td><td class="right">{{ number_format($record->vat_amount, 2) }}</td></tr>
            <tr><td class="right" colspan="5"><b>ລວມທັງໝົດ</b></td><td class="right"><b>{{ number_format($record->grand_total, 2) }} {{ $record->currency }}</b></td></tr>
        </tbody>
    </table>

    <table class="sig">
        <tr>
            <td>ຜູ້ເບີກ<div class="sigbox"></div><div class="muted">{{ $record->requester_name }}</div></td>
            <td>ຜູ້ອະນຸມັດ<div class="sigbox"></div><div class="muted">{{ $record->approver_name ?? '' }} · {{ $fmt($record->approved_at) }}</div></td>
            <td>ທີມສາງ<div class="sigbox"></div><div class="muted">{{ $record->warehouse_staff_name ?? '' }} · {{ $fmt($record->validated_at) }}</div></td>
        </tr>
    </table>

    @include('pdf._footer')
</body>
</html>
