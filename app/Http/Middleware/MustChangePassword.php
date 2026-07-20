<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ຖ້າ ຜູ້ໃຊ້ ຖືກ ໝາຍ must_change_password (ໃຫ້ ລະຫັດ ຊົ່ວຄາວ) →
 * ບັງຄັບ ໄປ ໜ້າ ຕັ້ງ ລະຫັດ ໃໝ່ ກ່ອນ ຈຶ່ງ ໃຊ້ ອັນ ອື່ນ ໄດ້.
 * ອະນຸຍາດ: ໜ້າ ຕັ້ງ ລະຫັດ ເອງ + ຄຳຂໍ Livewire (ຟອມ submit + logout).
 */
class MustChangePassword
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->must_change_password
            && ! $request->routeIs('password.force')
            && ! $request->is('livewire/*')) {
            return redirect()->route('password.force');
        }

        return $next($request);
    }
}
