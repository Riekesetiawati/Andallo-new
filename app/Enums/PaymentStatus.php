<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Unpaid = 'unpaid';
    case UnderReview = 'under_review';
    case Paid = 'paid';
    case Rejected = 'rejected';
    case RefundPending = 'refund_pending';
    case PartiallyRefunded = 'partially_refunded';
    case Refunded = 'refunded';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Belum Dibayar',
            self::UnderReview => 'Menunggu Verifikasi',
            self::Paid => 'Berhasil',
            self::Rejected => 'Ditolak',
            self::RefundPending => 'Menunggu Pengembalian',
            self::PartiallyRefunded => 'Sebagian Dikembalikan',
            self::Refunded => 'Dikembalikan',
            self::Expired => 'Kedaluwarsa',
        };
    }
}
