<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('a locked/deactivated user is logged out + redirected on their next request', function () {
    $u = User::factory()->create(['is_super_admin' => true, 'status' => 'active']);
    $this->actingAs($u);

    // active → ຜ່ານ ປົກກະຕິ
    $this->get(route('dashboard'))->assertOk();

    // admin ລັອກ ບັນຊີ → ຄຳຂໍ ຕໍ່ ໄປ ຖືກ ຕັດ session + redirect (ບໍ່ ຕ້ອງ ລໍ session ໝົດ ອາຍຸ)
    $u->update(['status' => 'locked']);
    $this->get(route('dashboard'))->assertRedirect(route('login'));
    $this->assertGuest();
});
