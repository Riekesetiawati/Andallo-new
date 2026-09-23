<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Exceptions\BookingRuleException;
use App\Models\Booking;
use App\Models\BookingStatusHistory;
use App\Models\Payment;
use App\Models\Provider;
use App\Models\ProviderSlotReservation;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingService
{
    public function __construct(
        private readonly ScheduleService $schedule,
        private readonly MarketplaceNotifier $notifier,
    ) {}

    public function sweepExpired(): int
    {
        $count = 0;

        $providerDue = Booking::query()
            ->where('status', BookingStatus::AwaitingProvider->value)
            ->whereNotNull('provider_response_deadline')
            ->where('provider_response_deadline', '<=', now())
            ->get();

        foreach ($providerDue as $booking) {
            try {
                $this->expire($booking, 'Batas respons penyedia terlewati. Jadwal dilepas.');
                $count++;
            } catch (BookingRuleException) {
                // Status sudah berubah di transaksi lain.
            }
        }

        $paymentDue = Booking::query()
            ->where('status', BookingStatus::AwaitingPayment->value)
            ->where('payment_status', PaymentStatus::Unpaid->value)
            ->whereNotNull('payment_deadline')
            ->where('payment_deadline', '<=', now())
            ->get();

        foreach ($paymentDue as $booking) {
            try {
                $this->expire($booking, 'Batas waktu pembayaran terlewati. Jadwal dilepas.');
                $count++;
            } catch (BookingRuleException) {
                // Status sudah berubah di transaksi lain.
            }
        }

        return $count;
    }

    public function create(User $customer, Service $service, array $input): Booking
    {
        $service->loadMissing('provider.user', 'category');
        $provider = $service->provider;

        if (! $provider || ! $provider->isApproved()) {
            throw new BookingRuleException('Penyedia ini belum dapat menerima pesanan.');
        }

        $start = Carbon::parse($input['scheduled_date'].' '.$input['start_time']);
        $end = isset($input['end_time'])
            ? Carbon::parse($input['scheduled_date'].' '.$input['end_time'])
            : $start->copy()->addMinutes((int) $service->duration_minutes);

        if (! $this->schedule->isBookable($service, $start, $end)) {
            throw new BookingRuleException('Jadwal yang dipilih sudah penuh atau tidak tersedia.');
        }

        $total = number_format((float) $service->price, 2, '.', '');

        return DB::transaction(function () use ($customer, $service, $provider, $input, $start, $end, $total) {
            Provider::query()->whereKey($provider->id)->lockForUpdate()->first();

            $conflict = ProviderSlotReservation::query()
                ->where('provider_id', $provider->id)
                ->where('starts_at', '<', $end)
                ->where('ends_at', '>', $start)
                ->lockForUpdate()
                ->exists();

            if ($conflict) {
                throw new BookingRuleException('Jadwal ini baru saja dipesan pelanggan lain.');
            }

            $needsReview = Setting::bool('require_admin_review', true);
            $status = $needsReview ? BookingStatus::PendingReview : BookingStatus::AwaitingProvider;
            $policy = (string) Setting::getValue('cancellation_policy', config('andallo.defaults.cancellation_policy'));

            $booking = Booking::query()->create([
                'code' => $this->makeCode(),
                'customer_id' => $customer->id,
                'provider_id' => $provider->id,
                'service_id' => $service->id,
                'scheduled_date' => $start->toDateString(),
                'start_time' => $start->format('H:i:s'),
                'end_time' => $end->format('H:i:s'),
                'city' => $input['city'],
                'address' => $input['address'],
                'customer_notes' => $input['customer_notes'] ?? null,
                'latitude' => $input['latitude'] ?? null,
                'longitude' => $input['longitude'] ?? null,
                'service_name_snapshot' => $service->name,
                'price_snapshot' => $total,
                'price_unit_snapshot' => $service->price_unit,
                'duration_snapshot' => $service->duration_minutes,
                'includes_snapshot' => $service->includes,
                'excludes_snapshot' => $service->excludes,
                'requires_visit_snapshot' => $service->requires_visit,
                'total' => $total,
                'status' => $status,
                'payment_status' => PaymentStatus::Unpaid,
                'refund_status' => RefundStatus::NotRequired,
                'provider_response_deadline' => $needsReview ? null : now()->addMinutes(Setting::int('provider_response_minutes', 2)),
                'cancellation_policy_snapshot' => $policy,
                'is_demo' => (bool) $service->is_demo,
            ]);

            try {
                ProviderSlotReservation::query()->create([
                    'provider_id' => $provider->id,
                    'booking_id' => $booking->id,
                    'starts_at' => $start,
                    'ends_at' => $end,
                ]);
            } catch (QueryException) {
                throw new BookingRuleException('Jadwal ini baru saja dipesan pelanggan lain.');
            }

            $this->writeHistory($booking, null, $status, null, PaymentStatus::Unpaid, $customer, 'Pesanan dibuat.');

            if ($needsReview) {
                $this->notifyAdmins(
                    'Pemeriksaan pesanan',
                    'Pesanan '.$booking->code.' menunggu pemeriksaan sebelum diteruskan ke penyedia.',
                    $booking,
                );
                $this->notifier->notifyUser(
                    $customer,
                    'booking.created',
                    'Pesanan tercatat',
                    'Pesanan '.$booking->code.' menunggu pemeriksaan admin. Status belum diterima penyedia.',
                    ['booking_id' => $booking->id],
                );
            } else {
                $this->dispatchProviderRequest($booking);
            }

            return $booking->fresh(['payment', 'provider.user', 'service']);
        });
    }

    public function forwardToProvider(Booking $booking, User $admin): Booking
    {
        return DB::transaction(function () use ($booking, $admin) {
            $booking = $this->lock($booking);
            $this->changeStatus($booking, BookingStatus::AwaitingProvider, $admin, 'Permintaan diteruskan ke penyedia.');
            $booking->provider_response_deadline = now()->addMinutes(Setting::int('provider_response_minutes', 2));
            $booking->save();
            $this->dispatchProviderRequest($booking);

            return $booking->fresh(['provider.user', 'payment']);
        });
    }

    public function rejectReview(Booking $booking, User $admin, string $reason): Booking
    {
        return DB::transaction(function () use ($booking, $admin, $reason) {
            $booking = $this->lock($booking);
            $this->changeStatus($booking, BookingStatus::Rejected, $admin, $reason);
            $booking->admin_note = $reason;
            $booking->save();
            $this->releaseSlot($booking);
            $this->notifier->notifyUser(
                $booking->customer,
                'booking.rejected',
                'Pesanan ditolak',
                'Pesanan '.$booking->code.' ditolak saat pemeriksaan: '.$reason,
                ['booking_id' => $booking->id],
            );

            return $booking;
        });
    }

    public function accept(Booking $booking, User $actor): Booking
    {
        return DB::transaction(function () use ($booking, $actor) {
            $booking = $this->lock($booking);

            if ($booking->status === BookingStatus::AwaitingPayment && $booking->payment) {
                return $booking;
            }

            if ($booking->provider_response_deadline && $booking->provider_response_deadline->lte(now())) {
                return $this->expire($booking, 'Batas respons penyedia terlewati. Jadwal dilepas.');
            }

            $this->changeStatus($booking, BookingStatus::AwaitingPayment, $actor, 'Penyedia menerima pesanan.');
            $booking->payment_deadline = now()->addMinutes(Setting::int('payment_window_minutes', 1440));
            $booking->save();

            if (! $booking->payment) {
                Payment::query()->create([
                    'booking_id' => $booking->id,
                    'amount' => $booking->total,
                    'method' => 'manual_transfer',
                    'status' => PaymentStatus::Unpaid,
                ]);
            }

            $detail = route('bookings.show', $booking);
            $body = $this->notifier->bookingSummary($booking)
                ."\nStatus: menunggu pembayaran."
                ."\nDetail pesanan (perlu masuk ke akun Anda): {$detail}"
                ."\nAlamat lengkap tidak disertakan di pesan ini.";

            $this->notifier->notifyUser(
                $booking->customer,
                'booking.accepted',
                'Penyedia menerima pesanan',
                'Pesanan '.$booking->code.' menunggu pembayaran sebesar '.rupiah($booking->total).'.',
                ['booking_id' => $booking->id],
            );
            $this->notifier->whatsapp($booking, $booking->customer, (string) $booking->customer->phone, 'invoice', $body);

            return $booking->fresh('payment');
        });
    }

    public function rejectByProvider(Booking $booking, User $actor, string $reason): Booking
    {
        return DB::transaction(function () use ($booking, $actor, $reason) {
            $booking = $this->lock($booking);
            $this->changeStatus($booking, BookingStatus::Rejected, $actor, $reason);
            $booking->provider_note = $reason;
            $booking->save();
            $this->releaseSlot($booking);
            $this->notifier->notifyUser(
                $booking->customer,
                'booking.rejected',
                'Penyedia menolak pesanan',
                'Pesanan '.$booking->code.' ditolak: '.$reason.'. Anda dapat memilih penyedia lain.',
                ['booking_id' => $booking->id],
            );
            $this->notifier->whatsapp(
                $booking,
                $booking->customer,
                (string) $booking->customer->phone,
                'rejected',
                $this->notifier->bookingSummary($booking, false)."\nDitolak: {$reason}\nSilakan pilih penyedia lain di Andallo.",
            );

            return $booking;
        });
    }

    public function advance(Booking $booking, User $actor, BookingStatus $to): Booking
    {
        return DB::transaction(function () use ($booking, $actor, $to) {
            $booking = $this->lock($booking);

            if ($booking->payment_status !== PaymentStatus::Paid) {
                throw new BookingRuleException('Layanan belum dapat dijalankan sebelum pembayaran terverifikasi.');
            }

            if ($to === BookingStatus::OnTheWay && ! $booking->requires_visit_snapshot) {
                throw new BookingRuleException('Layanan ini tidak membutuhkan kunjungan.');
            }

            $this->changeStatus($booking, $to, $actor, 'Progres layanan diperbarui.');
            $label = $to->label();
            $this->notifier->notifyUser(
                $booking->customer,
                'booking.progress',
                'Status pesanan berubah',
                'Pesanan '.$booking->code.' sekarang: '.$label.'.',
                ['booking_id' => $booking->id],
            );
            $this->notifier->whatsapp(
                $booking,
                $booking->customer,
                (string) $booking->customer->phone,
                'progress',
                $this->notifier->bookingSummary($booking)."\nStatus: {$label}\nDetail: ".route('bookings.show', $booking),
            );

            return $booking;
        });
    }

    public function complete(Booking $booking, User $actor): Booking
    {
        return DB::transaction(function () use ($booking, $actor) {
            $booking = $this->lock($booking);
            $this->changeStatus($booking, BookingStatus::Completed, $actor, 'Pelanggan mengonfirmasi pekerjaan selesai.');
            $this->releaseSlot($booking);
            $this->notifier->notifyUser(
                $booking->provider->user,
                'booking.completed',
                'Pesanan selesai',
                'Pelanggan mengonfirmasi pesanan '.$booking->code.' selesai.',
                ['booking_id' => $booking->id],
            );

            return $booking;
        });
    }

    public function expire(Booking $booking, string $reason): Booking
    {
        return DB::transaction(function () use ($booking, $reason) {
            $booking = $this->lock($booking);
            if (in_array($booking->status, [BookingStatus::Expired, BookingStatus::Cancelled, BookingStatus::Rejected, BookingStatus::Completed], true)) {
                return $booking;
            }
            $this->changeStatus($booking, BookingStatus::Expired, null, $reason);
            if ($booking->payment && $booking->payment->status === PaymentStatus::Unpaid) {
                $booking->payment->status = PaymentStatus::Expired;
                $booking->payment->save();
                $booking->payment_status = PaymentStatus::Expired;
                $booking->save();
            }
            $this->releaseSlot($booking);
            $this->notifier->notifyUser(
                $booking->customer,
                'booking.expired',
                'Pesanan kedaluwarsa',
                'Pesanan '.$booking->code.' kedaluwarsa. '.$reason,
                ['booking_id' => $booking->id],
            );
            if ($booking->provider?->user) {
                $this->notifier->notifyUser(
                    $booking->provider->user,
                    'booking.expired',
                    'Pesanan kedaluwarsa',
                    'Pesanan '.$booking->code.' kedaluwarsa. '.$reason,
                    ['booking_id' => $booking->id],
                );
            }

            return $booking;
        });
    }

    public function cancelDirect(Booking $booking, User $actor, string $reason): Booking
    {
        return DB::transaction(function () use ($booking, $actor, $reason) {
            $booking = $this->lock($booking);
            $this->changeStatus($booking, BookingStatus::Cancelled, $actor, $reason);
            $this->releaseSlot($booking);
            $this->notifyCancelled($booking, $reason);

            return $booking;
        });
    }

    public function releaseSlot(Booking $booking): void
    {
        ProviderSlotReservation::query()->where('booking_id', $booking->id)->delete();
    }

    public function changeStatus(Booking $booking, BookingStatus $to, ?User $actor, ?string $note, ?PaymentStatus $paymentTo = null): void
    {
        $from = $booking->status;
        $allowed = $this->transitions()[$from->value] ?? [];
        if (! in_array($to->value, $allowed, true)) {
            throw new BookingRuleException('Perubahan status dari '.$from->label().' ke '.$to->label().' tidak diizinkan.');
        }

        $fromPayment = $booking->payment_status;
        $booking->status = $to;
        if ($paymentTo) {
            $booking->payment_status = $paymentTo;
        }
        $booking->save();
        $this->writeHistory($booking, $from, $to, $fromPayment, $booking->payment_status, $actor, $note);
    }

    public function lock(Booking $booking): Booking
    {
        $locked = Booking::query()->whereKey($booking->id)->lockForUpdate()->firstOrFail();
        $locked->load(['customer', 'provider.user', 'payment', 'service']);

        return $locked;
    }

    public function dispatchProviderRequest(Booking $booking): void
    {
        $booking->loadMissing('provider.user', 'customer');
        $url = $this->notifier->providerActionUrl($booking);
        $minutes = Setting::int('provider_response_minutes', 2);
        $body = $this->notifier->bookingSummary($booking)
            ."\nBatas respons: {$minutes} menit sejak pesan ini dicatat."
            ."\nBuka halaman aman untuk menerima atau menolak. Membuka tautan tidak mengubah status: {$url}"
            ."\nAlamat lengkap tidak disertakan di pesan ini.";

        $log = $this->notifier->whatsapp(
            $booking,
            $booking->provider->user,
            $booking->provider->whatsapp,
            'provider_request',
            $body,
        );

        $this->notifier->notifyUser(
            $booking->provider->user,
            'booking.request',
            'Ada permintaan pesanan',
            'Pesanan '.$booking->code.' menunggu respons Anda. Pengiriman WhatsApp: '.$log->statusLabel().'.',
            ['booking_id' => $booking->id],
        );
    }

    private function notifyCancelled(Booking $booking, string $reason): void
    {
        $booking->loadMissing('customer', 'provider.user');
        $text = 'Pesanan '.$booking->code.' dibatalkan. '.$reason;
        $this->notifier->notifyUser($booking->customer, 'booking.cancelled', 'Pesanan dibatalkan', $text, ['booking_id' => $booking->id]);
        if ($booking->provider?->user) {
            $this->notifier->notifyUser($booking->provider->user, 'booking.cancelled', 'Pesanan dibatalkan', $text, ['booking_id' => $booking->id]);
            $this->notifier->whatsapp(
                $booking,
                $booking->provider->user,
                $booking->provider->whatsapp,
                'cancelled',
                $this->notifier->bookingSummary($booking, false)."\nDibatalkan: {$reason}",
            );
        }
    }

    private function notifyAdmins(string $title, string $body, Booking $booking): void
    {
        User::query()->where('role', 'admin')->where('is_active', true)->each(function (User $admin) use ($title, $body, $booking) {
            $this->notifier->notifyUser($admin, 'admin.queue', $title, $body, ['booking_id' => $booking->id]);
        });
    }

    private function writeHistory(
        Booking $booking,
        ?BookingStatus $from,
        BookingStatus $to,
        ?PaymentStatus $fromPayment,
        ?PaymentStatus $toPayment,
        ?User $actor,
        ?string $note,
    ): void {
        BookingStatusHistory::query()->create([
            'booking_id' => $booking->id,
            'from_status' => $from?->value,
            'to_status' => $to->value,
            'from_payment_status' => $fromPayment?->value,
            'to_payment_status' => $toPayment?->value,
            'actor_id' => $actor?->id,
            'actor_role' => $actor?->role?->value,
            'note' => $note,
        ]);
    }

    private function makeCode(): string
    {
        do {
            $code = 'AND-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
        } while (Booking::query()->where('code', $code)->exists());

        return $code;
    }

    private function transitions(): array
    {
        return [
            BookingStatus::PendingReview->value => [
                BookingStatus::AwaitingProvider->value,
                BookingStatus::Rejected->value,
                BookingStatus::Cancelled->value,
            ],
            BookingStatus::AwaitingProvider->value => [
                BookingStatus::AwaitingPayment->value,
                BookingStatus::Rejected->value,
                BookingStatus::Cancelled->value,
                BookingStatus::Expired->value,
            ],
            BookingStatus::AwaitingPayment->value => [
                BookingStatus::Confirmed->value,
                BookingStatus::Cancelled->value,
                BookingStatus::Expired->value,
            ],
            BookingStatus::Confirmed->value => [
                BookingStatus::OnTheWay->value,
                BookingStatus::InProgress->value,
                BookingStatus::Cancelled->value,
            ],
            BookingStatus::OnTheWay->value => [
                BookingStatus::InProgress->value,
                BookingStatus::Cancelled->value,
            ],
            BookingStatus::InProgress->value => [
                BookingStatus::AwaitingCustomer->value,
                BookingStatus::Cancelled->value,
            ],
            BookingStatus::AwaitingCustomer->value => [
                BookingStatus::Completed->value,
            ],
        ];
    }
}
