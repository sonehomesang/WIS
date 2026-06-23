<?php

namespace App\Livewire\Settings;

use App\Models\Setting;
use App\Services\NotificationService;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Notifications extends Component
{
    public bool $enabled = true;

    public bool $borrowReminder = true;

    /**
     * Indexed list (NOT keyed by the dotted template key) so Livewire's
     * dot-notation wire:model binding doesn't mis-nest "request.approve".
     *
     * @var array<int,array{key:string,title:string,message:string}>
     */
    public array $templates = [];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('settings.view'), 403);
        $flags = Setting::get('notifications', ['enabled' => true, 'borrow_reminder' => true]);
        $this->enabled = (bool) ($flags['enabled'] ?? true);
        $this->borrowReminder = (bool) ($flags['borrow_reminder'] ?? true);

        $custom = Setting::get('notification_templates', []);
        foreach (NotificationService::TEMPLATES as $key => $def) {
            $this->templates[] = [
                'key' => $key,
                'title' => $custom[$key]['title'] ?? $def['title'],
                'message' => $custom[$key]['message'] ?? $def['message'],
            ];
        }
    }

    public function saveFlags(): void
    {
        abort_unless(auth()->user()->can('settings.edit'), 403);
        Setting::put('notifications', ['enabled' => $this->enabled, 'borrow_reminder' => $this->borrowReminder], auth()->id());
        $this->dispatch('saved');
    }

    public function saveTemplates(): void
    {
        abort_unless(auth()->user()->can('settings.edit'), 403);
        $clean = [];
        foreach ($this->templates as $t) {
            if (! isset(NotificationService::TEMPLATES[$t['key']])) {
                continue;
            }
            $clean[$t['key']] = [
                'title' => mb_substr(trim($t['title'] ?? ''), 0, 256),
                'message' => mb_substr(trim($t['message'] ?? ''), 0, 500),
            ];
        }
        Setting::put('notification_templates', $clean, auth()->id());
        $this->dispatch('saved');
    }

    public function resetTemplate(int $i): void
    {
        abort_unless(auth()->user()->can('settings.edit'), 403);
        $key = $this->templates[$i]['key'] ?? null;
        if ($key && isset(NotificationService::TEMPLATES[$key])) {
            $this->templates[$i]['title'] = NotificationService::TEMPLATES[$key]['title'];
            $this->templates[$i]['message'] = NotificationService::TEMPLATES[$key]['message'];
        }
    }

    public function render(): View
    {
        return view('livewire.settings.notifications');
    }
}
