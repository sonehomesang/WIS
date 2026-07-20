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

    public string $lang = 'lo';        // ພາສາ ທີ່ ສົ່ງ ອອກ (ຜູ້ໃຊ້ ໄດ້ ຮັບ)

    public string $editLang = 'lo';    // ພາສາ ທີ່ ກຳລັງ ແກ້ ໃນ ໜ້າ ນີ້ (UI ເທົ່ານັ້ນ)

    /** email.set_password fields per language: ['lo'=>[...], 'en'=>[...]]. */
    public array $email = ['lo' => [], 'en' => []];

    /**
     * In-app templates, indexed (NOT keyed by dotted key) so Livewire dot-binding
     * doesn't mis-nest "request.approve".
     *
     * @var array<int,array<string,mixed>>
     */
    public array $templates = [];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('settings.view'), 403);

        $flags = Setting::get('notifications', []);
        $this->enabled = (bool) ($flags['enabled'] ?? true);
        $this->borrowReminder = (bool) ($flags['borrow_reminder'] ?? true);
        $lang = $flags['lang'] ?? 'lo';
        $this->lang = in_array($lang, ['lo', 'en'], true) ? $lang : 'lo';

        $stored = Setting::get('notification_templates', []);

        // email.set_password — prefill effective (stored ?: default) per language
        $defEmail = NotificationService::EMAILS['email.set_password'];
        $se = $stored['email.set_password'] ?? [];
        foreach (['lo', 'en'] as $L) {
            foreach ($defEmail[$L] as $f => $dv) {
                $this->email[$L][$f] = (string) ($se[$L][$f] ?? $dv);
            }
        }

        // in-app templates (grouped by module in TEMPLATES order)
        foreach (NotificationService::TEMPLATES as $key => $def) {
            $s = $stored[$key] ?? [];
            if (! isset($s['lo']) && ! isset($s['en']) && (isset($s['title']) || isset($s['message']))) {
                $s = ['lo' => ['title' => $s['title'] ?? '', 'message' => $s['message'] ?? '']];   // legacy flat → lo
            }
            $this->templates[] = [
                'key' => $key,
                'module' => explode('.', $key)[0],
                'enabled' => (bool) ($stored[$key]['enabled'] ?? true),
                'lo' => [
                    'title' => (string) ($s['lo']['title'] ?? $def['title']),
                    'message' => (string) ($s['lo']['message'] ?? $def['message']),
                ],
                'en' => [
                    'title' => (string) ($s['en']['title'] ?? ''),
                    'message' => (string) ($s['en']['message'] ?? ''),
                ],
            ];
        }
    }

    public function saveFlags(): void
    {
        abort_unless(auth()->user()->can('settings.edit'), 403);
        Setting::put('notifications', [
            'enabled' => $this->enabled,
            'borrow_reminder' => $this->borrowReminder,
            'lang' => in_array($this->lang, ['lo', 'en'], true) ? $this->lang : 'lo',
        ], auth()->id());
        $this->dispatch('saved');
    }

    public function saveEmail(): void
    {
        abort_unless(auth()->user()->can('settings.edit'), 403);
        $stored = Setting::get('notification_templates', []);
        $clean = ['enabled' => true, 'lo' => [], 'en' => []];   // email ບໍ່ ໃຫ້ ປິດ
        foreach (['lo', 'en'] as $L) {
            foreach (array_keys(NotificationService::EMAILS['email.set_password'][$L]) as $f) {
                $clean[$L][$f] = mb_substr(trim((string) ($this->email[$L][$f] ?? '')), 0, 1000);
            }
        }
        $stored['email.set_password'] = $clean;
        Setting::put('notification_templates', $stored, auth()->id());
        $this->dispatch('saved');
    }

    public function saveTemplates(): void
    {
        abort_unless(auth()->user()->can('settings.edit'), 403);
        $stored = Setting::get('notification_templates', []);
        foreach ($this->templates as $t) {
            $key = $t['key'] ?? null;
            if (! $key || ! isset(NotificationService::TEMPLATES[$key])) {
                continue;
            }
            $stored[$key] = [
                'enabled' => (bool) ($t['enabled'] ?? true),
                'lo' => [
                    'title' => mb_substr(trim((string) ($t['lo']['title'] ?? '')), 0, 256),
                    'message' => mb_substr(trim((string) ($t['lo']['message'] ?? '')), 0, 500),
                ],
                'en' => [
                    'title' => mb_substr(trim((string) ($t['en']['title'] ?? '')), 0, 256),
                    'message' => mb_substr(trim((string) ($t['en']['message'] ?? '')), 0, 500),
                ],
            ];
        }
        Setting::put('notification_templates', $stored, auth()->id());
        $this->dispatch('saved');
    }

    public function resetTemplate(int $i): void
    {
        abort_unless(auth()->user()->can('settings.edit'), 403);
        $key = $this->templates[$i]['key'] ?? null;
        if ($key && isset(NotificationService::TEMPLATES[$key])) {
            $this->templates[$i]['lo']['title'] = NotificationService::TEMPLATES[$key]['title'];
            $this->templates[$i]['lo']['message'] = NotificationService::TEMPLATES[$key]['message'];
            $this->templates[$i]['en']['title'] = '';
            $this->templates[$i]['en']['message'] = '';
        }
    }

    public function resetEmail(): void
    {
        abort_unless(auth()->user()->can('settings.edit'), 403);
        foreach (NotificationService::EMAILS['email.set_password'] as $L => $fields) {
            foreach ($fields as $f => $dv) {
                $this->email[$L][$f] = $dv;
            }
        }
    }

    public function render(): View
    {
        return view('livewire.settings.notifications');
    }
}
