@extends('layouts.public')
@section('title', 'Cari Jasa')
@section('content')
<h1>Cari Jasa</h1>
<p class="muted">Hasil diperbarui saat filter berubah. Jarak adalah perkiraan garis lurus jika koordinat tersedia.</p>
<div class="catalog">
    <form id="search-form" class="filters" method="get" action="{{ route('services.index') }}">
        <label for="q">Kata kunci</label>
        <input id="q" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Nama jasa, penyedia, atau kategori">
        <label for="category">Kategori</label>
        <select id="category" name="category">
            <option value="">Semua</option>
            @foreach($categories as $category)
                <option value="{{ $category->slug }}" @selected(($filters['category'] ?? '') === $category->slug)>{{ $category->name }}</option>
            @endforeach
        </select>
        <label for="city">Lokasi usaha</label>
        <select id="city" name="city">
            <option value="">Semua kota</option>
            @foreach($cities as $city)
                <option value="{{ $city }}" @selected(($filters['city'] ?? '') === $city)>{{ $city }}</option>
            @endforeach
        </select>
        <label for="min_price">Harga minimum</label>
        <input id="min_price" name="min_price" type="number" min="0" value="{{ $filters['min_price'] ?? '' }}">
        <label for="max_price">Harga maksimum</label>
        <input id="max_price" name="max_price" type="number" min="0" value="{{ $filters['max_price'] ?? '' }}">
        <label for="min_rating">Rating minimum</label>
        <select id="min_rating" name="min_rating">
            <option value="">Semua</option>
            @foreach([4, 3, 2] as $rate)
                <option value="{{ $rate }}" @selected((string)($filters['min_rating'] ?? '') === (string)$rate)>{{ $rate }}+</option>
            @endforeach
        </select>
        <label for="sort">Urutkan</label>
        <select id="sort" name="sort">
            <option value="rating" @selected(($filters['sort'] ?? 'rating') === 'rating')>Rating</option>
            <option value="price_asc" @selected(($filters['sort'] ?? '') === 'price_asc')>Harga terendah</option>
            <option value="price_desc" @selected(($filters['sort'] ?? '') === 'price_desc')>Harga tertinggi</option>
            <option value="distance" @selected(($filters['sort'] ?? '') === 'distance')>Jarak perkiraan</option>
        </select>
        <div class="inline mt-3">
            <button class="btn btn-andallo" type="submit">Terapkan</button>
            <button class="btn btn-outline" type="button" id="reset-filter">Reset filter</button>
        </div>
    </form>
    <div>
        @if($location['lat'] === null)
            <p class="note">Lokasi belum memiliki koordinat. Jarak tidak ditampilkan dan pengurutan jarak tidak memakai angka palsu.</p>
        @endif
        <div id="search-results" class="results" aria-live="polite">
            @include('services._results')
        </div>
    </div>
</div>
@endsection
