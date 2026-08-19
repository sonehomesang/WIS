<?php
use App\Models\Translation;
use Illuminate\Support\Facades\Cache;

beforeEach(fn () => Cache::forget(Translation::CACHE_REPLACE));

test('a replace source with & is applied to Blade-escaped HTML (Equipment & Tools)', function () {
    Translation::create(['type' => 'replace', 'source' => 'Equipment & Tools', 'target' => 'Tools & Machinery', 'is_active' => true]);

    // Blade renders the label escaped: "Equipment &amp; Tools"
    $html = '<span>Equipment &amp; Tools</span>';
    $out = Translation::applyReplacements($html);

    expect($out)->toBe('<span>Tools &amp; Machinery</span>'); // matched + target re-encoded
});

test('a plain source (no special chars) still works', function () {
    Translation::create(['type' => 'replace', 'source' => 'WH Inventories', 'target' => 'WH Inventory List', 'is_active' => true]);
    expect(Translation::applyReplacements('<a>WH Inventories</a>'))->toBe('<a>WH Inventory List</a>');
});
