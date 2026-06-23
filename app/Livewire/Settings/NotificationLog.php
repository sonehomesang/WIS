<?php

namespace App\Livewire\Settings;

use App\Models\Notification;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.app')]
class NotificationLog extends Component
{
    use WithPagination;

    public string $search = '';

    public string $typeFilter = '';

    public string $readFilter = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('settings.view'), 403);
    }

    public function updating($name): void
    {
        if (in_array($name, ['search', 'typeFilter', 'readFilter'], true)) {
            $this->resetPage();
        }
    }

    protected function baseQuery()
    {
        return Notification::query()
            ->leftJoin('users', 'users.id', '=', 'notifications.user_id')
            ->when($this->search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('notifications.title', 'like', "%{$this->search}%")
                ->orWhere('notifications.message', 'like', "%{$this->search}%")
                ->orWhere('users.display_name', 'like', "%{$this->search}%")))
            ->when($this->typeFilter !== '', fn ($q) => $q->where('notifications.type', $this->typeFilter))
            ->when($this->readFilter === 'unread', fn ($q) => $q->whereNull('notifications.read_at'))
            ->when($this->readFilter === 'read', fn ($q) => $q->whereNotNull('notifications.read_at'))
            ->select('notifications.*', 'users.display_name as recipient')
            ->orderByDesc('notifications.id');
    }

    public function export(): StreamedResponse
    {
        abort_unless(auth()->user()->can('settings.view'), 403);
        $rows = $this->baseQuery()->limit(5000)->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
            fputcsv($out, ['ID', 'Recipient', 'Type', 'Title', 'Message', 'Read at', 'Created at']);
            foreach ($rows as $r) {
                fputcsv($out, [$r->id, $r->recipient, $r->type, $r->title, $r->message, $r->read_at, $r->created_at]);
            }
            fclose($out);
        }, 'notifications-'.now()->format('Ymd-Hi').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function render(): View
    {
        return view('livewire.settings.notification-log', [
            'rows' => $this->baseQuery()->paginate(20),
        ]);
    }
}
