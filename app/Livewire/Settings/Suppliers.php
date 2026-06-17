<?php

namespace App\Livewire\Settings;

use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Suppliers extends Component
{
    public string $search = '';

    public bool $showModal = false;
    public ?int $editingId = null;
    public string $name = '';
    public string $name_en = '';
    public string $contact_person = '';
    public string $contact_phone = '';
    public string $contact_email = '';
    public string $address = '';
    public string $tax_id = '';
    public string $payment_terms = '';
    public string $default_currency = 'LAK';
    public string $notes = '';
    public bool $is_active = true;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('supplier.view'), 403);
    }

    public function newItem(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function editItem(int $id): void
    {
        $m = Supplier::findOrFail($id);
        $this->editingId = $m->id;
        $this->name = $m->name;
        $this->name_en = $m->name_en ?? '';
        $this->contact_person = $m->contact_person ?? '';
        $this->contact_phone = $m->contact_phone ?? '';
        $this->contact_email = $m->contact_email ?? '';
        $this->address = $m->address ?? '';
        $this->tax_id = $m->tax_id ?? '';
        $this->payment_terms = $m->payment_terms ?? '';
        $this->default_currency = $m->default_currency;
        $this->notes = $m->notes ?? '';
        $this->is_active = (bool) $m->is_active;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can('supplier.'.($this->editingId ? 'edit' : 'create')), 403);

        $data = $this->validate([
            'name' => ['required', 'string', 'max:256', Rule::unique('suppliers', 'name')->whereNull('deleted_at')->ignore($this->editingId)],
            'name_en' => ['nullable', 'string', 'max:256'],
            'contact_person' => ['nullable', 'string', 'max:256'],
            'contact_phone' => ['nullable', 'string', 'max:64'],
            'contact_email' => ['nullable', 'email', 'max:256'],
            'address' => ['nullable', 'string', 'max:2000'],
            'tax_id' => ['nullable', 'string', 'max:64'],
            'payment_terms' => ['nullable', 'string', 'max:128'],
            'default_currency' => ['required', 'in:LAK,THB,USD'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['boolean'],
        ], [], ['name' => 'ຊື່']);

        $uid = auth()->id();
        $payload = collect($data)->map(fn ($v) => $v === '' ? null : $v)->toArray();
        $payload['is_active'] = $this->is_active;
        $payload['updated_by'] = $uid;

        if ($this->editingId) {
            Supplier::findOrFail($this->editingId)->update($payload);
        } else {
            $payload['slug'] = $this->uniqueSlug($data['name']);
            $payload['created_by'] = $uid;
            Supplier::create($payload);
        }

        $this->showModal = false;
        $this->dispatch('saved');
    }

    public function toggle(int $id): void
    {
        $m = Supplier::findOrFail($id);
        abort_unless(auth()->user()->can('supplier.'.($m->is_active ? 'deactivate' : 'activate')), 403);
        $m->update(['is_active' => ! $m->is_active, 'updated_by' => auth()->id()]);
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()->can('supplier.delete'), 403);
        Supplier::findOrFail($id)->delete();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->name_en = '';
        $this->contact_person = '';
        $this->contact_phone = '';
        $this->contact_email = '';
        $this->address = '';
        $this->tax_id = '';
        $this->payment_terms = '';
        $this->default_currency = 'LAK';
        $this->notes = '';
        $this->is_active = true;
        $this->resetValidation();
    }

    protected function uniqueSlug(string $base): string
    {
        $slug = Str::slug($base) ?: 'supplier';
        $original = $slug;
        $i = 2;
        while (DB::table('suppliers')->where('slug', $slug)->exists()) {
            $slug = $original.'-'.$i++;
        }

        return $slug;
    }

    public function render(): View
    {
        $items = Supplier::when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")
            ->orWhere('name_en', 'like', "%{$this->search}%")
            ->orWhere('contact_person', 'like', "%{$this->search}%"))
            ->orderBy('name')->get();

        return view('livewire.settings.suppliers', ['items' => $items]);
    }
}
