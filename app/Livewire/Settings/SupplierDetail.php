<?php

namespace App\Livewire\Settings;

use App\Models\Supplier;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class SupplierDetail extends Component
{
    public Supplier $supplier;

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $contract_number = '';

    public ?string $sign_date = null;

    public ?string $effective_date = null;

    public ?string $expiry_date = null;

    public ?string $renewal_date = null;

    public string $status = 'draft';

    public string $notes = '';

    public function mount(Supplier $supplier): void
    {
        abort_unless(auth()->user()->can('supplier.view'), 403);
        $this->supplier = $supplier;
    }

    public function newContract(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function editContract(int $id): void
    {
        $c = $this->supplier->contracts()->findOrFail($id);
        $this->editingId = $c->id;
        $this->contract_number = $c->contract_number;
        $this->sign_date = $c->sign_date?->toDateString();
        $this->effective_date = $c->effective_date?->toDateString();
        $this->expiry_date = $c->expiry_date?->toDateString();
        $this->renewal_date = $c->renewal_date?->toDateString();
        $this->status = $c->status;
        $this->notes = $c->notes ?? '';
        $this->resetValidation();
        $this->showModal = true;
    }

    public function saveContract(): void
    {
        abort_unless(auth()->user()->can('supplier.'.($this->editingId ? 'edit' : 'create')), 403);

        $rules = [
            'contract_number' => ['required', 'string', 'max:64'],
            'sign_date' => ['nullable', 'date'],
            'effective_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date'],
            'renewal_date' => ['nullable', 'date'],
            'status' => ['required', 'in:draft,active,expired,superseded,cancelled'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
        if ($this->effective_date && $this->expiry_date) {
            $rules['expiry_date'][] = 'after_or_equal:effective_date';
        }
        $data = $this->validate($rules, [], ['contract_number' => 'contract number']);

        $uid = auth()->id();
        $payload = [
            'contract_number' => $data['contract_number'],
            'sign_date' => $data['sign_date'] ?: null,
            'effective_date' => $data['effective_date'] ?: null,
            'expiry_date' => $data['expiry_date'] ?: null,
            'renewal_date' => $data['renewal_date'] ?: null,
            'status' => $data['status'],
            'notes' => $data['notes'] ?: null,
            'updated_by' => $uid,
        ];

        if ($this->editingId) {
            $this->supplier->contracts()->findOrFail($this->editingId)->update($payload);
        } else {
            $payload['created_by'] = $uid;
            $this->supplier->contracts()->create($payload);
        }

        $this->showModal = false;
        $this->dispatch('saved');
    }

    public function deleteContract(int $id): void
    {
        abort_unless(auth()->user()->can('supplier.delete'), 403);
        $this->supplier->contracts()->findOrFail($id)->delete();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->contract_number = '';
        $this->sign_date = null;
        $this->effective_date = null;
        $this->expiry_date = null;
        $this->renewal_date = null;
        $this->status = 'draft';
        $this->notes = '';
        $this->resetValidation();
    }

    public function render(): View
    {
        return view('livewire.settings.supplier-detail', [
            'contracts' => $this->supplier->contracts()->orderByDesc('effective_date')->orderByDesc('id')->get(),
            'vat' => $this->supplier->resolveVat(),
            'vatChanges' => $this->supplier->vatChanges()->with('changedBy')->limit(20)->get(),
        ]);
    }
}
