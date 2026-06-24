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
