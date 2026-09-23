@extends('layouts.admin')
@section('content')
<h1>Pengaturan</h1>
<form method="POST" action="{{ route('admin.settings.update') }}" class="panel stack">
    @csrf @method('PUT')
    <label class="inline"><input type="checkbox" name="require_admin_review" value="1" @checked($values['require_admin_review'] == '1')> Pemeriksaan admin sebelum diteruskan ke penyedia</label>
    <label>Batas respons penyedia (menit)<input name="provider_response_minutes" type="number" value="{{ $values['provider_response_minutes'] }}" required></label>
    <label>Batas pembayaran (menit)<input name="payment_window_minutes" type="number" value="{{ $values['payment_window_minutes'] }}" required></label>
    <label>Batas peninjauan bukti (jam)<input name="payment_review_hours" type="number" value="{{ $values['payment_review_hours'] }}" required></label>
    <label>Persen refund sebelum pekerjaan dimulai<input name="refund_before_start_percent" type="number" value="{{ $values['refund_before_start_percent'] }}" required></label>
    <label>Bank<input name="bank_name" value="{{ $values['bank_name'] }}" required></label>
    <label>Nomor rekening<input name="bank_account_number" value="{{ $values['bank_account_number'] }}" required></label>
    <label>Atas nama<input name="bank_account_holder" value="{{ $values['bank_account_holder'] }}" required></label>
    <label>Email bantuan<input name="support_email" value="{{ $values['support_email'] }}" required></label>
    <label>WhatsApp bantuan<input name="support_phone" value="{{ $values['support_phone'] }}" required></label>
    <label>Jam bantuan<input name="support_hours" value="{{ $values['support_hours'] }}" required></label>
    <label>Kebijakan pembatalan<textarea name="cancellation_policy" rows="6" required>{{ $values['cancellation_policy'] }}</textarea></label>
    <button class="btn btn-andallo" type="submit">Simpan pengaturan</button>
</form>
<p class="muted">WhatsApp Business API dan payment gateway diatur lewat variabel lingkungan, bukan dari formulir ini.</p>
@endsection
