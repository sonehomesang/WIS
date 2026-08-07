<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * ຖ້າ ບັນຊີ ຖືກ lock/deactivate (status !== active) ຫຼັງ login ໄປ ແລ້ວ →
 * ຕັດ session ໃນ ຄຳຂໍ ຕໍ່ ໄປ ທັນທີ (ບໍ່ ຕ້ອງ ລໍ session ໝົດ ອາຍຸ ~2 ຊົ່ວໂມງ).
 * ອະນຸຍາດ Livewire request (ໃຫ້ ຟອມ ເຮັດ ວຽກ) + ໜ້າ login (ກັນ loop).
 */
class EnsureActiveUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ($user->status ?? 'active') !== 'active'
            && ! $request->is('livewire/*')
            && ! $request->routeIs('login')) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['form.email' => 'ບັນຊີ ຖືກ ລັອກ — ຕິດຕໍ່ admin.']);
        }

        return $next($request);
    }
}
