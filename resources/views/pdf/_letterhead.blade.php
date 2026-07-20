@php
    // Shared letterhead for all PDF exports. ດຶງ Settings → System (key 'letterhead').
    // ຄ່າ ວ່າງ → default ຂອງ ບໍລິສັດ ໄຟຟ້າ ນ້ຳເທີນ 2. $docTitle (ບັງຄັບ) · $docSub (optional).
    $lh = \App\Models\Setting::get('letterhead', []);
    $companyLo = ($lh['company_name'] ?? '') ?: 'ບໍລິສັດ ໄຟຟ້າ ນ້ຳເທີນ 2';
    $companyEn = ($lh['company_name_en'] ?? '') ?: 'Nam Theun 2 Power Company Ltd.';
    $addrLines = collect([
        ($lh['address1'] ?? '') ?: 'Head Office, House No.249, Unit 15,',
        ($lh['address2'] ?? '') ?: 'Lao-Thai Road, Vatnak Village, Sisattanak District,',
        ($lh['address3'] ?? '') ?: 'PO Box: 5862, Vientiane, Lao PDR',
    ])->filter();
    $contacts = collect([
        ['Tel', ($lh['phone'] ?? '') ?: '856-21-263 900'],
        ['Fax', ($lh['fax'] ?? '') ?: '856-21-263 901'],
        ['E-mail', ($lh['email'] ?? '') ?: 'dcc@namtheun2.com'],
        ['Web', ($lh['website'] ?? '') ?: 'www.namtheun2.com'],
    ])->filter(fn ($c) => ! empty($c[1]));

    $logoSrc = null;
    if (! empty($lh['logo_path'])) {
        try {
            $abs = \Illuminate\Support\Facades\Storage::disk('public')->path($lh['logo_path']);
            if (is_file($abs)) {
                $ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION)) === 'png' ? 'png' : 'jpeg';
                $logoSrc = "data:image/{$ext};base64,".base64_encode(file_get_contents($abs));
            }
        } catch (\Throwable $e) {
        }
    }
@endphp
<table style="width:100%; border-collapse:collapse; border-bottom:2.5px solid #1e3a5f; margin-bottom:12px;">
    <tr>
        @if ($logoSrc)
        <td style="width:86px; vertical-align:top; padding:0 12px 8px 0;">
            <img src="{{ $logoSrc }}" style="width:78px; height:78px; object-fit:contain;">
        </td>
        @endif
        <td style="vertical-align:top; padding-bottom:8px;">
            <div style="font-size:15px; font-weight:bold; color:#1e3a5f; line-height:1.3;">{{ $companyLo }}</div>
            <div style="font-size:10px; font-weight:bold; color:#374151; letter-spacing:0.3px;">{{ $companyEn }}</div>
            @foreach ($addrLines as $line)
                <div style="font-size:8px; color:#4b5563; line-height:1.65;">{{ $line }}</div>
            @endforeach
        </td>
        @if ($contacts->isNotEmpty())
        <td style="width:176px; vertical-align:bottom; padding-bottom:8px;">
            <table style="border-collapse:collapse;">
                @foreach ($contacts as $c)
                <tr>
                    <td style="font-size:8px; color:#6b7280; padding:0 0 2px 0; white-space:nowrap;">{{ $c[0] }}</td>
                    <td style="font-size:8px; color:#374151; padding:0 0 2px 4px;">: {{ $c[1] }}</td>
                </tr>
                @endforeach
            </table>
        </td>
        @endif
    </tr>
</table>

<div style="text-align:center; margin-bottom:10px;">
    <span style="font-size:15px; font-weight:bold;">{{ $docTitle }}</span>
    @if (! empty($docSub))<div style="font-size:9px; color:#6b7280;">{{ $docSub }}</div>@endif
</div>
