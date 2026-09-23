<?php

namespace App\Services;

use App\Exceptions\BookingRuleException;
use Illuminate\Http\UploadedFile;

class ProofInspector
{
    public function assertValid(UploadedFile $file): string
    {
        if ($file->getSize() > 2 * 1024 * 1024) {
            throw new BookingRuleException('Ukuran bukti pembayaran maksimal 2 MB.');
        }

        $path = $file->getRealPath();
        if ($path === false) {
            throw new BookingRuleException('Berkas bukti pembayaran tidak dapat dibaca.');
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path);
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];

        if (! in_array($mime, $allowed, true)) {
            throw new BookingRuleException('Bukti pembayaran harus berupa JPG, PNG, WEBP, atau PDF.');
        }

        if (str_starts_with($mime, 'image/')) {
            $info = @getimagesize($path);
            if ($info === false) {
                throw new BookingRuleException('Isi berkas gambar tidak valid.');
            }
        }

        if ($mime === 'application/pdf') {
            $header = file_get_contents($path, false, null, 0, 5);
            if ($header !== '%PDF-') {
                throw new BookingRuleException('Isi berkas PDF tidak valid.');
            }
        }

        return $mime;
    }
}
