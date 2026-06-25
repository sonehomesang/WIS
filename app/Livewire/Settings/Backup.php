<?php

namespace App\Livewire\Settings;

use App\Services\BackupService;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

#[Layout('layouts.app')]
class Backup extends Component
{
    public string $error = '';

    /** restore confirmation — must type RESTORE + pick a file. */
    public string $restoreTarget = '';

    public string $confirmRestore = '';

    public bool $busy = false;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('settings.view'), 403);
    }

    protected function canManage(): bool
    {
        return auth()->user()->can('settings.edit');
    }

    public function createBackup(): void
    {
        abort_unless($this->canManage(), 403);
        $this->error = '';
        try {
            $name = app(BackupService::class)->create();
            session()->flash('ok', "✓ ສ້າງ backup: {$name}");
        } catch (\Throwable $e) {
            $this->error = $e->getMessage();
        }
    }

    public function download(string $name): ?BinaryFileResponse
    {
        abort_unless($this->canManage(), 403);
        $path = app(BackupService::class)->path($name);
        abort_unless($path, 404);

        return response()->download($path);
    }

    public function deleteBackup(string $name): void
    {
        abort_unless($this->canManage(), 403);
        app(BackupService::class)->delete($name);
        session()->flash('ok', '✓ ລຶບ backup ແລ້ວ');
    }

    public function restore(): void
    {
        // Destructive — super_admin only + typed confirmation.
        abort_unless(auth()->user()->is_super_admin, 403);
        $this->error = '';
        if ($this->confirmRestore !== 'RESTORE' || $this->restoreTarget === '') {
            $this->error = 'ພິມ RESTORE + ເລືອກ ໄຟລ໌ ກ່ອນ.';

            return;
        }
        try {
            app(BackupService::class)->restore($this->restoreTarget);
            $this->reset(['restoreTarget', 'confirmRestore']);
            session()->flash('ok', '✓ Restore ສຳເລັດ — ກວດຂໍ້ມູນ ຄືນ.');
        } catch (\Throwable $e) {
            $this->error = $e->getMessage();
        }
    }

    public function render(): View
    {
        return view('livewire.settings.backup', [
            'backups' => app(BackupService::class)->list(),
            'canManage' => $this->canManage(),
            'isSuperAdmin' => auth()->user()->is_super_admin,
        ]);
    }
}
