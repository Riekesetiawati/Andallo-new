<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Exceptions\BookingRuleException;
use App\Models\Booking;
use App\Models\CancellationRequest;
use App\Models\Complaint;
use App\Models\Refund;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CancellationService
{
    public function __construct(
        private readonly BookingService $bookings,
        private readonly MarketplaceNotifier $notifier,
    ) {}

    public function request(Booking $booking, User $customer, string $reason): CancellationRequest|Booking
    {
        if ($booking->customer_id !== $customer->id) {
            throw new BookingRuleException('Anda tidak dapat membatalkan pesanan ini.');
        }

        return DB::transaction(function () use ($booking, $customer, $reason) {
            $booking = $this->bookings->lock($booking);
            $paymentStatus = $booking->payment?->status;

            if (in_array($booking->status, [BookingStatus::PendingReview, BookingStatus::AwaitingProvider], true)) {
                return $this->bookings->cancelDirect($booking, $customer, $reason);
            }

            if ($booking->status === BookingStatus::AwaitingPayment && in_array($paymentStatus, [null, PaymentStatus::Unpaid, PaymentStatus::Rejected, PaymentStatus::Expired], true)) {
                return $this->bookings->cancelDirect($booking, $customer, $reason);
            }

            if ($booking->status === BookingStatus::Completed) {
                throw new BookingRuleException('Pesanan selesai tidak dapat dibatalkan. Ajukan komplain jika ada masalah.');
            }

            if (in_array($booking->status, [BookingStatus::Cancelled, BookingStatus::Rejected, BookingStatus::Expired], true)) {
                throw new BookingRuleException('Pesanan ini sudah tidak aktif.');
            }

            $open = $booking->cancellationRequests()->whereIn('status', ['pending', 'pending_funds_check', 'awaiting_customer'])->exists();
            if ($open) {
                throw new BookingRuleException('Masih ada permintaan pembatalan yang belum selesai.');
            }

            if ($booking->status === BookingStatus::AwaitingPayment && $paymentStatus === PaymentStatus::UnderReview) {
                $request = CancellationRequest::query()->create([
                    'booking_id' => $booking->id,
                    'requested_by' => $customer->id,
                    'stage' => 'under_review',
                    'reason' => $reason,
                    'status' => 'pending_funds_check',
                ]);
                $this->notifyAdmins($booking, 'Pembatalan saat bukti diperiksa. Pastikan dana sudah masuk atau belum.');

                return $request;
            }

            if ($booking->status === BookingStatus::Confirmed) {
                $percent = Setting::int('refund_before_start_percent', 100);
                $estimate = round(((float) $booking->total) * $percent / 100, 2);
                $request = CancellationRequest::query()->create([
                    'booking_id' => $booking->id,
                    'requested_by' => $customer->id,
                    'stage' => 'before_start',
                    'reason' => $reason,
                    'status' => 'pending',
                    'refund_estimate' => $estimate,
                ]);
                $this->notifyAdmins($booking, 'Pembatalan sebelum pekerjaan dimulai. Estimasi pengembalian '.rupiah($estimate).' ('.$percent.'%).');

                return $request;
            }

            if (in_array($booking->status, [BookingStatus::OnTheWay, BookingStatus::InProgress, BookingStatus::AwaitingCustomer], true)) {
                $request = CancellationRequest::query()->create([
                    'booking_id' => $booking->id,
                    'requested_by' => $customer->id,
                    'stage' => 'in_progress',
                    'reason' => $reason,
                    'status' => 'pending',
                ]);
                $this->notifyAdmins($booking, 'Pembatalan saat pekerjaan berjalan. Tinjau progres dan ajukan nominal pengembalian.');

                return $request;
            }

            throw new BookingRuleException('Pembatalan tidak tersedia pada status ini.');
        });
    }

    public function resolveFunds(CancellationRequest $request, User $admin, bool $fundsReceived, ?string $note = null): void
    {
        DB::transaction(function () use ($request, $admin, $fundsReceived, $note) {
            $request = CancellationRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();
            $booking = $this->bookings->lock($request->booking);
            if ($request->status !== 'pending_funds_check') {
                throw new BookingRuleException('Permintaan ini tidak menunggu pengecekan dana.');
            }

            if (! $fundsReceived) {
                if ($booking->payment) {
                    $booking->payment->status = PaymentStatus::Rejected;
                    $booking->payment->rejection_reason = $note ?: 'Dana belum masuk saat pembatalan.';
                    $booking->payment->verified_by = $admin->id;
                    $booking->payment->verified_at = now();
                    $booking->payment->save();
                    $booking->payment_status = PaymentStatus::Rejected;
                    $booking->save();
                }
                $this->bookings->cancelDirect($booking, $admin, $note ?: 'Dibatalkan. Dana belum masuk.');
                $request->status = 'approved';
                $request->admin_note = $note;
                $request->resolved_by = $admin->id;
                $request->resolved_at = now();
                $request->save();

                return;
            }

            if (! $booking->payment || $booking->payment->status !== PaymentStatus::UnderReview) {
                throw new BookingRuleException('Tidak ada bukti yang dapat diverifikasi.');
            }

            $booking->payment->status = PaymentStatus::Paid;
            $booking->payment->paid_at = now();
            $booking->payment->verified_by = $admin->id;
            $booking->payment->verified_at = now();
            $booking->payment->verification_note = $note ?: 'Dana sudah masuk, pembatalan dilanjutkan ke pengembalian.';
            $booking->payment->save();
            $booking->payment_status = PaymentStatus::Paid;
            $booking->save();

            $percent = Setting::int('refund_before_start_percent', 100);
            $estimate = round(((float) $booking->total) * $percent / 100, 2);
            $this->approveRefundable($booking, $request, $admin, $estimate, $note ?: 'Dana sudah masuk, pesanan dibatalkan untuk pengembalian.');
        });
    }

    public function approveBeforeStart(CancellationRequest $request, User $admin, ?string $note = null): void
    {
        DB::transaction(function () use ($request, $admin, $note) {
            $request = CancellationRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();
            if ($request->status !== 'pending' || $request->stage !== 'before_start') {
                throw new BookingRuleException('Permintaan ini tidak dapat disetujui langsung.');
            }
            $booking = $this->bookings->lock($request->booking);
            $amount = (float) ($request->refund_estimate ?? $booking->total);
            $this->approveRefundable($booking, $request, $admin, $amount, $note ?: 'Pembatalan disetujui sebelum pekerjaan dimulai.');
        });
    }

    public function proposeSettlement(CancellationRequest $request, User $admin, float $amount, string $note): void
    {
        DB::transaction(function () use ($request, $admin, $amount, $note) {
            $request = CancellationRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();
            if ($request->stage !== 'in_progress' || $request->status !== 'pending') {
                throw new BookingRuleException('Penyelesaian ini tidak menunggu usulan admin.');
            }
            if ($amount < 0) {
                throw new BookingRuleException('Nominal pengembalian tidak boleh negatif.');
            }
            $request->refund_amount = $amount;
            $request->admin_note = $note;
            $request->status = 'awaiting_customer';
            $request->resolved_by = $admin->id;
            $request->save();

            $booking = $request->booking()->with('customer')->first();
            $this->notifier->notifyUser(
                $booking->customer,
                'cancellation.proposal',
                'Hasil tinjauan pembatalan',
                'Admin mengusulkan pengembalian '.rupiah($amount).' untuk pesanan '.$booking->code.'. Setujui atau ajukan komplain.',
                ['booking_id' => $booking->id],
            );
        });
    }

    public function customerDecision(CancellationRequest $request, User $customer, bool $accept): void
    {
        DB::transaction(function () use ($request, $customer, $accept) {
            $request = CancellationRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();
            $booking = $this->bookings->lock($request->booking);
            if ($booking->customer_id !== $customer->id || $request->status !== 'awaiting_customer') {
                throw new BookingRuleException('Keputusan ini tidak tersedia.');
            }

            if (! $accept) {
                $request->status = 'customer_disputed';
                $request->save();
                Complaint::query()->create([
                    'booking_id' => $booking->id,
                    'user_id' => $customer->id,
                    'subject' => 'Keberatan hasil pembatalan '.$booking->code,
                    'description' => 'Pelanggan tidak menyetujui usulan pengembalian. Pesanan tetap pada status '.$booking->status->label().'.',
                    'status' => 'open',
                ]);
                $this->notifyAdmins($booking, 'Pelanggan tidak setuju dengan hasil tinjauan pembatalan. Komplain dibuka.');

                return;
            }

            $this->approveRefundable(
                $booking,
                $request,
                $customer,
                (float) $request->refund_amount,
                'Pelanggan menyetujui hasil tinjauan pembatalan.',
            );
        });
    }

    public function confirmRefund(Refund $refund, User $admin, string $note, ?string $proofPath = null): Refund
    {
        return DB::transaction(function () use ($refund, $admin, $note, $proofPath) {
            $refund = Refund::query()->whereKey($refund->id)->lockForUpdate()->firstOrFail();
            if (! in_array($refund->status, [RefundStatus::PendingReview, RefundStatus::Processing, RefundStatus::Failed], true)) {
                throw new BookingRuleException('Refund ini tidak dapat dikonfirmasi.');
            }

            $booking = $this->bookings->lock($refund->booking);
            $payment = $booking->payment;
            if (! $payment) {
                throw new BookingRuleException('Pembayaran tidak ditemukan.');
            }

            $refund->status = RefundStatus::Success;
            $refund->responsible_admin_id = $admin->id;
            $refund->processed_at = now();
            $refund->note = $note;
            if ($proofPath) {
                $refund->proof_path = $proofPath;
            }
            $refund->save();

            $full = (float) $refund->amount >= (float) $payment->amount;
            $payment->status = $full ? PaymentStatus::Refunded : PaymentStatus::PartiallyRefunded;
            $payment->save();
            $booking->payment_status = $payment->status;
            $booking->refund_status = RefundStatus::Success;
            $booking->save();

            $this->notifier->notifyUser(
                $booking->customer,
                'refund.success',
                'Pengembalian dana selesai',
                'Pengembalian '.rupiah($refund->amount).' untuk pesanan '.$booking->code.' sudah dikonfirmasi.',
                ['booking_id' => $booking->id],
            );
            $this->notifier->whatsapp(
                $booking,
                $booking->customer,
                (string) $booking->customer->phone,
                'refund_success',
                $this->notifier->bookingSummary($booking)."\nPengembalian dana dikonfirmasi: ".rupiah($refund->amount),
            );

            return $refund;
        });
    }

    public function failRefund(Refund $refund, User $admin, string $note): Refund
    {
        return DB::transaction(function () use ($refund, $admin, $note) {
            $refund = Refund::query()->whereKey($refund->id)->lockForUpdate()->firstOrFail();
            $refund->status = RefundStatus::Failed;
            $refund->responsible_admin_id = $admin->id;
            $refund->note = $note;
            $refund->save();

            $booking = $refund->booking;
            $booking->refund_status = RefundStatus::Failed;
            $booking->save();

            $this->notifier->notifyUser(
                $booking->customer,
                'refund.failed',
                'Pengembalian dana gagal',
                'Pengembalian dana pesanan '.$booking->code.' gagal dan akan ditangani ulang. '.$note,
                ['booking_id' => $booking->id],
            );

            return $refund;
        });
    }

    private function approveRefundable(Booking $booking, CancellationRequest $request, User $actor, float $amount, string $note): void
    {
        if ($booking->status !== BookingStatus::Cancelled) {
            $this->bookings->changeStatus($booking, BookingStatus::Cancelled, $actor, $note);
            $this->bookings->releaseSlot($booking);
        }

        $payment = $booking->payment;
        if ($payment && $payment->status === PaymentStatus::Paid) {
            $payment->status = PaymentStatus::RefundPending;
            $payment->save();
            $booking->payment_status = PaymentStatus::RefundPending;
            $booking->refund_status = RefundStatus::Processing;
            $booking->save();

            Refund::query()->create([
                'booking_id' => $booking->id,
                'payment_id' => $payment->id,
                'cancellation_request_id' => $request->id,
                'amount' => $amount,
                'reason' => $request->reason,
                'status' => RefundStatus::Processing,
                'note' => $note,
            ]);
        }

        $request->status = 'approved';
        $request->refund_amount = $amount;
        $request->admin_note = $note;
        $request->resolved_by = $actor->isAdmin() ? $actor->id : $request->resolved_by;
        $request->resolved_at = now();
        $request->save();

        $this->notifier->notifyUser($booking->customer, 'booking.cancelled', 'Pesanan dibatalkan', 'Pesanan '.$booking->code.' dibatalkan. Pengembalian dana masih diproses terpisah.', ['booking_id' => $booking->id]);
        if ($booking->provider?->user) {
            $this->notifier->notifyUser($booking->provider->user, 'booking.cancelled', 'Pesanan dibatalkan', 'Pesanan '.$booking->code.' dibatalkan. Jangan lanjutkan pekerjaan.', ['booking_id' => $booking->id]);
        }
    }

    private function notifyAdmins(Booking $booking, string $message): void
    {
        User::query()->where('role', 'admin')->where('is_active', true)->each(function (User $admin) use ($booking, $message) {
            $this->notifier->notifyUser($admin, 'cancellation.queue', 'Permintaan pembatalan', $message.' ('.$booking->code.')', ['booking_id' => $booking->id]);
        });
    }
}
