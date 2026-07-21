@php
    // Shared letterhead for all PDF exports. ດຶງ Settings → System (key 'letterhead').
    // ໂລໂກ້ ເທິງ ສຸດ, ຂໍ້ມູນ ຢູ່ ກ້ອງ (1 ຖັນ). $docTitle (ບັງຄັບ) · $docSub (optional).
    $lh = \App\Models\Setting::get('letterhead', []);
    $companyLo = $lh['company_name'] ?? '';   // ວ່າງ = ບໍ່ ສະແດງ (admin ເອົາ ອອກ ໄດ້)
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
<div style="border-bottom:2.5px solid #1e3a5f; padding-bottom:7px; margin-bottom:12px;">
    @if ($logoSrc)
        <div style="margin-bottom:4px;"><img src="{{ $logoSrc }}" style="height:50px;"></div>
    @endif
    @if ($companyLo)
        <div style="font-size:13px; font-weight:bold; color:#1e3a5f; line-height:1.35;">{{ $companyLo }}</div>
    @endif
    <div style="font-size:12px; font-weight:bold; color:#374151; line-height:1.35;">{{ $companyEn }}</div>
    @foreach ($addrLines as $line)
        <div style="font-size:8px; color:#4b5563; line-height:1.4;">{{ $line }}</div>
    @endforeach
    @if ($contacts->isNotEmpty())
        <table style="border-collapse:collapse; margin-top:1px;">
            @foreach ($contacts as $c)
            <tr>
                <td style="font-size:8px; color:#6b7280; padding:0 0 1px 0; white-space:nowrap;">{{ $c[0] }}</td>
                <td style="font-size:8px; color:#4b5563; padding:0 0 1px 5px;">: {{ $c[1] }}</td>
            </tr>
            @endforeach
        </table>
    @endif
</div>

<div style="text-align:center; margin-bottom:10px;">
    <span style="font-size:15px; font-weight:bold;">{{ $docTitle }}</span>
    @if (! empty($docSub))<div style="font-size:9px; color:#6b7280;">{{ $docSub }}</div>@endif
</div>
