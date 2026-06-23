@php
    // Shared letterhead for all PDF exports. ດຶງ Settings → System (key 'letterhead').
    // $docTitle (ບັງຄັບ) · $docSub (optional).
    $lh = \App\Models\Setting::get('letterhead', []);
    $companyLo = $lh['company_name'] ?? 'ບໍລິສັດ ໄຟຟ້າ ນ້ຳເທີນ 2';
    $companyEn = $lh['company_name_en'] ?? 'NAM THEUN 2 POWER COMPANY';
    $addr = collect([$lh['address1'] ?? null, $lh['address2'] ?? null])->filter()->implode(', ');
    $contact = collect([$lh['phone'] ?? null, $lh['email'] ?? null])->filter()->implode(' · ');
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
<table style="width:100%; border-collapse:collapse; border-bottom:2px solid #111827; margin-bottom:8px;">
    <tr>
        @if ($logoSrc)<td style="width:74px; vertical-align:middle; padding-bottom:6px;"><img src="{{ $logoSrc }}" style="width:64px; height:64px; object-fit:contain;"></td>@endif
        <td style="vertical-align:middle; padding-bottom:6px;">
            <div style="font-size:13px; font-weight:bold; color:#111827;">{{ $companyLo }}</div>
            <div style="font-size:9px; color:#374151;">{{ $companyEn }} — Warehouse Information System</div>
            @if ($addr)<div style="font-size:8px; color:#6b7280;">{{ $addr }}</div>@endif
            @if ($contact)<div style="font-size:8px; color:#6b7280;">{{ $contact }}</div>@endif
        </td>
    </tr>
</table>

<div style="text-align:center; margin-bottom:10px;">
    <span style="font-size:15px; font-weight:bold;">{{ $docTitle }}</span>
    @if (! empty($docSub))<div style="font-size:9px; color:#6b7280;">{{ $docSub }}</div>@endif
</div>
