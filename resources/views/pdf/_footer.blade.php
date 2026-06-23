@php
    $lh = \App\Models\Setting::get('letterhead', []);
    $note = $lh['footer_note'] ?? 'Warehouse Information System — Nam Theun 2';
@endphp
<div style="margin-top:18px; border-top:1px solid #9ca3af; padding-top:4px; font-size:8px; color:#6b7280; text-align:center;">
    {{ $note }} · ພິມວັນທີ: {{ now()->format('d/m/Y H:i') }}
</div>
