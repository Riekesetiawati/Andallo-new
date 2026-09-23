<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Exceptions\BookingRuleException;
use App\Models\Booking;
use App\Models\Review;
use App\Models\Service;
use App\Models\Setting;
use App\Services\BookingService;
use App\Services\CancellationService;
use App\Services\ScheduleService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->string('status')->toString();
        $bookings = Booking::query()
            ->where('customer_id', $request->user()->id)
            ->with(['service', 'provider', 'payment'])
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('bookings.index', compact('bookings', 'status'));
    }

    public function create(Service $service, ScheduleService $schedule)
    {
        abort_unless($service->is_active && $service->provider?->isApproved(), 404);
        $service->load('provider', 'category');
        $date = request('date', now()->addDay()->toDateString());
        $slots = $schedule->slotsFor($service, Carbon::parse($date));
        $policy = (string) Setting::getValue('cancellation_policy', '');
        $percent = Setting::int('refund_before_start_percent', 100);

        return view('bookings.create', compact('service', 'slots', 'date', 'policy', 'percent'));
    }

    public function store(Request $request, Service $service, BookingService $bookings)
    {
        $data = $request->validate([
            'scheduled_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'city' => ['required', 'string', 'max:80'],
            'address' => ['required', 'string', 'max:500'],
            'customer_notes' => ['nullable', 'string', 'max:1000'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'agree_policy' => ['accepted'],
        ]);

        try {
            $booking = $bookings->create($request->user(), $service, $data);
        } catch (BookingRuleException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('bookings.show', $booking)->with('success', 'Pesanan '.$booking->code.' berhasil dibuat.');
    }

    public function show(Request $request, Booking $booking)
    {
        abort_unless($booking->involves($request->user()), 403);
        $booking->load([
            'service.category',
            'provider.user',
            'customer',
            'payment.proofs',
            'histories.actor',
            'cancellationRequests',
            'refunds',
            'complaints',
            'review',
            'notificationLogs',
        ]);

        $bank = [
            'name' => Setting::getValue('bank_name'),
            'number' => Setting::getValue('bank_account_number'),
            'holder' => Setting::getValue('bank_account_holder'),
        ];
        $percent = Setting::int('refund_before_start_percent', 100);

        return view('bookings.show', compact('booking', 'bank', 'percent'));
    }

    public function cancel(Request $request, Booking $booking, CancellationService $cancellations)
    {
        abort_unless($booking->customer_id === $request->user()->id, 403);
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);

        try {
            $result = $cancellations->request($booking, $request->user(), $data['reason']);
        } catch (BookingRuleException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $message = $result instanceof Booking
            ? 'Pesanan dibatalkan dan jadwal dilepas.'
            : 'Permintaan pembatalan dikirim. Pesanan tetap pada tahap sebelumnya sampai ada keputusan.';

        return back()->with('success', $message);
    }

    public function confirmComplete(Request $request, Booking $booking, BookingService $bookings)
    {
        abort_unless($booking->customer_id === $request->user()->id, 403);
        try {
            $bookings->complete($booking, $request->user());
        } catch (BookingRuleException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Pesanan ditandai selesai. Anda dapat memberi ulasan.');
    }

    public function review(Request $request, Booking $booking)
    {
        abort_unless($booking->customer_id === $request->user()->id, 403);
        abort_unless($booking->status === BookingStatus::Completed, 403);
        if ($booking->review) {
            return back()->with('error', 'Pesanan ini sudah memiliki ulasan.');
        }

        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        Review::query()->create([
            'booking_id' => $booking->id,
            'customer_id' => $request->user()->id,
            'provider_id' => $booking->provider_id,
            'service_id' => $booking->service_id,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
            'is_demo' => false,
        ]);
        $booking->service?->refreshRating();

        return back()->with('success', 'Ulasan tersimpan.');
    }

    public function settlement(Request $request, Booking $booking, CancellationService $cancellations)
    {
        abort_unless($booking->customer_id === $request->user()->id, 403);
        $data = $request->validate(['decision' => ['required', 'in:accept,reject']]);
        $cancellation = $booking->cancellationRequests()->where('status', 'awaiting_customer')->latest()->first();
        if (! $cancellation) {
            return back()->with('error', 'Tidak ada hasil tinjauan yang menunggu persetujuan.');
        }

        try {
            $cancellations->customerDecision($cancellation, $request->user(), $data['decision'] === 'accept');
        } catch (BookingRuleException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', $data['decision'] === 'accept'
            ? 'Anda menyetujui hasil tinjauan. Pengembalian dana diproses terpisah dari status pesanan.'
            : 'Keberatan dicatat sebagai komplain. Pesanan tidak ditutup otomatis.');
    }
}
