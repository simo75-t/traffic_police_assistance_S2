<?php

namespace App\Http\Services\Notifications;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class FcmNotificationService
{
    public function sendToUser(User $user, string $title, string $body, array $data = []): bool
    {
        if (! $this->isEnabled() || empty($user->fcm_token)) {
            return false;
        }

        $projectId = (string) config('services.firebase.project_id');
        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        $payload = [
            'message' => [
                'token' => $user->fcm_token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => $this->normalizeData($data),
                'android' => [
                    'priority' => 'high',
                    'notification' => [
                        'sound' => 'default',
                    ],
                ],
                'apns' => [
                    'headers' => [
                        'apns-priority' => '10',
                    ],
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                        ],
                    ],
                ],
            ],
        ];

        try {
            $response = Http::withToken($this->getAccessToken())
                ->acceptJson()
                ->post($url, $payload);

            if ($response->successful()) {
                return true;
            }

            Log::warning('FCM notification failed.', [
                'user_id' => $user->id,
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ]);
        } catch (\Throwable $exception) {
            Log::error('FCM notification exception.', [
                'user_id' => $user->id,
                'message' => $exception->getMessage(),
            ]);
        }

        return false;
    }

    public function isEnabled(): bool
    {
        return filled(config('services.firebase.project_id'))
            && filled(config('services.firebase.client_email'))
            && filled(config('services.firebase.private_key'));
    }

    private function getAccessToken(): string
    {
        return Cache::remember('firebase_access_token', now()->addMinutes(50), function () {
            $clientEmail = (string) config('services.firebase.client_email');
            $privateKey = str_replace('\n', "\n", (string) config('services.firebase.private_key'));
            $tokenUri = (string) config('services.firebase.token_uri', 'https://oauth2.googleapis.com/token');
            $issuedAt = time();

            $header = $this->base64UrlEncode(json_encode([
                'alg' => 'RS256',
                'typ' => 'JWT',
            ]));

            $payload = $this->base64UrlEncode(json_encode([
                'iss' => $clientEmail,
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => $tokenUri,
                'iat' => $issuedAt,
                'exp' => $issuedAt + 3600,
            ]));

            $unsignedToken = "{$header}.{$payload}";
            $signature = '';

            $privateKeyResource = openssl_pkey_get_private($privateKey);

            if (! $privateKeyResource) {
                throw new RuntimeException('Invalid Firebase private key.');
            }

            openssl_sign($unsignedToken, $signature, $privateKeyResource, OPENSSL_ALGO_SHA256);

            $jwt = $unsignedToken . '.' . $this->base64UrlEncode($signature);

            $response = Http::asForm()->post($tokenUri, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if (! $response->successful() || empty($response->json('access_token'))) {
                throw new RuntimeException('Unable to obtain Firebase access token.');
            }

            return (string) $response->json('access_token');
        });
    }

    private function normalizeData(array $data): array
    {
        $normalized = [];

        foreach ($data as $key => $value) {
            $normalized[(string) $key] = is_scalar($value)
                ? (string) $value
                : json_encode($value);
        }

        return $normalized;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
