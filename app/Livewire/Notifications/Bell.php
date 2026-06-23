<?php

namespace App\Livewire\Notifications;

use App\Models\Notification;
use Illuminate\View\View;
use Livewire\Component;

class Bell extends Component
{
    public function markRead(int $id)
    {
        $n = Notification::where('user_id', auth()->id())->find($id);
        if ($n) {
            $n->update(['read_at' => now()]);
            if ($n->link) {
                return $this->redirect($n->link, navigate: true);
            }
        }
    }

    public function markAllRead(): void
    {
        Notification::where('user_id', auth()->id())->unread()->update(['read_at' => now()]);
    }

    public function render(): View
    {
        $base = Notification::where('user_id', auth()->id());

        return view('livewire.notifications.bell', [
            'unread' => (clone $base)->unread()->count(),
            'items' => (clone $base)->orderByDesc('id')->limit(12)->get(),
        ]);
    }
}
