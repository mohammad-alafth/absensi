<?php

namespace App\Http\Controllers;

use App\Models\UserDevice;
use App\Services\DeviceBindingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoint perangkat (device binding).
 *
 * Hanya dipakai untuk MENCATAT perangkat yang sedang dipakai pengguna.
 * Sesuai keputusan D1, endpoint ini tidak pernah dipakai untuk menolak absen.
 */
class DeviceController extends Controller
{
    public function __construct(
        private readonly DeviceBindingService $devices = new DeviceBindingService(),
    ) {
    }

    /**
     * POST /api/device/bind
     *
     * UUID dibaca dari header `X-Device-Id` (utama) atau body `device_uuid`.
     * Metadata label/platform hanya dipakai untuk tampilan; nilai yang tidak
     * masuk akal diabaikan, bukan menggagalkan pencatatan.
     */
    public function bind(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:120'],
            'platform' => ['nullable', 'string', 'max:60'],
        ]);

        $uuid = $request->header('X-Device-Id') ?: $request->input('device_uuid');

        $result = $this->devices->bind($user, $uuid, [
            'label' => $data['label'] ?? null,
            'platform' => $data['platform'] ?? null,
        ], $request);

        $device = $result['device'];
        $status = $result['status'];

        $payload = [
            'success' => $status === 'bound',
            'status' => $status,
            'max_per_user' => $result['max_per_user'],
            'limit_reached' => $status === 'limit_reached',
            'device' => $device instanceof UserDevice ? $device->toPublicArray() : null,
        ];

        return match ($status) {
            'invalid' => response()->json($payload + [
                'message' => 'ID perangkat tidak valid. Perangkat tidak dicatat.',
            ], 422),

            'disabled' => response()->json($payload + [
                'message' => 'Pencatatan perangkat sedang tidak diaktifkan.',
            ]),

            'limit_reached' => response()->json($payload + [
                'message' => 'Batas ' . $result['max_per_user']
                    . ' perangkat aktif sudah tercapai. Hubungi admin untuk mencabut perangkat lama.',
            ]),

            'revoked' => response()->json($payload + [
                'message' => 'Perangkat ini sudah dicabut admin. Hubungi admin bila perangkat benar milik Anda.',
            ]),

            default => response()->json($payload + [
                'message' => 'Perangkat tercatat.',
            ]),
        };
    }
}
