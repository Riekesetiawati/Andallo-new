<?php

namespace App\Http\Controllers;

use App\Exceptions\BookingRuleException;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends Controller
{
    public function upload(Request $request, Booking $booking, PaymentService $payments)
    {
        abort_unless($booking->customer_id === $request->user()->id, 403);
        $data = $request->validate([
            'proof' => ['required', 'file', 'max:2048'],
        ]);

        try {
            $payments->uploadProof($booking, $request->user(), $data['proof']);
        } catch (BookingRuleException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Bukti terunggah dan menunggu verifikasi admin. Ini belum berarti pembayaran berhasil.');
    }

    public function proof(Request $request, Payment $payment): Response
    {
        $booking = $payment->booking()->with('provider')->firstOrFail();
        abort_unless($booking->involves($request->user()), 403);
        abort_unless($payment->proof_path && Storage::disk('local')->exists($payment->proof_path), 404);

        return Storage::disk('local')->response($payment->proof_path, $payment->proof_original_name);
    }

    public function webhook(Request $request, PaymentService $payments)
    {
        $secret = config('andallo.payment.webhook_secret');
        if (! $secret) {
            return response()->json(['message' => 'Webhook pembayaran belum dikonfigurasi.'], 503);
        }

        $signature = (string) $request->header('X-Andallo-Signature');
        $expected = hash_hmac('sha256', $request->getContent(), $secret);
        if (! hash_equals($expected, $signature)) {
            return response()->json(['message' => 'Tanda tangan webhook tidak valid.'], 401);
        }

        $payload = $request->validate([
            'booking_code' => ['required', 'string'],
            'status' => ['required', 'in:paid,failed'],
        ]);

        $booking = Booking::query()->where('code', $payload['booking_code'])->first();
        if (! $booking) {
            return response()->json(['message' => 'Pesanan tidak ditemukan.'], 404);
        }

        if ($payload['status'] === 'paid' && $booking->payment && $booking->payment->status->value === 'under_review') {
            $admin = $request->user() && $request->user()->isAdmin()
                ? $request->user()
                : \App\Models\User::query()->where('role', 'admin')->first();
            if ($admin) {
                $payments->approve($booking, $admin, 'Diverifikasi dari webhook pembayaran.');
            }
        }

        return response()->json(['message' => 'Webhook diterima.']);
    }

    public function gatewayReturn()
    {
        return redirect()->route('bookings.index')->with('success', 'Anda kembali dari halaman pembayaran. Status baru berubah setelah verifikasi server, bukan dari halaman ini.');
    }
}
