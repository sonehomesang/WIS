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
    $fmt = fn ($d) => $d?->format('M d, Y') ?? '—';
    $typeLabel = match ($record->borrow_type) {
        'new_inventory' => 'New Inventory', 'tools_equipment' => 'Tools/Equipment',
        'deposited_tools' => 'Deposited', 'others' => 'Others', default => $record->borrow_type,
    };
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 11px; color: #1f2937; margin: 0; }
        h1 { font-size: 18px; margin: 0 0 2px; }
        .muted { color: #6b7280; }
        .sec { margin-top: 14px; }
        .sec-title { font-size: 11px; font-weight: bold; color: #4f46e5; text-transform: uppercase; border-bottom: 1px solid #e5e7eb; padding-bottom: 3px; margin-bottom: 6px; }
        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; padding: 2px 4px; }
        .label { color: #9ca3af; font-size: 9px; text-transform: uppercase; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; background: #eef2ff; color: #4338ca; font-size: 10px; }
        .items th { background: #f3f4f6; text-align: left; padding: 4px; font-size: 10px; border-bottom: 1px solid #e5e7eb; }
        .items td { border-bottom: 1px solid #f3f4f6; padding: 4px; }
        .ph { width: 48px; height: 48px; object-fit: cover; border: 1px solid #e5e7eb; border-radius: 4px; margin-right: 3px; }
    </style>
</head>
<body>
    <table>
        <tr>
            <td><h1>{{ $record->request_number }}</h1><span class="muted">{{ $record->borrower_name }}</span></td>
            <td style="text-align:right"><span class="badge">{{ strtoupper($record->display_status) }}</span><br><span class="muted">{{ now()->format('M d, Y H:i') }}</span></td>
        </tr>
    </table>

    <table style="margin-top:10px">
        <tr>
            <td style="width:50%">
                <div class="sec-title">User Information</div>
                <div><span class="label">Name</span> {{ $record->borrower_name }}</div>
                <div><span class="label">Email</span> {{ $record->borrower_email }}</div>
                <div><span class="label">Unit</span> {{ $record->unit?->name ?? '—' }} &nbsp; <span class="label">Dept</span> {{ $record->department?->name ?? '—' }}</div>
            </td>
            <td style="width:50%">
                <div class="sec-title">Purposes & Approval</div>
                <div><span class="label">Type</span> {{ $typeLabel }}</div>
                <div><span class="label">Borrow</span> {{ $fmt($record->borrow_date) }} &nbsp; <span class="label">Return</span> {{ $fmt($record->planned_return_date) }}</div>
                <div><span class="label">Approver</span> {{ $record->approver_name ?? '—' }} @if ($record->requires_acknowledge)&nbsp; <span class="label">Line Mgr</span> {{ $record->acknowledge_name ?? '—' }}@endif</div>
                @if ($record->purpose)<div><span class="label">Purpose</span> {{ $record->purpose }}</div>@endif
            </td>
        </tr>
    </table>

    <div class="sec">
        <div class="sec-title">Materials</div>
        <table class="items">
            <thead><tr><th>Material ID</th><th>Description</th><th>Qty</th><th>Return</th><th>Photos</th></tr></thead>
            <tbody>
                @foreach ($record->items as $it)
                    <tr>
                        <td>{{ $it->inventoryItem?->slug ?? '—' }}</td>
                        <td>{{ $it->item_name }}</td>
                        <td>{{ $it->qty }}</td>
                        <td>{{ $it->return_qty ?? '—' }}</td>
                        <td>
                            @php $iph = $it->inventoryItem?->primaryPhoto?->first(); @endphp
                            @if ($iph && $src = $img($iph->path))<img class="ph" src="{{ $src }}">@endif
                            @foreach ($it->photos->where('kind', 'take') as $p)@if ($src = $img($p->path))<img class="ph" src="{{ $src }}">@endif @endforeach
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if ($record->returned_at)
        <div class="sec">
            <div class="sec-title">Returning & Closure</div>
            <div><span class="label">Final Return</span> {{ $record->returned_at?->format('M d, Y H:i') }} &nbsp; <span class="label">Return Qty</span> {{ $record->items->sum(fn ($i) => $i->return_qty ?? 0) }}</div>
        </div>
    @endif

    <div class="sec muted" style="font-size:9px; margin-top:24px; border-top:1px solid #e5e7eb; padding-top:6px;">
        WIS — Warehouse Information System · Generated {{ now()->format('Y-m-d H:i') }}
    </div>
</body>
</html>
