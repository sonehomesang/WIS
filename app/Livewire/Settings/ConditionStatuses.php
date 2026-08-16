<?php

namespace App\Livewire\Settings;

use App\Models\ConditionStatus as Status;
use App\Support\ConditionStatus as Catalog;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ConditionStatuses extends Component
{
    public bool $showModal = false;

    public ?int $editingId = null;

    public string $key = '';

    public string $label = '';

    public string $color = 'gray';

    public bool $is_disposable = false;

    public bool $is_active = true;

    public int $sort_order = 0;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('settings.view'), 403);
    }

    public function newItem(): void
    {
        $this->reset(['editingId', 'key', 'label', 'is_disposable']);
        $this->color = 'gray';
        $this->is_active = true;
        $this->sort_order = (int) Status::max('sort_order') + 1;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function editItem(int $id): void
    {
        $m = Status::findOrFail($id);
        $this->editingId = $m->id;
        $this->key = $m->key;
        $this->label = $m->label;
        $this->color = $m->color;
        $this->is_disposable = (bool) $m->is_disposable;
        $this->is_active = (bool) $m->is_active;
        $this->sort_order = (int) $m->sort_order;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can('settings.edit'), 403);

        // key is immutable after creation (item rows store it) — only validate on create
        $keyRules = $this->editingId
            ? []
            : ['required', 'string', 'max:40', 'regex:/^[a-z][a-z0-9_]*$/', Rule::unique('condition_statuses', 'key')];

        $data = $this->validate([
            'key' => $keyRules,
            'label' => ['required', 'string', 'max:128'],
            'color' => ['required', Rule::in(Catalog::colorNames())],
            'is_disposable' => ['boolean'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0', 'max:999'],
        ], [], ['key' => 'ລະຫັດ (key)', 'label' => 'ຊື່', 'color' => 'ສີ']);

        if ($this->editingId) {
            Status::findOrFail($this->editingId)->update([
                'label' => $data['label'], 'color' => $data['color'],
                'is_disposable' => $this->is_disposable, 'is_active' => $this->is_active,
                'sort_order' => $data['sort_order'],
            ]);
        } else {
            Status::create([
                'key' => Str::of($data['key'])->lower(), 'label' => $data['label'], 'color' => $data['color'],
                'is_disposable' => $this->is_disposable, 'is_active' => $this->is_active,
                'sort_order' => $data['sort_order'],
            ]);
        }

        $this->showModal = false;
        $this->dispatch('saved');
    }

    public function toggle(int $id): void
    {
        abort_unless(auth()->user()->can('settings.edit'), 403);
        $m = Status::findOrFail($id);
        $m->update(['is_active' => ! $m->is_active]);
    }

    public function render(): View
    {
        return view('livewire.settings.condition-statuses', [
            'items' => Status::orderBy('sort_order')->orderBy('id')->get(),
            'colors' => Catalog::COLORS,
        ]);
    }
}
