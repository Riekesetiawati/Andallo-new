<?php

namespace App\Enums;

enum BookingStatus: string
{
    case PendingReview = 'pending_review';
    case AwaitingProvider = 'awaiting_provider';
    case Rejected = 'rejected';
    case AwaitingPayment = 'awaiting_payment';
    case Confirmed = 'confirmed';
    case OnTheWay = 'on_the_way';
    case InProgress = 'in_progress';
    case AwaitingCustomer = 'awaiting_customer';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::PendingReview => 'Menunggu Pemeriksaan',
            self::AwaitingProvider => 'Menunggu Respons Penyedia',
            self::Rejected => 'Ditolak',
            self::AwaitingPayment => 'Menunggu Pembayaran',
            self::Confirmed => 'Dikonfirmasi',
            self::OnTheWay => 'Menuju Lokasi',
            self::InProgress => 'Sedang Dikerjakan',
            self::AwaitingCustomer => 'Menunggu Konfirmasi Pelanggan',
            self::Completed => 'Selesai',
            self::Cancelled => 'Dibatalkan',
            self::Expired => 'Kedaluwarsa',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Completed, self::Confirmed => 'success',
            self::Rejected, self::Cancelled, self::Expired => 'danger',
            self::AwaitingPayment, self::PendingReview, self::AwaitingProvider, self::AwaitingCustomer => 'warning',
            default => 'info',
        };
    }
}
