@extends('layouts.public')
@section('title', 'Jadwal')
@section('content')
<h1>Jadwal mingguan</h1>
<p class="muted">Satu penyedia melayani satu pesanan pada satu interval waktu. Slot yang sudah dipesan tidak muncul untuk pelanggan.</p>
<form method="POST" action="{{ route('provider.schedule.update') }}" class="panel stack">
    @csrf @method('PUT')
    @foreach(['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'] as $index => $label)
        @php $slot = $slots[$index] ?? null; @endphp
        <div class="inline">
            <label class="inline"><input type="checkbox" name="days[{{ $index }}][open]" value="1" @checked($slot)> {{ $label }}</label>
            <input type="time" name="days[{{ $index }}][start]" value="{{ $slot ? substr((string)$slot->start_time,0,5) : '08:00' }}">
            <input type="time" name="days[{{ $index }}][end]" value="{{ $slot ? substr((string)$slot->end_time,0,5) : '17:00' }}">
        </div>
    @endforeach
    <button class="btn btn-andallo" type="submit">Simpan jadwal</button>
</form>
@endsection
