<?php

namespace App\Enums;

enum ProviderStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Revision = 'revision_requested';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Pending => 'Menunggu Verifikasi',
            self::Revision => 'Perlu Perbaikan',
            self::Approved => 'Terverifikasi',
            self::Rejected => 'Ditolak',
        };
    }
}
