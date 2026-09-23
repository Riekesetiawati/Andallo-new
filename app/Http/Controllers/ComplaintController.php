<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Complaint;
use Illuminate\Http\Request;

class ComplaintController extends Controller
{
    public function store(Request $request, Booking $booking)
    {
        abort_unless($booking->customer_id === $request->user()->id, 403);
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:160'],
            'description' => ['required', 'string', 'max:2000'],
        ]);

        Complaint::query()->create([
            'booking_id' => $booking->id,
            'user_id' => $request->user()->id,
            'subject' => $data['subject'],
            'description' => $data['description'],
            'status' => 'open',
        ]);

        return back()->with('success', 'Komplain terkirim dan akan ditinjau admin.');
    }
}
