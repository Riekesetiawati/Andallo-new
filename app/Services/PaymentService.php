<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Exceptions\BookingRuleException;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\PaymentProof;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(
        private readonly BookingService $bookings,
        private readonly ProofInspector $proofs,
        private readonly MarketplaceNotifier $notifier,
    ) {}

    public function uploadProof(Booking $booking, User $customer, UploadedFile $file): Payment
    {
        if ($booking->customer_id !== $customer->id) {
            throw new BookingRuleException('Anda tidak dapat mengunggah bukti untuk pesanan ini.');
        }

        $this->proofs->assertValid($file);

        return DB::transaction(function () use ($booking, $customer, $file) {
            $booking = $this->bookings->lock($booking);
            $payment = $booking->payment;

            if (! $payment) {
                throw new BookingRuleException('Tagihan belum tersedia.');
            }

            $late = in_array($booking->status, [BookingStatus::Expired, BookingStatus::Cancelled], true);
            $payable = $booking->status === BookingStatus::AwaitingPayment
                && in_array($payment->status, [PaymentStatus::Unpaid, PaymentStatus::Rejected], true);

            if (! $late && ! $payable) {
                throw new BookingRuleException('Bukti pembayaran tidak dapat diunggah pada status ini.');
            }

            if ($payment->proof_path) {
                PaymentProof::query()->create([
                    'payment_id' => $payment->id,
                    'path' => $payment->proof_path,
                    'original_name' => $payment->proof_original_name ?: 'bukti',
                    'status' => $payment->status === PaymentStatus::Rejected ? 'rejected' : 'replaced',
                    'note' => $payment->rejection_reason,
                ]);
            }

            $path = $file->store('payment-proofs/'.$payment->id, 'local');
            $payment->proof_path = $path;
            $payment->proof_original_name = $file->getClientOriginalName();
            $payment->proof_version = (int) $payment->proof_version + 1;
            $payment->status = PaymentStatus::UnderReview;
            $payment->rejection_reason = null;
            $payment->review_due_at = now()->addHours(Setting::int('payment_review_hours', 24));
            $payment->save();

            $booking->payment_status = PaymentStatus::UnderReview;
            if ($late) {
                $booking->refund_status = RefundStatus::PendingReview;
                $booking->admin_note = trim(($booking->admin_note ? $booking->admin_note."\n" : '').'Bukti masuk setelah pesanan tidak lagi aktif. Masuk antrean peninjauan pengembalian dana.');
            }
            $booking->save();

            PaymentProof::query()->create([
                'payment_id' => $payment->id,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'status' => 'submitted',
            ]);

            $message = $late
                ? 'Bukti untuk pesanan '.$booking->code.' masuk setelah pesanan tidak aktif. Periksa untuk pengembalian dana, jangan jalankan layanan.'
                : 'Bukti pembayaran pesanan '.$booking->code.' menunggu verifikasi. Unggahan ini belum berarti dana sudah diterima.';

            User::query()->where('role', 'admin')->where('is_active', true)->each(function (User $admin) use ($message, $booking) {
                $this->notifier->notifyUser($admin, 'payment.review', 'Verifikasi transfer', $message, ['booking_id' => $booking->id]);
            });

            $this->notifier->notifyUser(
                $customer,
                'payment.uploaded',
                'Bukti terunggah',
                'Bukti pembayaran '.$booking->code.' menunggu pemeriksaan admin.',
                ['booking_id' => $booking->id],
            );

            return $payment->fresh();
        });
    }

    public function approve(Booking $booking, User $admin, ?string $note = null): Payment
    {
        return DB::transaction(function () use ($booking, $admin, $note) {
            $booking = $this->bookings->lock($booking);
            $payment = $booking->payment;

            if (! $payment || $payment->status !== PaymentStatus::UnderReview) {
                throw new BookingRuleException('Tidak ada bukti yang menunggu verifikasi.');
            }

            if ($booking->status !== BookingStatus::AwaitingPayment) {
                throw new BookingRuleException('Pembayaran tidak dapat disetujui pada status pesanan ini. Tinjau sebagai pengembalian dana jika pesanan sudah batal atau kedaluwarsa.');
            }

            $payment->status = PaymentStatus::Paid;
            $payment->paid_at = now();
            $payment->verified_by = $admin->id;
            $payment->verified_at = now();
            $payment->verification_note = $note;
            $payment->save();

            $this->bookings->changeStatus($booking, BookingStatus::Confirmed, $admin, $note ?: 'Pembayaran terverifikasi.', PaymentStatus::Paid);

            $summary = $this->notifier->bookingSummary($booking)."\nPembayaran terverifikasi. Jadwal dikonfirmasi.\nDetail: ".route('bookings.show', $booking);
            $this->notifier->notifyUser($booking->customer, 'payment.paid', 'Pembayaran terverifikasi', 'Pembayaran pesanan '.$booking->code.' berhasil diverifikasi.', ['booking_id' => $booking->id]);
            $this->notifier->notifyUser($booking->provider->user, 'payment.paid', 'Pembayaran terverifikasi', 'Pembayaran pesanan '.$booking->code.' sudah diverifikasi. Layanan dapat disiapkan.', ['booking_id' => $booking->id]);
            $this->notifier->whatsapp($booking, $booking->customer, (string) $booking->customer->phone, 'payment_confirmed', $summary);
            $this->notifier->whatsapp($booking, $booking->provider->user, $booking->provider->whatsapp, 'payment_confirmed', $summary);

            return $payment;
        });
    }

    public function rejectProof(Booking $booking, User $admin, string $reason): Payment
    {
        return DB::transaction(function () use ($booking, $admin, $reason) {
            $booking = $this->bookings->lock($booking);
            $payment = $booking->payment;

            if (! $payment || $payment->status !== PaymentStatus::UnderReview) {
                throw new BookingRuleException('Tidak ada bukti yang dapat ditolak.');
            }

            if ($booking->status !== BookingStatus::AwaitingPayment) {
                throw new BookingRuleException('Penolakan bukti pada pesanan nonaktif dilakukan lewat antrean refund.');
            }

            $payment->status = PaymentStatus::Rejected;
            $payment->rejection_reason = $reason;
            $payment->verified_by = $admin->id;
            $payment->verified_at = now();
            $payment->verification_note = $reason;
            $payment->save();

            $booking->payment_status = PaymentStatus::Rejected;
            $booking->payment_deadline = now()->addMinutes(Setting::int('payment_window_minutes', 1440));
            $booking->save();

            $this->notifier->notifyUser(
                $booking->customer,
                'payment.rejected',
                'Bukti pembayaran ditolak',
                'Bukti pesanan '.$booking->code.' ditolak: '.$reason.'. Anda dapat mengunggah ulang.',
                ['booking_id' => $booking->id],
            );

            return $payment;
        });
    }
}
