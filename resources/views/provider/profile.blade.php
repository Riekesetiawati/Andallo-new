@extends('layouts.public')
@section('title', 'Profil usaha')
@section('content')
<h1>Profil usaha</h1>
<form method="POST" action="{{ route('provider.profile.update') }}" enctype="multipart/form-data" class="panel stack" data-loading>
    @csrf @method('PUT')
    <label>Nama usaha<input name="business_name" value="{{ old('business_name', $provider->business_name) }}" required></label>
    <label>WhatsApp<input name="whatsapp" value="{{ old('whatsapp', $provider->whatsapp) }}" required></label>
    <label>Deskripsi<textarea name="description" required>{{ old('description', $provider->description) }}</textarea></label>
    <label>Ringkasan portofolio<textarea name="portfolio_summary">{{ old('portfolio_summary', $provider->portfolio_summary) }}</textarea></label>
    <label>Alamat<input name="address" value="{{ old('address', $provider->address) }}" required></label>
    <label>Kota<input name="city" value="{{ old('city', $provider->city) }}" required></label>
    <label>Kecamatan<input name="district" value="{{ old('district', $provider->district) }}"></label>
    <label>Area layanan<input name="service_area" value="{{ old('service_area', $provider->service_area) }}" required></label>
    <label>Lintang<input name="latitude" value="{{ old('latitude', $provider->latitude) }}"></label>
    <label>Bujur<input name="longitude" value="{{ old('longitude', $provider->longitude) }}"></label>
    <label>Foto<input type="file" name="photo" accept="image/*"></label>
    <button class="btn btn-andallo" type="submit">Simpan</button>
</form>
<form method="POST" action="{{ route('provider.profile.submit') }}" class="mt-3">@csrf<button class="btn btn-accent" type="submit">Ajukan verifikasi</button></form>
@endsection
