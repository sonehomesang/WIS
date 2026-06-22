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
    $typeLabel = ['incorrect_supplied' => 'Incorrect', 'oversupplied' => 'Over', 'undersupplied' => 'Under', 'damaged' => 'Damaged', 'no_paperwork' => 'No paperwork', 'other' => 'Other'];
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
        .lbl { background: #f3f4f6; font-weight: bold; width: 20%; }
        .items th { border: 1px solid #555; background: #f3f4f6; padding: 4px; }
        .items td { border: 1px solid #555; padding: 4px; }
        .center { text-align: center; }
        .ph { width: 90px; height: 90px; object-fit: cover; border: 1px solid #999; margin: 2px; }
        .sec { font-weight: bold; margin: 10px 0 4px; font-size: 12px; }
        .sig { margin-top: 24px; width: 100%; }
        .sig td { width: 33%; text-align: center; vertical-align: top; padding: 0 10px; }
        .sigbox { border: 1px solid #555; height: 56px; margin-top: 4px; }
        .muted { color: #666; font-size: 9px; }
    </style>
</head>
<body>
    <h2>ໃບແຈ້ງຄວາມຜິດ / DISCREPANCY ADVICE</h2>
    <div class="sub">NAM THEUN 2 — WAREHOUSE INFORMATION SYSTEM</div>

    <table class="fields">
        <tr><td class="lbl">DA No.</td><td style="width:30%">{{ $record->da_number }}</td><td class="lbl">ວັນທີ</td><td>{{ $fmt($record->date) }}</td></tr>
        <tr><td class="lbl">Supplier</td><td>{{ $record->supplier_name ?? '—' }}</td><td class="lbl">PO</td><td>{{ $record->po_number ?? '—' }}</td></tr>
        <tr><td class="lbl">ປະເພດຄວາມຜິດ</td><td colspan="3">{{ collect($record->discrepancy_types ?? [])->map(fn ($t) => $typeLabel[$t] ?? $t)->implode(', ') ?: '—' }}</td></tr>
    </table>

    <div class="sec">A · Items</div>
    <table class="items">
        <thead><tr><th>#</th><th>Stock</th><th>Description</th><th>Ordered</th><th>Delivered</th><th>Received</th></tr></thead>
        <tbody>
            @foreach ($record->items as $it)
                <tr><td class="center">{{ $loop->iteration }}</td><td>{{ $it->stock_code ?? '—' }}</td><td>{{ $it->description }}</td><td class="center">{{ $it->qty_ordered }}</td><td class="center">{{ $it->qty_delivered }}</td><td class="center">{{ $it->qty_received }}</td></tr>
            @endforeach
        </tbody>
    </table>

    @if ($record->photos->count())
        <div class="sec">Photos</div>
        @foreach ($record->photos as $p)@if ($src = $img($p->path))<img class="ph" src="{{ $src }}">@endif @endforeach
    @endif

    @if ($record->purchasing_decisions)
        <div class="sec">B · Purchasing</div>
        <table class="fields">
            <tr><td class="lbl">Decisions</td><td colspan="3">{{ collect($record->purchasing_decisions)->implode(', ') }}</td></tr>
            @if ($record->purchasing_note)<tr><td class="lbl">Note</td><td colspan="3">{{ $record->purchasing_note }}</td></tr>@endif
        </table>
    @endif

    @if ($record->status === 'resolved')
        <div class="sec">C · Leader</div>
        <table class="fields">
            <tr><td class="lbl">Resolution</td><td colspan="3">{{ $record->resolution_action ?? '—' }}</td></tr>
            <tr><td class="lbl">Next step</td><td colspan="3">{{ $record->next_step }}</td></tr>
        </table>
    @endif

    <table class="sig">
        <tr>
            <td>Warehouse<div class="sigbox"></div><div class="muted">{{ $record->raised_by_name }} · {{ $fmt($record->raised_at) }}</div></td>
            <td>Purchasing<div class="sigbox"></div><div class="muted">{{ $fmt($record->purchasing_decided_at) }}</div></td>
            <td>Leader<div class="sigbox"></div><div class="muted">{{ $record->approved_by_name }} · {{ $fmt($record->approved_at) }}</div></td>
        </tr>
    </table>
</body>
</html>
