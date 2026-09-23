@extends('layouts.public')
@section('title', 'Profil')
@section('content')
<h1>Profil</h1>
<form class="panel stack" method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" data-loading>
    @csrf
    @method('PUT')
    <div class="field"><label for="name">Nama</label><input id="name" name="name" value="{{ old('name', $user->name) }}" required></div>
    <div class="field"><label for="phone">Telepon</label><input id="phone" name="phone" value="{{ old('phone', $user->phone) }}" required></div>
    <div class="field"><label for="address">Alamat</label><textarea id="address" name="address" rows="3">{{ old('address', $user->address) }}</textarea></div>
    <div class="field"><label for="description">Deskripsi profil</label><textarea id="description" name="description" rows="3">{{ old('description', $profile->description) }}</textarea></div>
    <div class="field"><label for="photo">Foto profil</label><input id="photo" type="file" name="photo" accept="image/*"></div>
    <button class="btn btn-andallo" type="submit">Simpan profil</button>
</form>
@endsection
