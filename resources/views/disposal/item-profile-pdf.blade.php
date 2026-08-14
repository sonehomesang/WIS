@php
    use App\Support\ConditionStatus;
    use App\Support\PdfExport;

    $img = fn ($path) => PdfExport::thumb($path, 220);
    $fmt = fn ($d) => $d?->format('d/m/Y') ?? '—';

    // photos (JSON array of paths) — show up to 3 as evidence
    $photos = collect($item->photos ?? [])->take(3)->values();
    $photoLabels = ['① ຮູບ ຊູມ ລວມ / Overall', '② ຮູບ ໄອດີ / ID', '③ ຈຸດ ເສື່ອມ / Damage'];

    // source lifecycle info
    $cond = $source->condition_status ?? null;
    $purchase = ($item->source_type === 'equipment') ? ($source->purchase_date ?? null) : null;

    // endorsement chain = record signoffs
    $signs = $record->signoffs->groupBy('role_key');
    $endorse = function ($key, $lo, $en) use ($signs, $fmt) {
        $rows = $signs[$key] ?? collect();
        $first = $rows->first();

        return [
            'role_lo' => $lo, 'role_en' => $en,
            'name' => $rows->map(fn ($s) => $s->name)->filter()->implode(' · ') ?: '—',
            'date' => $first?->signed_at ? $fmt($first->signed_at).' '.$first->signed_at->format('H:i') : '—',
            'signed' => $rows->count() > 0,
        ];
    };
    $endorsers = [
        $endorse('preparer', 'ຜູ້ ເຮັດລິສ', 'Prepared by'),
        $endorse('committee', 'ຄະນະ ຮ່ວມກວດ', 'Committee'),
        $endorse('technical', 'ວິຊາການ / ວສ.', 'Technical / Eng.'),
        $endorse('manager', 'ຜູ້ ຈັດການ', 'Manager'),
    ];
    $exec = $endorse('executive', 'ຜູ້ ບໍລິຫານ', 'Executive (C-Level)');
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
        .sec { font-weight: bold; color: #1e3a5f; font-size: 11px; margin: 10px 0 4px; }
        .kv td { padding: 3px 5px; font-size: 9.5px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        .kv td.k { color: #6b7280; font-weight: bold; width: 42%; }
        .ph td { border: 1px solid #999; padding: 3px; text-align: center; vertical-align: top; width: 33%; }
        .ph .cap { font-size: 8px; color: #374151; font-weight: bold; margin-bottom: 2px; }
        .ph img { width: 100%; max-height: 120px; object-fit: cover; }
        .ph .none { color: #9ca3af; font-size: 8px; padding: 30px 0; }
        .en { table-layout: fixed; margin-top: 2px; }
        .en th { border: 1px solid #555; background: #f3f4f6; padding: 3px 5px; font-size: 8.5px; text-align: left; }
        .en td { border: 1px solid #555; padding: 4px 5px; font-size: 9px; vertical-align: top; }
        .en .role { font-weight: bold; }
        .en .stamp { border: 1px solid #1e7a4d; color: #1e7a4d; border-radius: 4px; padding: 1px 4px; display: inline-block; font-size: 8px; }
        .chip { border: 1px solid #999; border-radius: 10px; padding: 1px 7px; font-size: 9px; display: inline-block; }
        .chip.on { background: #fde8e8; color: #b91c1c; border-color: #f3c0c0; font-weight: bold; }
        .clevel { border: 1px dashed #b45309; background: #fdf3e3; padding: 5px 8px; font-size: 9px; margin-top: 6px; }
        .muted { color: #6b7280; font-size: 8px; }
    </style>
</head>
<body>
    {{-- letterhead (same 2-col header as DA/OGA) --}}
    <table style="margin-bottom:6px;">
        <tr>
            <td style="width:54%; vertical-align:top; padding-right:14px;">@include('pdf._letterhead_block')</td>
            <td style="width:46%; vertical-align:top; padding:24px 0 0 14px;">
                <div style="text-align:center; margin-bottom:7px;">
                    <div style="font-size:15px; font-weight:bold;">ໃບ ຂໍ້ມູນ ເຄື່ອງ ຈຳໜ່າຍ</div>
                    <div style="font-size:9px; color:#555;">ITEM DISPOSAL PROFILE</div>
                </div>
                <table class="info">
                    <tr><td class="lbl">ລະຫັດ / Code</td><td>{{ $item->asset_code ?: '—' }}</td><td class="lbl">ວັນທີ / Date</td><td>{{ $fmt($record->created_at) }}</td></tr>
                    <tr><td class="lbl">ແຫຼ່ງ / Source</td><td>{{ ucfirst($item->source_type) }}</td><td class="lbl">ພະແນກ / Dept</td><td>{{ $record->department?->name ?? '—' }}</td></tr>
                    <tr><td class="lbl">ໃບ / Ref</td><td>{{ $record->request_number }}</td><td class="lbl">ສະຖານະ / Status</td><td>{{ $record->statusLabel() }}</td></tr>
                </table>
            </td>
        </tr>
    </table>
    <div style="border-bottom:2px solid #1e3a5f; margin-bottom:10px;"></div>

    {{-- A · photos --}}
    <div class="sec">A · ຮູບ ຫຼັກຖານ / Evidence photos</div>
    <table class="ph">
        <tr>
            @for ($i = 0; $i < 3; $i++)
                <td>
                    <div class="cap">{{ $photoLabels[$i] }}</div>
                    @if (isset($photos[$i]) && ($src = $img($photos[$i])))
                        <img src="{{ $src }}" alt="">
                        <div class="muted">{{ $fmt($record->created_at) }}</div>
                    @else
                        <div class="none">— ບໍ່ ມີ ຮູບ —</div>
                    @endif
                </td>
            @endfor
        </tr>
    </table>

    {{-- B · item details --}}
    <div class="sec">B · ຂໍ້ມູນ ເຄື່ອງ / Item details</div>
    <table class="kv">
        <tr><td class="k">ຊື່ ເຄື່ອງ / Item</td><td>{{ $item->item_name }}</td></tr>
        @if ($item->fixed_asset_no)<tr><td class="k">ທະບຽນ ຊັບສິນ / Fixed asset</td><td>{{ $item->fixed_asset_no }}</td></tr>@endif
        @if ($cond)<tr><td class="k">ສະຖານະພາບ / Condition</td><td>{{ ConditionStatus::label($cond) }}</td></tr>@endif
        @if ($purchase)<tr><td class="k">ວັນ ຮັບ ເຂົ້າ / Received</td><td>{{ $fmt($purchase) }}</td></tr>@endif
        @if ($item->condition)<tr><td class="k">ສະພາບ / State</td><td>{{ $item->condition }}</td></tr>@endif
        <tr><td class="k">ຈຳນວນ / Qty</td><td>{{ $item->qty }} {{ $item->unit }}</td></tr>
        <tr><td class="k">ເຫດຜົນ / Reason</td><td>{{ $item->reason ?: '—' }}@if ($item->reason_detail) · {{ $item->reason_detail }}@endif</td></tr>
        @if ($item->estimated_value)<tr><td class="k">ມູນຄ່າ ຄົງ ເຫຼືອ / Residual value</td><td>{{ number_format((float) $item->estimated_value, 2) }} {{ $item->currency }}</td></tr>@endif
    </table>

    {{-- C · history / timeline --}}
    @if (! empty($item->history))
        <div class="sec">C · ປະຫວັດ / Timeline</div>
        <table class="kv">
            @foreach ($item->history as $h)
                <tr><td class="k">{{ $h['date'] ?? '' }}</td><td>{{ $h['problem'] ?? '' }}@if (! empty($h['action'])) → {{ $h['action'] }}@endif</td></tr>
            @endforeach
        </table>
    @endif

    {{-- D · recommendation & endorsement --}}
    <div class="sec">D · ຄຳ ແນະນຳ &amp; ຮັບຮອງ / Recommendation &amp; Endorsement</div>
    <div style="font-size:9px; margin-bottom:3px;">
        ຄຳ ແນະນຳ ຕໍ່ C-Level / Recommended:
        <span class="chip on">{{ $item->recommendation ?: '—' }}</span>
        @if ($item->recommendation_detail)<span class="muted">· {{ $item->recommendation_detail }}</span>@endif
        <span class="muted">&nbsp;(ທຳລາຍ · ຂາຍ ເສດ · ບໍລິຈາກ · ສົ່ງ ຄືນ)</span>
    </div>
    <table class="en">
        <tr><th style="width:26%">ຂັ້ນ / Role</th><th style="width:44%">ຜູ້ ຮັບຮອງ / Endorser</th><th style="width:30%">ລາຍເຊັນ + ວັນເວລາ / Signed</th></tr>
        @foreach ($endorsers as $e)
            <tr>
                <td class="role">{{ $e['role_lo'] }}<br><span class="muted">{{ $e['role_en'] }}</span></td>
                <td>{{ $e['name'] }}</td>
                <td>@if ($e['signed'])<span class="stamp">{{ $e['name'] }} · {{ $e['date'] }}</span>@else <span class="muted">— ລໍ ເຊັນ —</span>@endif</td>
            </tr>
        @endforeach
    </table>
    <div class="clevel">
        🏛 ການ ຕັດສິນ ໃຈ ຂັ້ນ ບໍລິຫານ / C-Level decision ({{ $exec['role_en'] }}):
        @if ($exec['signed'])<b>{{ $exec['name'] }}</b> · {{ $exec['date'] }} — {{ $record->statusLabel() }}@else <b>ລໍ ຕັດສິນ ໃຈ (Pending)</b>@endif
    </div>

    @include('pdf._footer')
</body>
</html>
