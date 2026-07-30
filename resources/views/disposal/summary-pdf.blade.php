@php
    $fmt = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('d/m/Y') : '—';
    $statusLo = ['disposed' => 'ຈຳໜ່າຍ ແລ້ວ', 'approved' => 'ອະນຸມັດ ແລ້ວ', 'all' => 'ອະນຸມັດ + ຈຳໜ່າຍ'][$status] ?? $status;
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: 'Phetsarath OT', DejaVu Sans, sans-serif; }
        body { font-size: 10px; color: #111; margin: 0; }
        table { width: 100%; border-collapse: collapse; }
        .items { table-layout: fixed; }
        .items th { border: 1px solid #555; background: #f3f4f6; padding: 3px; font-size: 9px; word-wrap: break-word; }
        .items td { border: 1px solid #555; padding: 3px 4px; vertical-align: top; font-size: 9px; word-wrap: break-word; }
        .center { text-align: center; }
        .right { text-align: right; }
        .muted { color: #666; font-size: 8px; }
    </style>
</head>
<body>
    <table style="margin-bottom:6px;">
        <tr>
            <td style="width:54%; vertical-align:top; padding-right:14px;">@include('pdf._letterhead_block')</td>
            <td style="width:46%; vertical-align:top; padding:26px 0 0 14px; text-align:center;">
                <div style="font-size:15px; font-weight:bold;">ລິສ ລວມ ຈຳໜ່າຍ ເຄື່ອງ</div>
                <div style="font-size:9px; color:#555;">DISPOSAL SUMMARY</div>
                <div class="muted" style="margin-top:4px;">ໄລຍະ {{ $fmt($from) }} – {{ $fmt($to) }} · {{ $statusLo }}@if ($deptName) · {{ $deptName }}@endif</div>
            </td>
        </tr>
    </table>
    <div style="border-bottom:2px solid #1e3a5f; margin-bottom:10px;"></div>

    <table class="items">
        <thead><tr>
            <th style="width:6%">#</th><th style="width:14%">DS</th><th style="width:30%">ລາຍການ</th>
            <th style="width:14%">ພະແນກ</th><th style="width:9%">ຈຳນວນ</th><th style="width:15%">ເຫດຜົນ</th><th style="width:12%">ມູນຄ່າ</th>
        </tr></thead>
        <tbody>
            @foreach ($items as $it)
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td>{{ $it->record?->request_number }}</td>
                    <td>{{ $it->item_name }}@if ($it->asset_code)<div class="muted">{{ $it->asset_code }}</div>@endif</td>
                    <td>{{ $it->record?->department?->name ?? '—' }}</td>
                    <td class="center">{{ $it->qty }} {{ $it->unit }}</td>
                    <td>{{ $it->reason ?: '—' }}</td>
                    <td class="right">{{ $it->estimated_value ? number_format($it->estimated_value) : '—' }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background:#f3f4f6; font-weight:bold;">
                <td colspan="4" class="right">ລວມ {{ $items->count() }} ລາຍການ · {{ number_format($totalQty) }} ໜ່ວຍ</td>
                <td colspan="2" class="right">ມູນຄ່າ ລວມ</td>
                <td class="right">{{ number_format($totalValue) }} ກີບ</td>
            </tr>
        </tfoot>
    </table>

    @include('pdf._footer')
</body>
</html>
