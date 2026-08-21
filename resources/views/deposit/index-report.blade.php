<!doctype html>
<html lang="lo">
<head>
<meta charset="utf-8">
<title>ບັນຊີ ລາຍການ ເຄື່ອງ ຝາກ · Deposit Items Index</title>
<style>
    @page { size: A4 landscape; margin: 12mm 10mm; }
    * { box-sizing: border-box; }
    body { font-family: 'Noto Sans Lao', 'Phetsarath OT', 'Leelawadee UI', sans-serif; color: #1f2937; font-size: 12px; margin: 0; background: #f3f4f6; }
    .sheet { background: #fff; max-width: 1120px; margin: 16px auto; padding: 22px 26px; box-shadow: 0 4px 20px rgba(0,0,0,.12); }
    .lh-head { width: 100%; border-collapse: collapse; border-bottom: 2px solid #1e3a5f; padding-bottom: 6px; }
    .lh-head td { vertical-align: top; }
    .doc-meta { text-align: right; font-size: 9px; color: #6b7280; line-height: 1.6; }
    .title { text-align: center; margin: 12px 0 2px; }
    .title .t { font-size: 16px; font-weight: 800; letter-spacing: .02em; }
    .title .te { font-size: 11px; color: #6b7280; font-weight: 600; }
    .ctx { display: flex; gap: 18px; flex-wrap: wrap; font-size: 10.5px; color: #374151; background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 5px; padding: 5px 10px; margin: 8px 0 10px; }
    .ctx b { color: #1e3a5f; }
    table.data { width: 100%; border-collapse: collapse; font-size: 11px; }
    table.data th { background: #eef2f7; color: #1e3a5f; border: 1px solid #cbd5e1; padding: 5px 6px; text-align: left; font-size: 10.5px; }
    table.data td { border: 1px solid #cbd5e1; padding: 4px 6px; vertical-align: middle; }
    .num { text-align: center; color: #6b7280; }
    .qty { text-align: center; white-space: nowrap; }
    .thumb { width: 46px; height: 46px; object-fit: cover; border: 1px solid #d1d5db; border-radius: 4px; display: block; }
    .noimg { width: 46px; height: 46px; border: 1px dashed #d1d5db; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #cbd5e1; font-size: 16px; }
    .dp { font-family: monospace; color: #4b5563; font-size: 10px; }
    .pill { display: inline-block; font-size: 10px; font-weight: 600; border-radius: 999px; padding: 1px 8px; white-space: nowrap; }
    .c-emerald { background:#ecfdf5; color:#047857; } .c-amber{background:#fffbeb;color:#b45309;} .c-orange{background:#fff7ed;color:#c2410c;}
    .c-yellow{background:#fefce8;color:#a16207;} .c-red{background:#fef2f2;color:#b91c1c;} .c-rose{background:#fff1f2;color:#be123c;}
    .c-purple{background:#faf5ff;color:#7e22ce;} .c-slate{background:#f1f5f9;color:#475569;} .c-gray{background:#f3f4f6;color:#4b5563;}
    .rec { color: #374151; }
    .sign { margin-top: 22px; display: flex; justify-content: flex-end; gap: 64px; }
    .sign .box { text-align: center; font-size: 10px; color: #374151; }
    .sign .line { border-top: 1px solid #9ca3af; width: 160px; margin-top: 40px; padding-top: 3px; }
    .foot { display: flex; justify-content: space-between; margin-top: 18px; font-size: 9.5px; color: #6b7280; border-top: 1px solid #e5e7eb; padding-top: 5px; }
    .toolbar { max-width: 1120px; margin: 12px auto 0; text-align: right; }
    .btn { font-family: inherit; font-size: 13px; background: #0ea5e9; color: #fff; border: 0; border-radius: 8px; padding: 8px 16px; cursor: pointer; }
    .empty { text-align:center; color:#9ca3af; padding:30px; }
    @media print { body { background: #fff; } .sheet { box-shadow: none; margin: 0; max-width: none; } .toolbar { display: none; } }
</style>
</head>
<body>
@php
    $colorClass = fn ($s) => 'c-'.(optional(\App\Support\ConditionStatus::badge($s)) ? \Illuminate\Support\Str::of(\App\Support\ConditionStatus::badge($s))->after('bg-')->before('-')->value() : 'gray');
    $rows = $records->flatMap(fn ($r) => $r->items->map(fn ($it) => ['r' => $r, 'it' => $it]));
    $totQty = $records->sum(fn ($r) => $r->items->sum('qty'));
    $statusLabels = ['submitted'=>'ລໍຮັບ','accepted'=>'ຮັບແລ້ວ','stored'=>'ເກັບໄວ້','claimed'=>'ເອົາຄືນແລ້ວ','needs_fix'=>'ຕ້ອງແກ້','disposal'=>'ກຳລັງຈຳໜ່າຍ','disposed'=>'ຈຳໜ່າຍແລ້ວ','draft'=>'draft','cancelled'=>'ຍົກເລີກ','needs_info'=>'ຮ່າງ ລໍ ຕື່ມ ຂໍ້ມູນ'];
@endphp

<div class="toolbar"><button class="btn" onclick="window.print()">🖨 ພິມ / Save PDF</button></div>

<div class="sheet">
    <table class="lh-head"><tr>
        <td style="width:62%; padding-right:14px;">@include('pdf._letterhead_block')</td>
        <td class="doc-meta">
            ວັນທີ ພິມ / Date : {{ now()->format('d/m/Y H:i') }}<br>
            ພິມ ໂດຍ / By : {{ $generatedBy }}<br>
            ເອກະສານ : DEP-INDEX
        </td>
    </tr></table>

    <div class="title">
        <div class="t">ບັນຊີ ລາຍການ ເຄື່ອງ ຝາກ</div>
        <div class="te">Deposit Items Index</div>
    </div>

    <div class="ctx">
        <span><b>ໜ່ວຍງານ / Unit:</b> {{ $filterUnit?->name ?? 'ທັງໝົດ (All)' }}</span>
        <span><b>ສະຖານະ / Status:</b> {{ $filterStatus ? ($statusLabels[$filterStatus] ?? $filterStatus) : 'ທັງໝົດ (All)' }}</span>
        <span><b>ລວມ / Total:</b> {{ $records->count() }} ໃບ · {{ $rows->count() }} ລາຍການ · {{ number_format($totQty) }} ໜ່ວຍ</span>
    </div>

    <table class="data">
        <thead><tr>
            <th style="width:26px" class="num">#</th>
            <th style="width:58px">ຮູບ</th>
            <th>ລາຍລະອຽດ (Detail)</th>
            <th style="width:64px" class="qty">ຈຳນວນ</th>
            <th style="width:96px">ສະຖານະພາບ</th>
            <th style="width:230px">ຄຳ ແນະນຳ (Recommendation)</th>
        </tr></thead>
        <tbody>
            @forelse ($rows as $i => $row)
                @php $r = $row['r']; $it = $row['it']; $cs = $it->condition_status ?: 'in_service'; $ph = $it->photos->first(); @endphp
                <tr>
                    <td class="num">{{ $i + 1 }}</td>
                    <td>@if ($ph)<img class="thumb" src="{{ $ph->url }}" alt="">@else<div class="noimg">📦</div>@endif</td>
                    <td>
                        <b>{{ $it->item_name }}</b>@if ($it->unit) <span style="color:#9ca3af">({{ $it->unit }})</span>@endif
                        <div class="dp">{{ $r->request_number }} · {{ $r->unit?->name ?? '—' }}@if ($it->asset_code) · {{ $it->asset_code }}@endif</div>
                    </td>
                    <td class="qty"><b>{{ number_format($it->qty) }}</b>@if ($it->unit) {{ $it->unit }}@endif</td>
                    <td><span class="pill {{ $colorClass($cs) }}">{{ \App\Support\ConditionStatus::shortLabel($cs) }}</span></td>
                    <td class="rec">{{ $it->recommendation ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty">ບໍ່ ມີ ລາຍການ ຕາມ ຟິວເຕີ ນີ້</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="sign">
        <div class="box"><div class="line">ຜູ້ ສະຫຼຸບ / Prepared by</div></div>
        <div class="box"><div class="line">ຫົວໜ້າ ສາງ / Warehouse Head</div></div>
    </div>

    <div class="foot">
        <span>Nam Theun 2 Power Company Ltd. · Deposit Items Index</span>
        <span>ພິມ: {{ now()->format('d/m/Y H:i') }}</span>
    </div>
</div>
</body>
</html>
