<?php

namespace App\Livewire\Settings;

use App\Models\Setting;
use App\Services\RequestService;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class System extends Component
{
    public $vat_rate = 10;

    public bool $vat_enabled = true;

    /** @var array<string,bool> Request form fields ເປີດ/ປິດ */
    public array $reqFields = [];

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
