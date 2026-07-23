<?php

use App\Livewire\Settings\Translations as TranslationsPage;
use App\Models\Translation;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($this->admin);
});

test('replace map applies and busts cache on save', function () {
    Translation::create(['type' => 'replace', 'source' => 'ສິນຄ້າ', 'target' => 'ວັດສະດຸ', 'is_active' => true]);
    expect(Translation::applyReplacements('ລາຍການ ສິນຄ້າ ທັງໝົດ'))->toBe('ລາຍການ ວັດສະດຸ ທັງໝົດ');

    // inactive pair is ignored (model save busts the cache)
    $t = Translation::first();
    $t->is_active = false;
    $t->save();
    expect(Translation::applyReplacements('ສິນຄ້າ'))->toBe('ສິນຄ້າ');
});

test('longer source wins over shorter (ordering)', function () {
    Translation::create(['type' => 'replace', 'source' => 'ໃບເບີກ', 'target' => 'AAA']);
    Translation::create(['type' => 'replace', 'source' => 'ໃບເບີກ ວັດສະດຸ', 'target' => 'BBB']);
    expect(Translation::applyReplacements('ໃບເບີກ ວັດສະດຸ'))->toBe('BBB');
});

test('replace list paginates 10 per page', function () {
    for ($n = 1; $n <= 25; $n++) {
        Translation::create(['type' => 'replace', 'group' => 'g', 'source' => "s{$n}", 'target' => "t{$n}"]);
    }

    Livewire::test(TranslationsPage::class)
        ->assertSet('repTotal', 25)
        ->assertSet('repPages', 3)
        ->assertCount('rep', 10)        // page 1
        ->call('goRep', 3)
        ->assertSet('repPage', 3)
        ->assertCount('rep', 5)         // last page remainder
        ->call('goRep', 99)             // out of range → clamped to last page
        ->assertSet('repPage', 3);
});

test('term override resolves with fallback', function () {
    expect(Translation::term('status.draft', 'ຮ່າງ'))->toBe('ຮ່າງ');
    Translation::create(['type' => 'term', 'source' => 'status.draft', 'target' => 'ກຳລັງເຮັດ']);
    expect(Translation::term('status.draft', 'ຮ່າງ'))->toBe('ກຳລັງເຮັດ');
});

test('middleware replaces wording in rendered HTML', function () {
    Translation::create(['type' => 'replace', 'source' => 'ສະບາຍດີ', 'target' => 'ໂລກສະບາຍ', 'is_active' => true]);
    $this->get('/dashboard')->assertOk()->assertSee('ໂລກສະບາຍ', false)->assertDontSee('ສະບາຍດີ,', false);
});

test('the Translations page itself is also translated (chrome, not source cells)', function () {
    // an override for one of the page's own buttons
    Translation::create(['type' => 'replace', 'source' => '🔄 ດຶງ ຄຳ ໃໝ່', 'target' => '🔄 ໂຫຼດ ຄຳ ໃໝ່', 'is_active' => true]);

    $html = $this->get(route('settings.translations'))->assertOk()->getContent();

    // the button's visible text node is now replaced (this route was skipped before).
    // Source/target editor cells are wire:model inputs hydrated from the Livewire
    // snapshot (unicode-escaped JSON in an attribute), so they are never touched.
    expect($html)->toContain('>🔄 ໂຫຼດ ຄຳ ໃໝ່');
});

test('replacement only touches text nodes + safe attrs, never code', function () {
    Translation::create(['type' => 'replace', 'source' => 'Reports', 'target' => 'ລາຍງານ', 'is_active' => true]);
    $html = '<a class="Reports" value="Reports" title="Reports" wire:confirm="Reports">Reports</a><script>var Reports=1;</script>';
    $r = Translation::applyReplacements($html);

    expect($r)->toContain('class="Reports"')           // class untouched
        ->toContain('value="Reports"')                 // value untouched (option/form values safe)
        ->toContain('title="ລາຍງານ"')                   // safe attr translated
        ->toContain('wire:confirm="ລາຍງານ"')            // confirm dialog translated
        ->toContain('>ລາຍງານ<')                         // text node translated
        ->toContain('var Reports=1;');                 // <script> untouched
});

test('admin can add + save a replace pair via the page', function () {
    Livewire::test(TranslationsPage::class)
        ->call('addRep')
        ->set('rep.0.source', 'ສິນຄ້າ')
        ->set('rep.0.target', 'ວັດສະດຸ')
        ->call('save', 'replace')
        ->assertSet('savedOk', true)
        ->assertSee('ບັນທຶກ 1 ຄຳ ສຳເລັດ');

    expect(Translation::where('type', 'replace')->where('source', 'ສິນຄ້າ')->value('target'))->toBe('ວັດສະດຸ');
});

test('blank or duplicate sources are skipped on save', function () {
    Livewire::test(TranslationsPage::class)
        ->call('addRep')->set('rep.0.source', '')->set('rep.0.target', 'x')
        ->call('addRep')->set('rep.1.source', 'ກີບ')->set('rep.1.target', 'LAK')
        ->call('addRep')->set('rep.2.source', 'ກີບ')->set('rep.2.target', 'DUP')
        ->call('save', 'replace');

    expect(Translation::where('source', 'ກີບ')->count())->toBe(1);
    expect(Translation::where('source', 'ກີບ')->value('target'))->toBe('LAK');
    expect(Translation::where('source', '')->count())->toBe(0);
});

test('extract command seeds the catalogue from blades', function () {
    $this->artisan('translations:extract')->assertSuccessful();
    expect(Translation::where('type', 'replace')->where('note', 'auto')->count())->toBeGreaterThan(50);
    // catalogue rows are identity (target=source) so they don't alter output yet
    expect(Translation::applyReplacements('xyz'))->toBe('xyz');
});

test('translations page is forbidden without settings permission', function () {
    $u = User::factory()->create(['is_super_admin' => false]);
    $u->assignRole('requester');
    $this->actingAs($u);
    Livewire::test(TranslationsPage::class)->assertForbidden();
});

test('the extractor pulls hardcoded strings into the catalogue, idempotently', function () {
    $r1 = App\Support\TranslationExtractor::run();
    expect($r1['created'])->toBeGreaterThan(0);
    expect($r1['total'])->toBe($r1['created']);                 // fresh DB → all created
    expect(Translation::where('type', 'replace')->count())->toBe($r1['total']);

    $r2 = App\Support\TranslationExtractor::run();               // again
    expect($r2['created'])->toBe(0);                            // nothing new
    expect($r2['total'])->toBe($r1['total']);
});

test('the sync button pulls terms for an admin', function () {
    Livewire::test(TranslationsPage::class)
        ->call('syncTerms')
        ->assertSet('savedOk', true);
    expect(Translation::where('type', 'replace')->count())->toBeGreaterThan(0);
});

test('extractor captures dropdown/label text and rejects code leaks', function () {
    $phrases = new ReflectionMethod(App\Support\TranslationExtractor::class, 'phrases');
    $phrases->setAccessible(true);

    // A Lao word ending in ດ (e0 ba 94) used to be sheared by the byte-based
    // trim() char-list (— = e2 80 94) → invalid UTF-8 → dropped. Dropdown
    // options are lowercase English that the strict label filter also rejected.
    $html = <<<'BLADE'
<select wire:model="type">
    <option value="">ທຸກ ໝວດ (ໜ້າ) — {{ $total }} ຄຳ</option>
    <option value="">ທຸກ ປະເພດ</option>
    <option value="available">available</option>
    <option value="low-stock">low-stock</option>
    <option value="active">active (in use)</option>
</select>
<label>transactionScope</label>
@if($record->deleted_reason)
    <div>ok</div>
@endif
<svg><path d="M6 18L18 6M6 6l12 12"/></svg>
BLADE;

    $out = $phrases->invoke(null, $html);

    // recovered — static text either side of a {{ }} binding, Lao ending in ດ,
    // lowercase + kebab options, balanced-paren label
    expect($out)->toContain('ທຸກ ໝວດ (ໜ້າ)')
        ->toContain('ທຸກ ປະເພດ')
        ->toContain('available')
        ->toContain('low-stock')
        ->toContain('active (in use)');

    // rejected — PHP arrow leak, camelCase identifier, SVG path data
    expect($out)->not->toContain('deleted_reason)')
        ->not->toContain('transactionScope')
        ->not->toContain('M6 18L18 6M6 6l12 12');
});
