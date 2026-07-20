<?php

namespace App\Livewire\Settings;

use App\Models\Setting;
use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** ຕັ້ງຄ່າ ຕົວ ສົ່ງ email (SMTP) — admin ຕັ້ງ/ປ່ຽນ ເອງ, ເກັບ ໃນ DB. */
#[Layout('layouts.app')]
class Email extends Component
{
    public bool $enabled = false;     // true = smtp · false = log (ບໍ່ ສົ່ງ ອອກ)

    public string $host = '';

    public ?int $port = 587;

    public string $scheme = 'tls';    // tls (587) · ssl (465) · '' (none)

    public string $username = '';

    public string $password = '';     // ວ່າງ ຕອນ ໂຫຼດ — ໃສ່ ຄ່າ ໃໝ່ ຈຶ່ງ ປ່ຽນ

    public bool $hasPassword = false;

    public string $fromAddress = '';

    public string $fromName = '';

    public string $testTo = '';

    public string $result = '';

    public string $resultType = '';   // ok · error

    public function mount(): void
    {
        abort_unless(auth()->user()->can('settings.view'), 403);
        $m = Setting::get('mail', []);
        $this->enabled = ($m['mailer'] ?? 'log') === 'smtp';
        $this->host = $m['host'] ?? '';
        $this->port = (int) ($m['port'] ?? 587);
        $this->scheme = $m['scheme'] ?? 'tls';
        $this->username = $m['username'] ?? '';
        $this->hasPassword = ! empty($m['password']);
        $this->fromAddress = $m['from_address'] ?? '';
        $this->fromName = $m['from_name'] ?? (config('app.name') ?: 'WH');
        $this->testTo = auth()->user()->email;
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can('settings.edit'), 403);
        $req = $this->enabled ? 'required' : 'nullable';
        $this->validate([
            'host' => [$req, 'string', 'max:200'],
            'port' => [$req, 'integer', 'min:1', 'max:65535'],
            'scheme' => ['nullable', 'in:tls,ssl,'],
            'username' => ['nullable', 'string', 'max:200'],
            'fromAddress' => [$req, 'email', 'max:200'],
            'fromName' => ['nullable', 'string', 'max:200'],
        ], [], [
            'host' => 'SMTP host', 'port' => 'port', 'fromAddress' => 'from address',
        ]);

        $existing = Setting::get('mail', []);
        // ວ່າງ = ຮັກສາ ລະຫັດ ເກົ່າ (ເຂົ້າລະຫັດ ໄວ້); ໃສ່ ໃໝ່ = ເຂົ້າລະຫัດ ໃໝ່
        $password = $this->password !== ''
            ? Crypt::encryptString($this->password)
            : ($existing['password'] ?? null);

        Setting::put('mail', [
            'mailer' => $this->enabled ? 'smtp' : 'log',
            'host' => $this->host,
            'port' => (int) $this->port,
            'scheme' => $this->scheme,
            'username' => $this->username,
            'password' => $password,
            'from_address' => $this->fromAddress,
            'from_name' => $this->fromName,
        ], auth()->id());

        Cache::forget('settings.mail');
        $this->password = '';
        $this->hasPassword = (bool) $password;
        $this->dispatch('saved');
    }

    /** ສົ່ງ email ທົດສອບ ດ້ວຍ ຄ່າ ທີ່ ບັນທຶກ ໄວ້. */
    public function sendTest(): void
    {
        abort_unless(auth()->user()->can('settings.edit'), 403);
        $this->validate(['testTo' => ['required', 'email']], [], ['testTo' => 'test email']);
        $this->result = '';

        Cache::forget('settings.mail');
        AppServiceProvider::applyMailSettings();   // ໃຊ້ ຄ່າ ຫຼ້າສຸດ ໃນ request ນີ້

        try {
            Mail::raw('ນີ້ ຄື email ທົດສອບ ຈາກ ລະບົບ WH — Warehouse. ຖ້າ ໄດ້ ຮັບ ໝາຍ ຄວາມ ວ່າ SMTP ຕັ້ງ ຖືກ ແລ້ວ.',
                fn ($msg) => $msg->to($this->testTo)->subject('WH — ທົດສອບ SMTP'));
            $this->result = 'ສົ່ງ email ທົດສອບ ໄປ '.$this->testTo.' ແລ້ວ — ກະລຸນາ ເຊັກ inbox.';
            $this->resultType = 'ok';
        } catch (\Throwable $e) {
            $this->result = 'ສົ່ງ ບໍ່ ສຳເລັດ: '.$e->getMessage();
            $this->resultType = 'error';
        }
    }

    public function render(): View
    {
        return view('livewire.settings.email');
    }
}
