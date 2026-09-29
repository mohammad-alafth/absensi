<?php

namespace App\Models\Concerns;

use App\Support\SignatureCompressor;

/*
|--------------------------------------------------------------------------
| OTOMATIS KOMPRES KOLOM TANDA TANGAN
|--------------------------------------------------------------------------
| Dipakai model Leave, Permission, dan Overtime. Saat model disimpan,
| seluruh kolom tanda tangan yang berubah (data-URI base64 dari canvas)
| diperkecil dimensinya lewat App\Support\SignatureCompressor.
|
| Tidak mengubah alur approval/penyimpanan: nilai tetap tersimpan pada
| kolom yang sama, hanya ukuran gambarnya lebih efisien.
*/
trait CompressesSignatureAttributes
{
    protected static function bootCompressesSignatureAttributes(): void
    {
        static::saving(function ($model) {
            foreach (static::signatureAttributes() as $attribute) {

                // Hanya proses kolom yang berubah agar tidak ada beban tambahan.
                if (! $model->isDirty($attribute)) {
                    continue;
                }

                $value = $model->getAttribute($attribute);

                if (empty($value)) {
                    continue;
                }

                $model->setAttribute($attribute, SignatureCompressor::compress($value));
            }
        });
    }

    /**
     * Daftar kolom tanda tangan pada tabel pengajuan.
     */
    public static function signatureAttributes(): array
    {
        return [
            'employee_signature',
            'pj_signature',
            'hrd_signature',
            'head_signature',
            'director_signature',
            'medical_service_signature',
            'kabag_umum_signature',
            'manager_umum_signature',
            'kabag_marketing_signature',
            'manager_finance_signature',
        ];
    }
}
