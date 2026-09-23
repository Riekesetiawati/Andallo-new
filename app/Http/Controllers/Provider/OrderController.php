<?php

namespace App\Http\Controllers\Provider;

use App\Enums\BookingStatus;
use App\Exceptions\BookingRuleException;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $provider = $request->user()->provider;
        $status = $request->string('status')->toString();
        $bookings = $provider->bookings()->with(['service', 'customer', 'payment'])
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('provider.orders', compact('bookings', 'status', 'provider'));
    }

    public function accept(Request $request, Booking $booking, BookingService $bookings)
    {
        $this->owns($request, $booking);
        try {
            $bookings->accept($booking, $request->user());
        } catch (BookingRuleException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Pesanan diterima. Pelanggan perlu membayar sebelum layanan dijalankan.');
    }

    public function reject(Request $request, Booking $booking, BookingService $bookings)
    {
        $this->owns($request, $booking);
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        try {
            $bookings->rejectByProvider($booking, $request->user(), $data['reason']);
        } catch (BookingRuleException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Pesanan ditolak dan jadwal dilepas.');
    }

    public function progress(Request $request, Booking $booking, BookingService $bookings)
    {
        $this->owns($request, $booking);
        $data = $request->validate([
            'status' => ['required', 'in:on_the_way,in_progress,awaiting_customer'],
        ]);
        try {
            $bookings->advance($booking, $request->user(), BookingStatus::from($data['status']));
        } catch (BookingRuleException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Progres layanan diperbarui.');
    }

    private function owns(Request $request, Booking $booking): void
    {
        abort_unless($booking->provider_id === $request->user()->provider?->id, 403);
        abort_unless($request->user()->provider?->isApproved(), 403, 'Akun mitra belum disetujui.');
    }
}
