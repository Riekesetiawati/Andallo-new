@extends('layouts.admin')
@section('content')
<h1>Kategori</h1>
<form method="POST" action="{{ route('admin.categories.store') }}" class="panel inline mb-3">
    @csrf
    <input name="name" placeholder="Nama" required>
    <input name="icon" placeholder="ikon: broom" required>
    <button class="btn btn-andallo" type="submit">Tambah</button>
</form>
@foreach($categories as $category)
<form method="POST" action="{{ route('admin.categories.update', $category) }}" class="panel inline mb-2">
    @csrf @method('PUT')
    <input name="name" value="{{ $category->name }}" required>
    <input name="icon" value="{{ $category->icon }}" required>
    <label class="inline"><input type="checkbox" name="is_active" value="1" @checked($category->is_active)> Aktif</label>
    <button class="btn btn-outline" type="submit">Simpan</button>
</form>
@endforeach
@endsection
