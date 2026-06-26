<?php

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $email = '';

    /**
     * Send a password reset link to the provided email address.
     */
    public function sendPasswordResetLink(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $status = Password::sendResetLink(
            $this->only('email')
        );

        if ($status != Password::RESET_LINK_SENT) {
            $this->addError('email', __($status));

            return;
        }

        $this->reset('email');

        session()->flash('status', __($status));
    }
}; ?>

<div>
    <h1 class="text-lg font-semibold text-gray-800">ລືມ ລະຫັດຜ່ານ?</h1>
    <p class="text-sm text-gray-500 mt-1 mb-5">
        ປ້ອນ ອີເມວ ຂອງ ທ່ານ — ລະບົບ ຈະ ສົ່ງ ລິ້ງ ຕັ້ງ ລະຫັດຜ່ານ ໃໝ່ ໄປ ໃຫ້.
    </p>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="sendPasswordResetLink" class="space-y-4">
        <!-- Email Address -->
        <div>
            <label for="email" class="block text-sm font-medium text-gray-600 mb-1">ອີເມວ</label>
            <x-text-input wire:model="email" id="email" class="block w-full" type="email" name="email" required autofocus placeholder="you@namtheun2.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <button type="submit" wire:loading.attr="disabled"
                class="w-full inline-flex items-center justify-center gap-2 h-11 rounded-lg bg-sky-700 text-white text-sm font-semibold hover:bg-sky-800 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 transition disabled:opacity-60">
            ສົ່ງ ລິ້ງ ຕັ້ງ ລະຫັດຜ່ານ ໃໝ່
        </button>
    </form>

    <div class="mt-6 text-center">
        <a href="{{ route('login') }}" wire:navigate class="text-sm text-sky-700 hover:text-sky-900 hover:underline">← ກັບ ໄປ ໜ້າ login</a>
    </div>
</div>
