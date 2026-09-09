<?php

namespace App\Http\Middleware;

use App\Support\SecuritySettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ເມື່ອ ເປີດ ນະໂຍບາຍ "ບັງຄັບ ຢືນຢັນ email" (Settings › System › Security):
 * ບັນຊີ ທ້ອງຖິ່ນ (auth_provider=password) ທີ່ ຍັງ ບໍ່ ຢືນຢັນ email ຈະ ຖືກ ພາ ໄປ
 * ໜ້າ verify-email ຈົນ ກວ່າ ຈະ ກົດ ລິ້ງ ຢືນຢັນ.
 *
 * ຍົກເວັ້ນ: ບັນຊີ domain (ຢືນຢັນ ຜ່ານ AD), super_admin (break-glass), ແລະ
 * ຄຳຂໍ livewire + ໜ້າ auth ທີ່ ຈຳເປັນ (ກັນ redirect loop).
 */
class EnsureEmailVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! SecuritySettings::verificationRequired()) {
            return $next($request);
        }

        $user = $request->user();

        if ($user
            && $user->auth_provider === 'password'
            && ! $user->is_super_admin
            && ! $user->hasVerifiedEmail()
            && ! $request->is('livewire/*')
            && ! $request->routeIs('verification.notice', 'verification.verify', 'logout', 'login', 'password.force')) {
            return redirect()->route('verification.notice');
        }

        return $next($request);
    }
}
