@php $suffix = $suffix ?? 'lokasi'; @endphp
<details class="location-picker">
    <summary>
        <x-icon name="pin"/>
        Lokasi Anda: {{ $currentLocation['label'] }}
        <span aria-hidden="true">▾</span>
    </summary>
    <div class="location-panel">
        <p>
            @if($currentLocation['source'] === 'device')
                Lokasi dari perangkat. Bukan pelacakan perjalanan.
            @elseif($currentLocation['lat'] === null)
                Koordinat belum ada, jadi jarak tidak ditampilkan.
            @else
                Lokasi pilihan, bukan GPS langsung. Izin perangkat hanya diminta jika Anda menekan tombol di bawah.
            @endif
        </p>
        <form method="POST" action="{{ route('location.update') }}" class="stack js-lokasi">
            @csrf
            <input type="hidden" name="source" value="manual" class="js-source">
            <input type="hidden" name="lat" class="js-lat">
            <input type="hidden" name="lng" class="js-lng">
            <input type="hidden" name="label" class="js-label">
            <label for="kota-{{ $suffix }}">Kota</label>
            <select id="kota-{{ $suffix }}" name="city">
                @foreach(config('cities') as $city)
                    <option value="{{ $city['name'] }}" @selected($currentLocation['label'] === $city['name'])>{{ $city['name'] }}</option>
                @endforeach
            </select>
            <button class="btn btn-andallo" type="submit">Pakai kota ini</button>
            <button class="btn btn-outline js-gps" type="button">Gunakan lokasi perangkat</button>
            <button class="btn btn-outline" type="submit" data-clear>Hapus lokasi</button>
            <p class="muted js-pesan" hidden></p>
        </form>
    </div>
</details>
