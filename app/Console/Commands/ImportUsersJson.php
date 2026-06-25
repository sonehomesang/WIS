<?php

namespace App\Console\Commands;

use App\Models\BorrowRecord;
use App\Models\Department;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * users:import-json — import WIS users + departments → WH (Phase 6.6.7/B).
 *
 * Users imported as is_pre_created (no real password — login ໄດ້ເມื่อ sign-in + match email).
 * NEVER auto-sets is_super_admin (bypass flag). Roles assigned via Spatie if the role exists.
 * Dedup by email. Then re-links borrow_records.borrower_user_id by email.
 */
class ImportUsersJson extends Command
{
    protected $signature = 'users:import-json {path}';

    protected $description = 'Import WIS users + departments and re-link borrow borrowers';

    public function handle(): int
    {
        $path = $this->argument('path');
        if (! is_file($path)) {
            $this->error("ບໍ່ພົບໄຟລ໌: {$path}");

            return self::FAILURE;
        }
        $data = json_decode(file_get_contents($path), true);
        if (! is_array($data)) {
            $this->error('JSON ບໍ່ຖືກຕ້ອງ');

            return self::FAILURE;
        }

        // ── unit lookup (by slug + name, lowercased) ──
        $unitByKey = [];
        foreach (Unit::all() as $u) {
            $unitByKey[mb_strtolower($u->slug)] = $u->id;
            $unitByKey[mb_strtolower($u->name)] = $u->id;
        }

        // ── 1. departments (add missing ທີ່ map unit ໄດ້; dept ຕ້ອງມີ unit) ──
        $depAdded = 0;
        $depSkipped = 0;
        foreach ($data['departments'] ?? [] as $d) {
            $name = trim($d['name'] ?? '');
            if ($name === '' || Department::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->exists()) {
                continue;
            }
            $unitId = $unitByKey[mb_strtolower($d['unit_code'] ?? '')] ?? null;
            if (! $unitId) {
                $depSkipped++; // ບໍ່ map unit ໄດ້ → ບໍ່ເພີ່ມ (dept ຕ້ອງມີ unit)

                continue;
            }
            Department::create([
                'unit_id' => $unitId,
                'slug' => $this->uniqueSlug(Department::class, $name),
                'name' => $name,
                'description' => $d['description'] ?? null,
                'is_active' => $d['is_active'] ?? true,
            ]);
            $depAdded++;
        }
        $depByName = Department::all()->mapWithKeys(fn ($d) => [mb_strtolower($d->name) => $d->id])->all();

        // ── 2. users (dedup by email) ──
        $roleNames = Role::pluck('name')->map(fn ($r) => mb_strtolower($r))->flip();
        $uAdded = 0;
        $uSkipped = 0;
        $rolesAssigned = 0;
        foreach ($data['users'] ?? [] as $u) {
            $email = mb_strtolower(trim($u['email'] ?? ''));
            if ($email === '' || User::where('email', $email)->exists()) {
                $uSkipped++;

                continue;
            }
            $user = User::create([
                'email' => $email,
                'display_name' => $u['display_name'] ?? $email,
                'phone_number' => $u['phone'] ?? null,
                'unit_id' => $unitByKey[mb_strtolower($u['unit_code'] ?? '')] ?? null,
                'department_id' => $depByName[mb_strtolower($u['department_name'] ?? '')] ?? null,
                'status' => $u['status'] ?? 'active',
                'auth_provider' => 'domain',
                'is_pre_created' => true,
                'is_super_admin' => false, // ບໍ່ auto-grant bypass
                'password' => bcrypt(Str::random(40)),
                'email_verified_at' => now(), // vetted AD/HR account — skip the verify wall
            ]);
            $role = mb_strtolower($u['role'] ?? '');
            if ($role !== '' && $roleNames->has($role)) {
                $user->assignRole($u['role']);
                $rolesAssigned++;
            }
            $uAdded++;
        }

        // ── 3. re-link borrow borrowers by email ──
        $linked = 0;
        $idByEmail = User::pluck('id', 'email')->all();
        foreach (BorrowRecord::query()->get(['id', 'borrower_email', 'borrower_user_id']) as $r) {
            $uid = $idByEmail[mb_strtolower($r->borrower_email)] ?? null;
            if ($uid && $uid !== $r->borrower_user_id) {
                DB::table('borrow_records')->where('id', $r->id)->update(['borrower_user_id' => $uid]);
                $linked++;
            }
        }

        $this->newLine();
        $this->info("✓ Departments added: {$depAdded} (skipped {$depSkipped} — no unit match)");
        $this->info("✓ Users added: {$uAdded} (skipped {$uSkipped}, roles assigned {$rolesAssigned})");
        $this->info("✓ Borrow borrowers re-linked: {$linked}");

        return self::SUCCESS;
    }

    private function uniqueSlug(string $model, string $base): string
    {
        $slug = Str::slug($base) ?: 'item';
        $original = $slug;
        $i = 2;
        while ($model::where('slug', $slug)->exists()) {
            $slug = $original.'-'.$i++;
        }

        return $slug;
    }
}
