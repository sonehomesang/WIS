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

test('replacement only touches text nodes + safe attrs, never code', function () {
    Translation::create(['type' => 'replace', 'source' => 'Reports', 'target' => 'ລາຍງານ', 'is_active' => true]);
    $html = '<a class="Reports" title="Reports">Reports</a><script>var Reports=1;</script>';
    $r = Translation::applyReplacements($html);

    expect($r)->toContain('class="Reports"');     // class untouched
    expect($r)->toContain('title="ລາຍງານ"');       // safe attr translated
    expect($r)->toContain('>ລາຍງານ<');             // text node translated
    expect($r)->toContain('var Reports=1;');       // <script> untouched
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
