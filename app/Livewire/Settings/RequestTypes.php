<?php

namespace App\Livewire\Settings;

use App\Models\RequestType as Type;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class RequestTypes extends Component
{
    public bool $showModal = false;

    public ?int $editingId = null;

    public string $key = '';

    public string $label = '';

    public bool $is_active = true;

    public int $sort_order = 0;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('settings.view'), 403);
    }

    public function newItem(): void
    {
        $this->reset(['editingId', 'key', 'label']);
        $this->is_active = true;
        $this->sort_order = (int) Type::max('sort_order') + 1;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function editItem(int $id): void
    {
        $m = Type::findOrFail($id);
        $this->editingId = $m->id;
        $this->key = $m->key;
        $this->label = $m->label;
        $this->is_active = (bool) $m->is_active;
        $this->sort_order = (int) $m->sort_order;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can('settings.edit'), 403);

        // key is immutable after creation (request rows store it) — only validate on create
        $keyRules = $this->editingId
            ? []
            : ['required', 'string', 'max:40', 'regex:/^[A-Za-z][A-Za-z0-9_]*$/', Rule::unique('request_types', 'key')];

        $data = $this->validate([
            'key' => $keyRules,
            'label' => ['required', 'string', 'max:128'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0', 'max:999'],
        ], [], ['key' => 'ລະຫັດ (key)', 'label' => 'ຊື່']);

        if ($this->editingId) {
            Type::findOrFail($this->editingId)->update([
                'label' => $data['label'], 'is_active' => $this->is_active, 'sort_order' => $data['sort_order'],
            ]);
        } else {
            Type::create([
                'key' => $data['key'], 'label' => $data['label'],
                'is_active' => $this->is_active, 'sort_order' => $data['sort_order'],
            ]);
        }

        $this->showModal = false;
        $this->dispatch('saved');
    }

    public function toggle(int $id): void
    {
        abort_unless(auth()->user()->can('settings.edit'), 403);
        $m = Type::findOrFail($id);
        $m->update(['is_active' => ! $m->is_active]);
    }

    public function render(): View
    {
        return view('livewire.settings.request-types', [
            'items' => Type::orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }
}
