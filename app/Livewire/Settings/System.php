<?php

namespace App\Livewire\Settings;

use App\Models\Setting;
use App\Services\RequestService;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class System extends Component
{
    use WithFileUploads;

    public $vat_rate = 10;

    public bool $vat_enabled = true;

    // ── General / App ──
    public string $appName = '';

    public ?int $defaultBorrowDays = 7;

    /** ໂໝດ ທະຍອຍ ສົ່ງ ຍ່ອຍ / ທະຍອຍ ຮັບຄືນ (workflow.borrow.partial_return). */
    public bool $borrowPartialReturn = false;

    /** ຂັ້ນ ຮັບຊາບ (off|optional|required) ແລະ ອະນຸມັດ (required|off) ຂອງ ການ ຢືມ. */
    public string $borrowAcknowledge = 'optional';

    public string $borrowApprove = 'required';

    /** @var array<string,bool> ໂມດູລ ເປີດ/ປິດ (feature flags). */
    public array $modules = [];

    // ── Currency ──
    public string $curPrimary = 'THB';

    public string $curSecondary = 'LAK';

    public $exchangeRate = 0;

    public bool $curSecondaryEnabled = true;

    /** @var array<string,bool> Request form fields ເປີດ/ປິດ */
    public array $reqFields = [];

    // ── Letterhead (PDF) ──
    public string $lhCompanyLo = '';

    public string $lhCompanyEn = '';

    public string $lhAddress1 = '';

    public string $lhAddress2 = '';

    public string $lhAddress3 = '';

    public string $lhPhone = '';

    public string $lhFax = '';

    public string $lhEmail = '';

    public string $lhWebsite = '';

    public string $lhFooter = '';

    public ?string $lhLogoPath = null;

    public $lhLogo;   // upload

    public function mount(): void
    {
        abort_unless(auth()->user()->can('settings.view'), 403);
        $vat = Setting::get('vat', ['rate' => 10, 'enabled' => true]);
        $this->vat_rate = $vat['rate'] ?? 10;
        $this->vat_enabled = (bool) ($vat['enabled'] ?? true);

        $saved = Setting::get('request', [])['fields'] ?? [];
        foreach (RequestService::FIELD_KEYS as $k) {
            $this->reqFields[$k] = (bool) ($saved[$k] ?? true);
        }

        $gen = Setting::get('general', []);
        $this->appName = $gen['app_name'] ?? 'WH — Warehouse';
        $this->defaultBorrowDays = (int) ($gen['default_borrow_days'] ?? 7);

        $wfBorrow = Setting::get('workflow', [])['borrow'] ?? [];
        $this->borrowPartialReturn = ($wfBorrow['partial_return'] ?? 'off') === 'on';
        $this->borrowAcknowledge = $wfBorrow['acknowledge'] ?? 'optional';
        $this->borrowApprove = $wfBorrow['approve'] ?? 'required';

        $mods = Setting::get('modules', []);
        foreach (\App\Support\Modules::TOGGLEABLE as $mk) {
            $this->modules[$mk] = (bool) ($mods[$mk] ?? true);
        }

        $cur = Setting::get('currency', []);
        $this->curPrimary = $cur['primary'] ?? 'THB';
        $this->curSecondary = $cur['secondary'] ?? 'LAK';
        $this->exchangeRate = $cur['exchange_rate'] ?? 0;
        $this->curSecondaryEnabled = (bool) ($cur['secondary_enabled'] ?? true);

        $lh = Setting::get('letterhead', []);
        $this->lhCompanyLo = array_key_exists('company_name', $lh) ? (string) ($lh['company_name'] ?? '') : 'ບໍລິສັດ ໄຟຟ້າ ນ້ຳເທີນ 2';
        $this->lhCompanyEn = ($lh['company_name_en'] ?? '') ?: 'Nam Theun 2 Power Company Ltd.';
        $this->lhAddress1 = ($lh['address1'] ?? '') ?: 'Head Office, House No.249, Unit 15,';
        $this->lhAddress2 = ($lh['address2'] ?? '') ?: 'Lao-Thai Road, Vatnak Village, Sisattanak District,';
        $this->lhAddress3 = ($lh['address3'] ?? '') ?: 'PO Box: 5862, Vientiane, Lao PDR';
        $this->lhPhone = ($lh['phone'] ?? '') ?: '856-21-263 900';
        $this->lhFax = ($lh['fax'] ?? '') ?: '856-21-263 901';
        $this->lhEmail = ($lh['email'] ?? '') ?: 'dcc@namtheun2.com';
        $this->lhWebsite = ($lh['website'] ?? '') ?: 'www.namtheun2.com';
        $this->lhFooter = $lh['footer_note'] ?? '';
        $this->lhLogoPath = $lh['logo_path'] ?? null;
    }

    public function saveGeneral(): void
    {
        abort_unless(auth()->user()->can('settings.edit'), 403);
        $this->validate([
            'appName' => ['required', 'string', 'max:128'],
            'defaultBorrowDays' => ['required', 'integer', 'min:1', 'max:365'],
        ], [], ['appName' => 'App name', 'defaultBorrowDays' => 'default borrow days']);

        Setting::put('general', [
            'app_name' => $this->appName,
            'default_borrow_days' => (int) $this->defaultBorrowDays,
        ], auth()->id());
        $this->dispatch('saved');
    }

    public function saveBorrowWorkflow(): void
    {
        abort_unless(auth()->user()->can('settings.edit'), 403);
        $this->validate([
            'borrowAcknowledge' => ['required', 'in:off,optional,required'],
            'borrowApprove' => ['required', 'in:off,required'],
        ]);
        $wf = Setting::get('workflow', []);
        $borrow = $wf['borrow'] ?? [];
        $borrow['acknowledge'] = $this->borrowAcknowledge;
        $borrow['approve'] = $this->borrowApprove;
        $borrow['partial_return'] = $this->borrowPartialReturn ? 'on' : 'off';
        $wf['borrow'] = $borrow;
        Setting::put('workflow', $wf, auth()->id());
        $this->dispatch('saved');
    }

    public function saveModules(): void
    {
        abort_unless(auth()->user()->can('settings.edit'), 403);
        $out = [];
        foreach (\App\Support\Modules::TOGGLEABLE as $mk) {
            $out[$mk] = (bool) ($this->modules[$mk] ?? true);
        }
        Setting::put('modules', $out, auth()->id());
        $this->dispatch('saved');
    }

    public function saveCurrency(): void
    {
        abort_unless(auth()->user()->can('settings.edit'), 403);
        $this->validate([
            'curPrimary' => ['required', 'string', 'max:8'],
            'curSecondary' => ['required', 'string', 'max:8'],
            'exchangeRate' => ['required', 'numeric', 'min:0'],
        ], [], ['exchangeRate' => 'exchange rate']);

        Setting::put('currency', [
            'primary' => strtoupper($this->curPrimary),
            'secondary' => strtoupper($this->curSecondary),
            'exchange_rate' => (float) $this->exchangeRate,
            'secondary_enabled' => $this->curSecondaryEnabled,
        ], auth()->id());
        $this->dispatch('saved');
    }

    public function saveLetterhead(): void
    {
        abort_unless(auth()->user()->can('settings.edit'), 403);
        $this->validate([
            'lhCompanyLo' => ['nullable', 'string', 'max:256'],
            'lhCompanyEn' => ['nullable', 'string', 'max:256'],
            'lhAddress1' => ['nullable', 'string', 'max:256'],
            'lhAddress2' => ['nullable', 'string', 'max:256'],
            'lhAddress3' => ['nullable', 'string', 'max:256'],
            'lhPhone' => ['nullable', 'string', 'max:128'],
            'lhFax' => ['nullable', 'string', 'max:128'],
            'lhEmail' => ['nullable', 'string', 'max:128'],
            'lhWebsite' => ['nullable', 'string', 'max:128'],
            'lhFooter' => ['nullable', 'string', 'max:256'],
            'lhLogo' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
        ]);

        if ($this->lhLogo) {
            if ($this->lhLogoPath) {
                Storage::disk('public')->delete($this->lhLogoPath);
            }
            $this->lhLogoPath = $this->lhLogo->store('letterhead', 'public');
            $this->lhLogo = null;
        }

        Setting::put('letterhead', [
            'company_name' => $this->lhCompanyLo ?: null,
            'company_name_en' => $this->lhCompanyEn ?: null,
            'address1' => $this->lhAddress1 ?: null,
            'address2' => $this->lhAddress2 ?: null,
            'address3' => $this->lhAddress3 ?: null,
            'phone' => $this->lhPhone ?: null,
            'fax' => $this->lhFax ?: null,
            'email' => $this->lhEmail ?: null,
            'website' => $this->lhWebsite ?: null,
            'footer_note' => $this->lhFooter ?: null,
            'logo_path' => $this->lhLogoPath,
        ], auth()->id());
        $this->dispatch('saved');
    }

    public function removeLogo(): void
    {
        abort_unless(auth()->user()->can('settings.edit'), 403);
        if ($this->lhLogoPath) {
            Storage::disk('public')->delete($this->lhLogoPath);
            $this->lhLogoPath = null;
            $lh = Setting::get('letterhead', []);
            $lh['logo_path'] = null;
            Setting::put('letterhead', $lh, auth()->id());
        }
    }

    public function saveRequestFields(): void
    {
        abort_unless(auth()->user()->can('settings.edit'), 403);
        $fields = [];
        foreach (RequestService::FIELD_KEYS as $k) {
            $fields[$k] = (bool) ($this->reqFields[$k] ?? false);
        }
        Setting::put('request', ['fields' => $fields], auth()->id());
        $this->dispatch('saved');
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can('settings.edit'), 403);
        $this->validate(['vat_rate' => ['required', 'numeric', 'min:0', 'max:100']], [], ['vat_rate' => 'VAT rate']);

        Setting::put('vat', ['rate' => (float) $this->vat_rate, 'enabled' => $this->vat_enabled], auth()->id());
        $this->dispatch('saved');
    }

    public function render(): View
    {
        return view('livewire.settings.system');
    }
}
