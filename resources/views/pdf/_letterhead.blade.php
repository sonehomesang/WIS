{{-- Full-width letterhead (block + centered title). ໃຊ້ ໂດຍ ຟອມ ທີ່ ຍັງ ບໍ່ ຈັດ 2 ຖັນ. --}}
<div style="border-bottom:2.5px solid #1e3a5f; padding-bottom:7px; margin-bottom:12px;">
    @include('pdf._letterhead_block')
</div>

<div style="text-align:center; margin-bottom:10px;">
    <span style="font-size:15px; font-weight:bold;">{{ $docTitle }}</span>
    @if (! empty($docSub))<div style="font-size:9px; color:#6b7280;">{{ $docSub }}</div>@endif
</div>
