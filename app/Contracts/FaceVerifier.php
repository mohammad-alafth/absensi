<?php

namespace App\Contracts;

use App\Models\User;
use App\Support\FaceResult;

/**
 * Kontrak verifikasi wajah.
 *
 * Implementasi yang tersedia:
 *  - App\Services\Face\NullFaceVerifier  : wajah tidak diverifikasi (fallback).
 *  - App\Services\Face\RealFaceVerifier  : memanggil microservice InsightFace.
 */
interface FaceVerifier
{
    /**
     * Verifikasi wajah pengguna dari foto selfie (base64).
     *
     * @param string|null $imageBase64 foto wajah (base64, boleh berawalan data:image/...)
     */
    public function verify(User $user, ?string $imageBase64): FaceResult;
}