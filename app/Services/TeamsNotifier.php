<?php

namespace App\Services;

use App\Support\TeamsSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Posts workflow notifications to Microsoft Teams via an Incoming Webhook.
 * Fire-and-forget: deferred until after the response so a slow/blocked webhook
 * never delays the user's action, and any failure is logged, never thrown.
 */
class TeamsNotifier
{
    /**
     * Dispatch a Teams post for a notification event key (e.g. "borrow.approve").
     * No-op unless Teams is on and that module has a webhook resolved.
     */
    public function dispatch(string $key, string $title, ?string $body, ?string $link = null): void
    {
        $url = TeamsSettings::webhookFor(explode('.', $key)[0]);
        if (! $url) {
            return;
        }
        $full = $this->fullLink($link);
        $send = fn () => $this->post($url, $title, $body, $full);

        // Sync under tests (so Http::fake can assert); deferred in production.
        app()->runningUnitTests() ? $send() : defer($send);
    }

    /** POST an Office365-connector MessageCard to the webhook. Returns success. */
    public function post(string $url, string $title, ?string $body, ?string $link): bool
    {
        try {
            $resp = Http::timeout(6)->asJson()->post($url, $this->card($title, $body, $link));
            if (! $resp->successful()) {
                Log::warning('Teams webhook non-2xx', ['status' => $resp->status()]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('Teams webhook error: '.$e->getMessage());

            return false;
        }
    }

    /** Send a test message to a webhook (Settings button). */
    public function testWebhook(string $url): array
    {
        $ok = $this->post(
            $url,
            'WH — ທົດສອບ Teams',
            'ຖ້າ ເຫັນ ຂໍ້ຄວາມ ນີ້ ໃນ Teams ໝາຍ ຄວາມ ວ່າ ຕັ້ງ ຄ່າ webhook ຖືກຕ້ອງ ✅',
            rtrim((string) config('app.url'), '/'),
        );

        return [
            'ok' => $ok,
            'message' => $ok ? 'ສົ່ງ ຂໍ້ຄວາມ ທົດສອບ ໄປ Teams ແລ້ວ — ກະລຸນາ ເຊັກ channel.' : 'ສົ່ງ ບໍ່ ສຳເລັດ — ກວດ webhook URL ຄືນ.',
        ];
    }

    /** Adaptive/connector card body. */
    protected function card(string $title, ?string $body, ?string $link): array
    {
        $card = [
            '@type' => 'MessageCard',
            '@context' => 'https://schema.org/extensions',
            'themeColor' => '0EA5E9',
            'summary' => mb_substr($title, 0, 120),
            'title' => $title,
            'text' => (string) $body,
        ];
        if ($link) {
            $card['potentialAction'] = [[
                '@type' => 'OpenUri',
                'name' => 'ເປີດ ໃນ WH',
                'targets' => [['os' => 'default', 'uri' => $link]],
            ]];
        }

        return $card;
    }

    /** Absolutise a relative WH link for the card button. */
    protected function fullLink(?string $link): ?string
    {
        if (! $link) {
            return null;
        }

        return str_starts_with($link, 'http') ? $link : rtrim((string) config('app.url'), '/').'/'.ltrim($link, '/');
    }
}
