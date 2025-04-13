<?php

namespace App\Services;

use App\Models\DeliveryNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FirebaseService
{
    protected $serverKey;
    protected $projectId;
    protected $fcmUrl = 'https://fcm.googleapis.com/fcm/send';

    public function __construct()
    {
        $this->serverKey = config('services.firebase.server_key');
        $this->projectId = config('services.firebase.project_id');
    }

    /**
     * Send notification to a specific device
     */
    public function sendNotificationToDevice(string $deviceToken, string $title, string $body, array $data = []): bool
    {
        try {
            $payload = [
                'to' => $deviceToken,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                    'sound' => 'default',
                    'badge' => '1',
                ],
                'data' => $data,
            ];

            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->serverKey,
                'Content-Type' => 'application/json',
            ])->post($this->fcmUrl, $payload);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Firebase notification failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send notification to a topic
     */
    public function sendNotificationToTopic(string $topic, string $title, string $body, array $data = []): bool
    {
        try {
            $payload = [
                'to' => '/topics/' . $topic,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                    'sound' => 'default',
                ],
                'data' => $data,
            ];

            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->serverKey,
                'Content-Type' => 'application/json',
            ])->post($this->fcmUrl, $payload);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Firebase topic notification failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send notification to multiple devices
     */
    public function sendNotificationToDevices(array $deviceTokens, string $title, string $body, array $data = []): bool
    {
        try {
            $payload = [
                'registration_ids' => $deviceTokens,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                    'sound' => 'default',
                    'badge' => '1',
                ],
                'data' => $data,
            ];

            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->serverKey,
                'Content-Type' => 'application/json',
            ])->post($this->fcmUrl, $payload);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Firebase multi-device notification failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Subscribe a device to a topic
     */
    public function subscribeToTopic(string $deviceToken, string $topic): bool
    {
        try {
            $url = "https://iid.googleapis.com/iid/v1/{$deviceToken}/rel/topics/{$topic}";

            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->serverKey,
                'Content-Type' => 'application/json',
            ])->post($url);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Firebase topic subscription failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Unsubscribe a device from a topic
     */
    public function unsubscribeFromTopic(string $deviceToken, string $topic): bool
    {
        try {
            $url = "https://iid.googleapis.com/iid/v1:batchRemove";

            $payload = [
                'to' => '/topics/' . $topic,
                'registration_tokens' => [$deviceToken],
            ];

            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->serverKey,
                'Content-Type' => 'application/json',
            ])->post($url, $payload);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Firebase topic unsubscription failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Process pending notifications
     */
    public function processPendingNotifications(): int
    {
        $count = 0;
        $pendingNotifications = DeliveryNotification::unsent()
            ->where('channel', 'firebase')
            ->get();

        foreach ($pendingNotifications as $notification) {
            $success = false;

            // Get device token(s) for the user
            if ($notification->user_id) {
                $deviceTokens = $this->getDeviceTokensForUser($notification->user_id);

                if (count($deviceTokens) > 0) {
                    $success = $this->sendNotificationToDevices(
                        $deviceTokens,
                        $notification->title,
                        $notification->body,
                        $notification->data ?? []
                    );
                }
            } else {
                // Send to a topic if no specific user (e.g., all drivers)
                $topic = $notification->data['topic'] ?? 'all_drivers';
                $success = $this->sendNotificationToTopic(
                    $topic,
                    $notification->title,
                    $notification->body,
                    $notification->data ?? []
                );
            }

            if ($success) {
                $notification->markAsSent();
                $count++;
            }
        }

        return $count;
    }

    /**
     * Get device tokens for a user
     */
    protected function getDeviceTokensForUser(int $userId): array
    {
        return \App\Models\UserDevice::where('user_id', $userId)
            ->where('active', true)
            ->pluck('device_token')
            ->toArray();
    }
}
