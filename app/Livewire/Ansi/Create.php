<?php

namespace App\Livewire\Ansi;

use App\Models\AnsiApplication;
use App\Models\Uom;
use App\Models\User;
use App\Services\AnsiService;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Create extends Component
{
    use WithFileUploads;

    public ?int $hos_user_id = null;

    public ?int $manager_user_id = null;

    public string $section_team = '';

    public string $phone = '';

    public string $app_date = '';

    public string $sub_assembly = '';

    public string $functional_system = '';

    public string $purpose = '';

    /** @var array<int, array> */
    public array $items = [];

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $files = [];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('ansi.create'), 403);
        $this->app_date = Carbon::today()->toDateString();
        $this->items = [$this->blankItem()];
    }

    protected function blankItem(): array
    {
        return [
            'stock' => false, 'description' => '', 'price_usd' => '', 'qty_order' => 1,
            'unit' => '', 'min_qty' => '', 'max_qty' => '', 'suggested_supplier' => '',
            'hazardous' => false, 'criticality' => false, 'special_storage' => 'Normal',
        ];
    }

    public function addItem(): void
    {
        $this->items[] = $this->blankItem();
    }

    public function removeItem(int $i): void
    {
        unset($this->items[$i]);
        $this->items = array_values($this->items);
        if (empty($this->items)) {
            $this->items = [$this->blankItem()];
        }
    }

    public function removeFile(int $i): void
    {
        unset($this->files[$i]);
        $this->files = array_values($this->files);
    }

    public function save(bool $submit = false): void
    {
        abort_unless(auth()->user()->can('ansi.create'), 403);

        $this->validate([
            'hos_user_id' => [$submit ? 'required' : 'nullable', 'exists:users,id'],
            'manager_user_id' => [$submit ? 'required' : 'nullable', 'exists:users,id'],
            'section_team' => ['nullable', 'string', 'max:128'],
            'phone' => ['nullable', 'string', 'max:64'],
            'app_date' => ['required', 'date'],
            'sub_assembly' => ['nullable', 'string', 'max:128'],
            'functional_system' => ['nullable', 'string', 'max:128'],
            'purpose' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:1000'],
            'items.*.qty_order' => ['required', 'integer', 'min:1'],
            'items.*.price_usd' => ['nullable', 'numeric', 'min:0'],
            'items.*.min_qty' => ['nullable', 'integer', 'min:0'],
            'items.*.max_qty' => ['nullable', 'integer', 'min:0'],
            'items.*.unit' => ['nullable', 'string', 'max:32'],
            'items.*.suggested_supplier' => ['nullable', 'string', 'max:256'],
            'items.*.special_storage' => ['nullable', 'in:Normal,Air Cond room'],
            'files.*' => ['file', 'max:8192', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx'],
        ], [], ['hos_user_id' => 'HoS/TL', 'manager_user_id' => 'Manager']);

        $svc = app(AnsiService::class);
        $app = $svc->createDraft([
            'hos_user_id' => $this->hos_user_id,
            'manager_user_id' => $this->manager_user_id,
            'section_team' => $this->section_team,
            'phone' => $this->phone,
            'app_date' => $this->app_date,
            'sub_assembly' => $this->sub_assembly,
            'functional_system' => $this->functional_system,
            'purpose' => $this->purpose,
            'items' => $this->items,
        ], auth()->user());

        foreach ($this->files as $file) {
            $path = $file->store("ansi/{$app->id}", 'public');
            $app->attachments()->create([
                'path' => $path, 'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(), 'uploaded_by' => auth()->id(), 'created_at' => now(),
            ]);
        }

        if ($submit) {
            $svc->submit($app, auth()->user());
            session()->flash('ok', "✓ Submitted {$app->request_number} for HoS/TL endorsement");
        } else {
            session()->flash('ok', "✓ Saved draft {$app->request_number}");
        }

        $this->redirectRoute('ansi.show', $app, navigate: true);
    }

    public function render(): View
    {
        return view('livewire.ansi.create', [
            'uoms' => Uom::where('is_active', true)->orderBy('name')->get(),
            'people' => User::where('status', 'active')->orderBy('display_name')->get(['id', 'display_name', 'email']),
        ]);
    }
}
