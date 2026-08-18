<?php

namespace App\Services;

use App\Models\PushSubscription;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class PushService
{
    private ?WebPush $webPush = null;

    private function driver(): WebPush
    {
        if ($this->webPush) return $this->webPush;

        $this->webPush = new WebPush([
            'VAPID' => [
                'subject' => config('webpush.subject'),
                'publicKey' => config('webpush.public_key'),
                'privateKey' => config('webpush.private_key'),
            ],
        ]);
        $this->webPush->setDefaultOptions([
            'TTL' => 60 * 60 * 24,
            'urgency' => 'normal',
        ]);

        return $this->webPush;
    }

    /**
     * Send a push notification to all subscriptions of a user.
     */
    public function sendToUser(int $userId, string $title, string $body, ?string $url = '/'): void
    {
        $subs = PushSubscription::where('id_user', $userId)->get();
        if ($subs->isEmpty()) return;

        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'url' => $url,
        ]);

        $driver = $this->driver();
        foreach ($subs as $sub) {
            try {
                $driver->queueNotification(
                    Subscription::create([
                        'endpoint' => $sub->endpoint,
                        'publicKey' => $sub->p256dh,
                        'authToken' => $sub->auth,
                    ]),
                    $payload,
                );
            } catch (\Throwable $e) {
                Log::warning('Push queue failed', ['user' => $userId, 'err' => $e->getMessage()]);
            }
        }

        foreach ($driver->flush() as $report) {
            if (! $report->isSuccess()) {
                $code = $report->getResponse()?->getStatusCode();
                if ($code === 404 || $code === 410) {
                    PushSubscription::where('id_user', $userId)
                        ->where('endpoint', $report->getRequest()->getUri()->__toString())
                        ->delete();
                }
            }
        }
    }
}
