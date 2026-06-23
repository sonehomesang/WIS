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
    $stars = fn ($n) => str_repeat('★', (int) $n).str_repeat('☆', 5 - (int) $n);
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
        .lbl { background: #f3f4f6; font-weight: bold; width: 20%; }
        .items th { border: 1px solid #555; background: #f3f4f6; padding: 4px; }
        .items td { border: 1px solid #555; padding: 4px; vertical-align: top; }
        .center { text-align: center; }
        .sec { font-weight: bold; margin: 12px 0 4px; font-size: 12px; }
        .muted { color: #666; font-size: 9px; }
    </style>
</head>
<body>
    @include('pdf._letterhead', ['docTitle' => 'ບົດລາຍງານ ການເຂົ້າຮ່ວມ EXPO / EXPO REPORT'])

    <table class="fields">
        <tr><td class="lbl">ງານ</td><td style="width:30%">{{ $record->title }}</td><td class="lbl">ເລກທີ</td><td>{{ $record->expo_number }}</td></tr>
        <tr><td class="lbl">ສະຖານທີ່</td><td>{{ collect([$record->venue, $record->city, $record->country])->filter()->implode(', ') }}</td><td class="lbl">ວັນທີ</td><td>{{ $fmt($record->start_date) }}–{{ $fmt($record->end_date) }}</td></tr>
        <tr><td class="lbl">ຫົວข้อ</td><td>{{ $record->topic ?? '—' }}</td><td class="lbl">ບໍລິສັດໃນງານ</td><td>{{ $record->total_companies_at_expo ?? '—' }}</td></tr>
        <tr><td class="lbl">ຜູ້ໄປ</td><td colspan="3">{{ $record->attendees->pluck('user_name')->implode(', ') ?: '—' }}</td></tr>
    </table>

    <div class="sec">ບໍລິສັດໜ້າສົນໃຈ ({{ $record->companies->count() }})</div>
    <table class="items">
        <thead><tr><th style="width:4%">#</th><th class="center" style="width:22%">ບໍລິສັດ</th><th>ສິນຄ້າ / ບໍລິການ</th><th style="width:10%">ຄະແນນ</th><th style="width:26%">ຜູ້ຕິດຕໍ່</th></tr></thead>
        <tbody>
            @foreach ($record->companies as $c)
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td>{{ $c->name }}<div class="muted">{{ strtoupper($c->interest_level) }}@if ($c->country) · {{ $c->country }}@endif</div></td>
                    <td>{{ $c->products }}@if ($c->benefit)<div class="muted">→ {{ $c->benefit }}</div>@endif</td>
                    <td class="center">{{ $stars($c->score ?? 0) }}</td>
                    <td>
                        @forelse ($c->contacts as $k)
                            <div>{{ $k->name }}@if ($k->title) ({{ $k->title }})@endif<div class="muted">{{ collect([$k->email, $k->phone, $k->app_contact])->filter()->implode(' · ') }}</div></div>
                        @empty — @endforelse
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if ($record->attendees->whereNotNull('opinion')->count())
        <div class="sec">ຄວາມຄິດເຫັນ ພະນັກງານ</div>
        <table class="fields">
            @foreach ($record->attendees->whereNotNull('opinion') as $a)
                <tr><td class="lbl">{{ $a->user_name }}</td><td>{{ $a->opinion }}</td></tr>
            @endforeach
        </table>
    @endif

    <div class="sec">ພາບລວມ ແລະ ຄັ້ງຕໍ່ໄປ</div>
    <table class="fields">
        <tr><td class="lbl">ພາບລວມ</td><td colspan="3">{{ $record->feedback ?? '—' }}</td></tr>
        <tr><td class="lbl">ຄັ້ງຕໍ່ໄປ</td><td>{{ $record->next_event_location ?? '—' }}</td><td class="lbl">ຂໍ້ສະເໜີ</td><td>{{ $record->next_proposal ?? '—' }}</td></tr>
    </table>

    @include('pdf._footer')
</body>
</html>
