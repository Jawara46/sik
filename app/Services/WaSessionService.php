<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\School;
use App\Models\WaSession;

class WaSessionService
{
    public function __construct(
        private readonly WaGatewayService $waGatewayService,
    ) {
    }

    public function getOrCreateSession(School $school): WaSession
    {
        return WaSession::query()->firstOrCreate(
            ['school_id' => $school->id],
            [
                'session_id' => sprintf('school-%d', $school->id),
                'status' => 'DISCONNECTED',
            ],
        );
    }

    /**
     * @return array{status:string,qr_code:?string,session_id:string}
     */
    public function refreshQr(School $school): array
    {
        $session = $this->getOrCreateSession($school);
        $response = $this->waGatewayService->getSessionQr($session->session_id);

        if ($response === null) {
            $session->update(['status' => 'DISCONNECTED']);
            return [
                'status' => 'DISCONNECTED',
                'qr_code' => $this->toQrDataUri($session->qr_code),
                'session_id' => $session->session_id,
            ];
        }

        $status = $this->normalizeStatus((string) ($response['status'] ?? 'QR_READY'));
        $qrRaw = (string) ($response['qr'] ?? $response['qr_code'] ?? '');
        $qrCode = $qrRaw !== '' ? $qrRaw : $session->qr_code;

        $session->update([
            'status' => $status,
            'qr_code' => $qrCode,
        ]);

        return [
            'status' => $status,
            'qr_code' => $this->toQrDataUri($qrCode),
            'session_id' => $session->session_id,
        ];
    }

    /**
     * @return array{status:string,qr_code:?string,session_id:string}
     */
    public function getStatus(School $school): array
    {
        $session = $this->getOrCreateSession($school);
        $response = $this->waGatewayService->getSessionStatus($session->session_id);

        if ($response !== null) {
            $status = $this->normalizeStatus((string) ($response['status'] ?? $session->status));
            $qrRaw = (string) ($response['qr'] ?? $response['qr_code'] ?? '');
            $qrCode = $qrRaw !== '' ? $qrRaw : $session->qr_code;

            $session->update([
                'status' => $status,
                'qr_code' => $qrCode,
            ]);
        } else {
            $session->update(['status' => 'DISCONNECTED']);
        }

        $session->refresh();

        return [
            'status' => (string) $session->status,
            'qr_code' => $this->toQrDataUri($session->qr_code),
            'session_id' => (string) $session->session_id,
        ];
    }

    private function normalizeStatus(string $status): string
    {
        $normalized = strtoupper(trim($status));
        if (!in_array($normalized, ['DISCONNECTED', 'QR_READY', 'CONNECTED'], true)) {
            return 'DISCONNECTED';
        }

        return $normalized;
    }

    private function toQrDataUri(?string $qrCode): ?string
    {
        if ($qrCode === null || $qrCode === '') {
            return null;
        }

        if (str_starts_with($qrCode, 'data:image')) {
            return $qrCode;
        }

        return 'data:image/png;base64,' . $qrCode;
    }
}
