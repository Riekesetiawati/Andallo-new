<?php

namespace App\Http\Controllers;

use App\Exceptions\BookingRuleException;
use App\Models\ActionToken;
use App\Services\BookingService;
use Illuminate\Http\Request;

class ActionTokenController extends Controller
{
    public function show(string $token)
    {
        $record = $this->find($token);
        $booking = $record->booking()->with(['provider', 'service'])->first();

        return view('actions.show', [
            'token' => $token,
            'record' => $record,
            'booking' => $booking,
            'expired' => $record->expires_at->isPast() || $record->used_at,
        ]);
    }

    public function store(Request $request, string $token, BookingService $bookings)
    {
        $data = $request->validate([
            'decision' => ['required', 'in:accept,reject'],
            'reason' => ['required_if:decision,reject', 'nullable', 'string', 'max:500'],
        ]);

        $record = $this->find($token);
        if ($record->used_at || $record->expires_at->isPast()) {
            return back()->with('error', 'Tautan ini sudah tidak berlaku.');
        }

        $booking = $record->booking()->with('provider.user')->firstOrFail();
        $actor = $record->user;

        try {
            if ($data['decision'] === 'accept') {
                $bookings->accept($booking, $actor);
                $message = 'Pesanan diterima. Pelanggan mendapat tagihan. Ini tercatat di Andallo, bukan karena tautan WhatsApp dibuka.';
            } else {
                $bookings->rejectByProvider($booking, $actor, $data['reason'] ?? 'Ditolak penyedia.');
                $message = 'Pesanan ditolak dan jadwal dilepas.';
            }
            $record->used_at = now();
            $record->save();
        } catch (BookingRuleException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', $message);
    }

    private function find(string $token): ActionToken
    {
        $record = ActionToken::query()->where('token_hash', hash('sha256', $token))->first();
        abort_unless($record && $record->action === 'provider_response', 404);

        return $record;
    }
}
