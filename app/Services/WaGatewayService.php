<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class WaGatewayService
{
    protected string $wapiUrl;

    public function __construct()
    {
        // Default assuming Node WAPI runs on localhost:3000
        $this->wapiUrl = config('services.wapi.url', 'http://127.0.0.1:3000');
    }

    /**
     * Send a WhatsApp message to a specific number.
     * 
     * @param string $to Phone number (e.g. '6281234567890')
     * @param string $message The text message
     * @return bool
     */
    public function sendMessage(string $to, string $message): bool
    {
        try {
            $response = Http::timeout(5)
                ->connectTimeout(2)
                ->post("{$this->wapiUrl}/kirim-pesan", [
                    'token' => 'tokenwapi',
                    'number' => $to,
                    'message' => $message,
                ]);

            if ($response->successful()) {
                $status = $response->json('status');
                return $status === true || $status === 'true' || $status == 1;
            }
        } catch (Throwable $e) {
            Log::error('WAPI sendMessage failed: ' . $e->getMessage());
        }
        
        return false;
    }

    /**
     * Fetch the QR code for a session from the Node app.
     * 
     * @param string $sessionId
     * @return array|null Returns ['qr' => 'base64...', 'status' => '...'] or null on error
     */
    public function getSessionQr(string $sessionId): ?array
    {
        try {
            $response = Http::timeout(5)->connectTimeout(2)->post("{$this->wapiUrl}/get-qr", [
                'token' => 'tokenwapi'
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                
                // WAPI Node app.js returns:
                // status "3": Connected
                // status "1": QR Code available
                if (($data['status'] ?? '') === '3') {
                    return ['status' => 'CONNECTED'];
                }
                
                if (isset($data['response']) && is_string($data['response'])) {
                    return [
                        'qr' => $data['response'],
                        'status' => 'QR_READY'
                    ];
                }
            }
        } catch (Throwable $e) {
            Log::error('WAPI getSessionQr failed: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Fetch session status from Node.js gateway.
     *
     * @param string $sessionId
     * @return array<string, mixed>|null
     */
    public function getSessionStatus(string $sessionId): ?array
    {
        // Because the custom node script does not have a separate status endpoint
        // we can utilize get-qr which returns status 3 if connected.
        return $this->getSessionQr($sessionId);
    }
    /**
     * Disconnect / logout the WhatsApp session.
     */
    public function logout(): bool
    {
        try {
            $response = Http::timeout(5)
                ->connectTimeout(2)
                ->post("{$this->wapiUrl}/logout", [
                    'token' => 'tokenwapi',
                ]);

            return $response->successful() && ($response->json('status') === true);
        } catch (Throwable $e) {
            Log::error('WAPI logout failed: ' . $e->getMessage());
        }

        return false;
    }
}
