<?php

namespace App\Enums;

enum RefundStatus: string
{
    case NotRequired = 'not_required';
    case PendingReview = 'pending_review';
    case Processing = 'processing';
    case Success = 'success';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::NotRequired => 'Tidak Diperlukan',
            self::PendingReview => 'Menunggu Peninjauan',
            self::Processing => 'Diproses',
            self::Success => 'Berhasil',
            self::Failed => 'Gagal',
        };
    }
}
