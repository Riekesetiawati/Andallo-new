@extends('layouts.public')
@section('title', $booking->code)
@php
    $user = auth()->user();
    $isCustomer = $user->id === $booking->customer_id;
    $isProvider = $user->isProvider() && $user->provider && $user->provider->id === $booking->provider_id;
    $wa = whatsapp_link($booking->provider->whatsapp, "Halo, saya ingin bertanya tentang pesanan {$booking->code} ({$booking->service_name_snapshot}) pada {$booking->scheduleLabel()}.");
@endphp
@section('content')
<div class="inline" style="justify-content:space-between">
    <h1>Pesanan {{ $booking->code }}</h1>
    <span class="badge {{ $booking->status->tone() }}">{{ $booking->status->label() }}</span>
</div>
@if($booking->whatsapp_dispatch_status === 'simulated')
    <p class="note">Notifikasi WhatsApp terakhir berstatus simulasi. Pesan dicatat di Andallo dan belum terkirim ke WhatsApp.</p>
@elseif($booking->whatsapp_dispatch_status === 'failed')
    <p class="note">Pengiriman WhatsApp gagal. Ini bukan kesalahan penyedia. Admin dapat mencoba ulang dari log notifikasi.</p>
@endif
<div class="detail">
    <div class="stack">
        <article class="panel">
            <h2>Layanan</h2>
            <p><strong>{{ $booking->service_name_snapshot }}</strong><br>{{ $booking->provider->business_name }}</p>
            <p>{{ $booking->scheduleLabel() }}</p>
            <p><strong>Kota:</strong> {{ $booking->city }}</p>
            @if($isCustomer || $isProvider || $user->isAdmin())
                <p><strong>Alamat layanan:</strong> {{ $booking->address }}</p>
            @endif
            @if($booking->customer_notes)<p><strong>Catatan:</strong> {{ $booking->customer_notes }}</p>@endif
            <p><strong>Termasuk:</strong> {{ $booking->includes_snapshot }}</p>
            <p><strong>Tidak termasuk:</strong> {{ $booking->excludes_snapshot ?: '—' }}</p>
        </article>
        <article class="panel">
            <h2>Biaya</h2>
            <p>Harga saat dipesan: {{ rupiah($booking->price_snapshot) }} {{ $booking->price_unit_snapshot }}</p>
            <p class="price">Total {{ rupiah($booking->total) }}</p>
            <p>Pembayaran: <strong>{{ $booking->payment_status->label() }}</strong></p>
            <p>Refund: <strong>{{ $booking->refund_status->label() }}</strong></p>
            @if($booking->status === \App\Enums\BookingStatus::AwaitingPayment)
                <p>Batas bayar: {{ $booking->payment_deadline?->translatedFormat('d M Y H:i') }}</p>
                <p>Rekening {{ $bank['name'] }} · {{ $bank['number'] }} · a.n. {{ $bank['holder'] }}</p>
            @endif
            @if($booking->status === \App\Enums\BookingStatus::AwaitingProvider && $booking->provider_response_deadline)
                <p>Batas respons penyedia: {{ $booking->provider_response_deadline->translatedFormat('d M Y H:i') }}</p>
            @endif
        </article>
        <article class="panel">
            <h2>Timeline</h2>
            <ol class="timeline">
                @foreach($booking->histories as $history)
                    <li>
                        <strong>{{ \App\Enums\BookingStatus::from($history->to_status)->label() }}</strong>
                        <br><span class="muted">{{ $history->created_at->translatedFormat('d M Y H:i') }} · {{ $history->actor?->name ?? 'Sistem' }}</span>
                        @if($history->note)<br>{{ $history->note }}@endif
                    </li>
                @endforeach
            </ol>
        </article>
        @if($booking->cancellationRequests->isNotEmpty())
            <article class="panel">
                <h2>Pembatalan</h2>
                @foreach($booking->cancellationRequests as $item)
                    <p>{{ $item->statusLabel() }} — {{ $item->reason }}
                        @if($item->refund_estimate) · estimasi {{ rupiah($item->refund_estimate) }} @endif
                        @if($item->refund_amount) · usulan {{ rupiah($item->refund_amount) }} @endif
                        @if($item->admin_note)<br>{{ $item->admin_note }}@endif
                    </p>
                @endforeach
            </article>
        @endif
        @if($booking->refunds->isNotEmpty())
            <article class="panel">
                <h2>Pengembalian dana</h2>
                @foreach($booking->refunds as $refund)
                    <p>{{ rupiah($refund->amount) }} · {{ $refund->status->label() }} @if($refund->note)<br>{{ $refund->note }}@endif</p>
                @endforeach
            </article>
        @endif
    </div>
    <aside class="stack">
        <article class="panel stack">
            <h2>Tindakan</h2>
            @if($isProvider && $booking->status === \App\Enums\BookingStatus::AwaitingProvider)
                <form method="POST" action="{{ route('provider.orders.accept', $booking) }}" data-loading>@csrf<button class="btn btn-andallo" type="submit">Terima pesanan</button></form>
                <form method="POST" action="{{ route('provider.orders.reject', $booking) }}" data-loading>@csrf<label for="reason-tolak">Alasan penolakan</label><textarea id="reason-tolak" name="reason" required></textarea><button class="btn btn-danger" type="submit">Tolak</button></form>
            @endif
            @if($isProvider && $booking->payment_status === \App\Enums\PaymentStatus::Paid)
                @if($booking->status === \App\Enums\BookingStatus::Confirmed && $booking->requires_visit_snapshot)
                    <form method="POST" action="{{ route('provider.orders.progress', $booking) }}">@csrf<input type="hidden" name="status" value="on_the_way"><button class="btn btn-andallo" type="submit">Menuju lokasi</button></form>
                @endif
                @if(in_array($booking->status, [\App\Enums\BookingStatus::Confirmed, \App\Enums\BookingStatus::OnTheWay], true))
                    <form method="POST" action="{{ route('provider.orders.progress', $booking) }}">@csrf<input type="hidden" name="status" value="in_progress"><button class="btn btn-andallo" type="submit">Sedang dikerjakan</button></form>
                @endif
                @if($booking->status === \App\Enums\BookingStatus::InProgress)
                    <form method="POST" action="{{ route('provider.orders.progress', $booking) }}">@csrf<input type="hidden" name="status" value="awaiting_customer"><button class="btn btn-andallo" type="submit">Minta konfirmasi pelanggan</button></form>
                @endif
            @endif
            @if($isCustomer && $booking->status === \App\Enums\BookingStatus::AwaitingPayment && $booking->payment && in_array($booking->payment->status, [\App\Enums\PaymentStatus::Unpaid, \App\Enums\PaymentStatus::Rejected], true))
                <form method="POST" action="{{ route('payments.upload', $booking) }}" enctype="multipart/form-data" data-loading>
                    @csrf
                    <label for="proof">Unggah bukti transfer</label>
                    <input id="proof" type="file" name="proof" accept="image/jpeg,image/png,image/webp,application/pdf" required>
                    <button class="btn btn-accent" type="submit">Kirim bukti</button>
                    @if($booking->payment->rejection_reason)<p>Alasan ditolak: {{ $booking->payment->rejection_reason }}</p>@endif
                </form>
            @endif
            @if($isCustomer && in_array($booking->status, [\App\Enums\BookingStatus::Expired, \App\Enums\BookingStatus::Cancelled], true) && $booking->payment)
                <form method="POST" action="{{ route('payments.upload', $booking) }}" enctype="multipart/form-data">
                    @csrf
                    <p>Jika Anda sudah mentransfer setelah pesanan tidak aktif, unggah bukti untuk antrean peninjauan pengembalian. Layanan tidak dijalankan.</p>
                    <input type="file" name="proof" accept="image/jpeg,image/png,image/webp,application/pdf" required>
                    <button class="btn btn-outline" type="submit">Unggah untuk ditinjau</button>
                </form>
            @endif
            @if($booking->payment?->proof_path && ($isCustomer || $isProvider || $user->isAdmin()))
                <a href="{{ route('payments.proof', $booking->payment) }}">Lihat bukti pembayaran</a>
            @endif
            @if($isCustomer && $booking->status === \App\Enums\BookingStatus::AwaitingCustomer)
                <form method="POST" action="{{ route('bookings.complete', $booking) }}" data-loading>@csrf<button class="btn btn-andallo" type="submit">Konfirmasi selesai</button></form>
            @endif
            @if($isCustomer && $booking->status === \App\Enums\BookingStatus::Completed && ! $booking->review)
                <form method="POST" action="{{ route('bookings.review', $booking) }}" class="stack">
                    @csrf
                    <label for="rating">Rating</label>
                    <select id="rating" name="rating" required>
                        @for($i = 5; $i >= 1; $i--)<option value="{{ $i }}">{{ $i }}</option>@endfor
                    </select>
                    <label for="comment">Ulasan</label>
                    <textarea id="comment" name="comment" rows="3"></textarea>
                    <button class="btn btn-andallo" type="submit">Kirim ulasan</button>
                </form>
            @endif
            @if($isCustomer && $booking->cancellationRequests->firstWhere('status', 'awaiting_customer'))
                <form method="POST" action="{{ route('bookings.settlement', $booking) }}">
                    @csrf
                    <p>Admin mengusulkan hasil pembatalan. Pesanan tetap berjalan sampai Anda menyetujui.</p>
                    <button class="btn btn-andallo" name="decision" value="accept" type="submit">Setujui hasil</button>
                    <button class="btn btn-outline" name="decision" value="reject" type="submit">Tidak setuju, buka komplain</button>
                </form>
            @endif
            @if($isCustomer && ! in_array($booking->status, [\App\Enums\BookingStatus::Completed, \App\Enums\BookingStatus::Cancelled, \App\Enums\BookingStatus::Rejected, \App\Enums\BookingStatus::Expired], true))
                <form method="POST" action="{{ route('bookings.cancel', $booking) }}" class="stack">
                    @csrf
                    <label for="reason-batal">Batalkan pesanan</label>
                    <textarea id="reason-batal" name="reason" required placeholder="Alasan pembatalan"></textarea>
                    <p class="muted">Jika menutup formulir ini tanpa mengirim, pesanan tetap di tahap sekarang. Estimasi pengembalian sebelum pekerjaan dimulai: {{ $percent }}%.</p>
                    <button class="btn btn-danger" type="submit">Kirim pembatalan</button>
                </form>
            @endif
            @if($isCustomer && $booking->status === \App\Enums\BookingStatus::Completed)
                <form method="POST" action="{{ route('complaints.store', $booking) }}" class="stack">
                    @csrf
                    <h3>Komplain</h3>
                    <input name="subject" placeholder="Subjek" required>
                    <textarea name="description" required placeholder="Ceritakan masalahnya"></textarea>
                    <button class="btn btn-outline" type="submit">Ajukan komplain</button>
                </form>
            @endif
            <a class="btn btn-outline" href="{{ $wa }}" target="_blank" rel="noopener">Hubungi via WhatsApp</a>
            <p class="muted">Anda perlu menekan kirim di WhatsApp. Klik ini tidak menandai pesan terkirim atau pesanan diterima.</p>
            <a href="{{ route('help') }}">Butuh bantuan?</a>
        </article>
    </aside>
</div>
@endsection
