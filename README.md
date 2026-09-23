# Andallo

Marketplace jasa yang menghubungkan pelanggan dengan UMKM penyedia jasa lokal. Pelanggan mencari dan membandingkan jasa, memesan jadwal, membayar dengan transfer manual, lalu memantau status. Penyedia mengelola layanan dan jadwal. Admin memverifikasi mitra, pembayaran, pembatalan, dan refund.

Antarmuka menggunakan Bahasa Indonesia. Status pesanan disimpan di database. WhatsApp dipakai untuk komunikasi, dengan mode simulasi bila kredensial Business API belum diisi.

## Menjalankan

Butuh PHP 8.3, Composer, dan MySQL.

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve --host=127.0.0.1 --port=43123
```

Buka http://127.0.0.1:43123

Agar batas respons dua menit dan batas bayar diproses tanpa menunggu kunjungan halaman, jalankan juga:

```bash
php artisan schedule:work
```

Perintah `php artisan bookings:sweep` bisa dijalankan manual. Aplikasi juga memeriksa kedaluwarsa paling banyak sekali setiap 15 detik saat ada permintaan web.

## Akun demo

Seeder demo hanya berjalan saat `APP_ENV=local`. Jangan memakai akun ini di produksi.

| Peran | Email | Kata sandi |
| --- | --- | --- |
| Admin | admin@andallo.test | AndalloDemo! |
| Pelanggan | pelanggan@andallo.test | AndalloDemo! |
| Mitra (Sari Clean) | mitra@andallo.test | AndalloDemo! |

Mitra contoh lain memakai pola `mitra.{nama-usaha}@andallo.test` dengan kata sandi yang sama, hanya di lingkungan local.

## Uji

```bash
php artisan test
```

## Konfigurasi

Salin `.env.example`. Rekening, batas waktu, persen refund sebelum pekerjaan dimulai, dan teks kebijakan pembatalan bisa diubah dari dasbor admin.

WhatsApp:

- `WHATSAPP_DRIVER=simulation` mencatat pesan dan tidak mengirim. Antarmuka menyebutnya simulasi, bukan terkirim.
- `WHATSAPP_DRIVER=cloud_api` memakai WhatsApp Cloud API. Isi `WHATSAPP_CLOUD_TOKEN` dan `WHATSAPP_PHONE_NUMBER_ID`.
- Tautan `wa.me` selalu membutuhkan tombol kirim di WhatsApp. Membuka tautan tidak mengubah pesanan.

Pembayaran gateway belum aktif. `PAYMENT_WEBHOOK_SECRET` kosong membuat webhook menolak permintaan. Transfer manual adalah alur yang dipakai.

## Dokumen

Diagram relasi ada di `docs/erd.md`.
