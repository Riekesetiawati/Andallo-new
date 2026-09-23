@extends('layouts.public')
@section('title', 'Layanan mitra')
@section('content')
<h1>Layanan</h1>
<form method="POST" action="{{ route('provider.services.store') }}" enctype="multipart/form-data" class="panel stack mb-4">
    @csrf
    <h2>Layanan baru</h2>
    <label>Kategori<select name="category_id" required>@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select></label>
    <label>Nama<input name="name" required></label>
    <label>Deskripsi<textarea name="description" required></textarea></label>
    <label>Harga<input name="price" type="number" min="0" required></label>
    <label>Satuan<select name="price_unit">@foreach(['per jam','per kunjungan','per sesi','per item','per kg'] as $unit)<option>{{ $unit }}</option>@endforeach</select></label>
    <label>Durasi menit<input name="duration_minutes" type="number" value="60" required></label>
    <label>Termasuk<textarea name="includes" required></textarea></label>
    <label>Tidak termasuk<textarea name="excludes"></textarea></label>
    <label class="inline"><input type="checkbox" name="requires_visit" value="1" checked> Perlu kunjungan</label>
    <label class="inline"><input type="checkbox" name="is_active" value="1" checked> Aktif</label>
    <label>Foto<input type="file" name="image" accept="image/*"></label>
    <button class="btn btn-andallo" type="submit">Tambah layanan</button>
</form>
<h2>Portofolio</h2>
<form method="POST" action="{{ route('provider.portfolios.store') }}" enctype="multipart/form-data" class="panel inline mb-4">
    @csrf
    <input name="title" placeholder="Judul" required>
    <input name="description" placeholder="Deskripsi">
    <input type="file" name="image" accept="image/*" required>
    <button class="btn btn-outline">Unggah</button>
</form>
@foreach($services as $service)
<article class="panel mb-2">
    <strong>{{ $service->name }}</strong> · {{ rupiah($service->price) }} {{ $service->price_unit }} · {{ $service->is_active ? 'Aktif' : 'Nonaktif' }}
    <form method="POST" action="{{ route('provider.services.update', $service) }}" class="stack mt-2">
        @csrf @method('PUT')
        <input type="hidden" name="category_id" value="{{ $service->category_id }}">
        <input name="name" value="{{ $service->name }}" required>
        <textarea name="description" required>{{ $service->description }}</textarea>
        <input name="price" type="number" value="{{ $service->price }}" required>
        <select name="price_unit">@foreach(['per jam','per kunjungan','per sesi','per item','per kg'] as $unit)<option @selected($service->price_unit === $unit)>{{ $unit }}</option>@endforeach</select>
        <input name="duration_minutes" type="number" value="{{ $service->duration_minutes }}">
        <textarea name="includes" required>{{ $service->includes }}</textarea>
        <textarea name="excludes">{{ $service->excludes }}</textarea>
        <label class="inline"><input type="checkbox" name="requires_visit" value="1" @checked($service->requires_visit)> Kunjungan</label>
        <label class="inline"><input type="checkbox" name="is_active" value="1" @checked($service->is_active)> Aktif</label>
        <button class="btn btn-outline" type="submit">Perbarui</button>
    </form>
    <form method="POST" action="{{ route('provider.services.destroy', $service) }}">@csrf @method('DELETE')<button class="btn btn-danger mt-2">Nonaktifkan / hapus</button></form>
</article>
@endforeach
@endsection
