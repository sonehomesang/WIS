<?php

use App\Livewire\Settings\System;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('super admin can update global VAT', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));

    Livewire::test(System::class)
        ->set('vat_rate', 7)
        ->set('vat_enabled', true)
        ->call('save')
        ->assertHasNoErrors();

    expect(Setting::get('vat')['rate'])->toEqual(7.0);
});

test('super admin can save letterhead (company info + logo)', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));
    Storage::fake('public');

    Livewire::test(System::class)
        ->set('lhCompanyLo', 'ບໍລິສັດ ທົດສອບ')
        ->set('lhCompanyEn', 'Test Co')
        ->set('lhAddress3', 'PO Box 5862, Vientiane')
        ->set('lhFax', '856-21-263 901')
        ->set('lhWebsite', 'www.namtheun2.com')
        ->set('lhFooter', 'footer line')
        ->set('lhLogo', UploadedFile::fake()->image('logo.png', 120, 120))
        ->call('saveLetterhead')
        ->assertHasNoErrors();

    $lh = Setting::get('letterhead');
    expect($lh['company_name'])->toBe('ບໍລິສັດ ທົດສອບ');
    expect($lh['address3'])->toBe('PO Box 5862, Vientiane');
    expect($lh['fax'])->toBe('856-21-263 901');
    expect($lh['website'])->toBe('www.namtheun2.com');
    expect($lh['footer_note'])->toBe('footer line');
    expect($lh['logo_path'])->not->toBeNull();
    Storage::disk('public')->assertExists($lh['logo_path']);
});

test('non-permitted user cannot open system settings', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => false]));
    Livewire::test(System::class)->assertForbidden();
});
