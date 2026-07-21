@php
    $lh = \App\Models\Setting::get('letterhead', []);
    $note = ($lh['footer_note'] ?? '') ?: 'NTPC, SCU-Warehouse Information System';
@endphp
<style>
    .page-footer { position: fixed; bottom: 0; left: 0; right: 0; }
    .page-footer table { width: 100%; border-collapse: collapse; border-top: 1px solid #cbd5e1; }
    .page-footer td { padding-top: 4px; font-size: 8px; color: #6b7280; }
    .pagenum:before { content: counter(page); }
    .pagecount:before { content: counter(pages); }
</style>
<div class="page-footer">
    <table>
        <tr>
            <td style="text-align:left;">{{ $note }} · {{ now()->format('d/m/Y H:i') }}</td>
            <td style="text-align:right; white-space:nowrap;">Page <span class="pagenum"></span> / <span class="pagecount"></span></td>
        </tr>
    </table>
</div>
