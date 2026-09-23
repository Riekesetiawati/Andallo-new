<?php

return [
    'whatsapp' => [
        'driver' => env('WHATSAPP_DRIVER', 'simulation'),
        'token' => env('WHATSAPP_CLOUD_TOKEN'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'api_version' => env('WHATSAPP_API_VERSION', 'v21.0'),
    ],

    'payment' => [
        'driver' => env('PAYMENT_DRIVER', 'manual'),
        'webhook_secret' => env('PAYMENT_WEBHOOK_SECRET'),
    ],

    'defaults' => [
        'require_admin_review' => env('ANDALLO_REQUIRE_ADMIN_REVIEW', true) ? '1' : '0',
        'provider_response_minutes' => (string) env('ANDALLO_PROVIDER_RESPONSE_MINUTES', 2),
        'payment_window_minutes' => (string) env('ANDALLO_PAYMENT_WINDOW_MINUTES', 1440),
        'payment_review_hours' => (string) env('ANDALLO_PAYMENT_REVIEW_HOURS', 24),
        'refund_before_start_percent' => (string) env('ANDALLO_REFUND_BEFORE_START_PERCENT', 100),
        'bank_name' => 'Bank Mandiri',
        'bank_account_number' => '1370012345678',
        'bank_account_holder' => 'PT Andallo Indonesia',
        'support_email' => 'bantuan@andallo.test',
        'support_phone' => '6281110000001',
        'support_hours' => 'Setiap hari, 08.00–20.00 WIB',
        'cancellation_policy' => 'Sebelum penyedia menerima pesanan, pelanggan dapat membatalkan tanpa biaya. Setelah diterima tetapi belum dibayar, pembatalan melepaskan jadwal tanpa tagihan. Jika bukti transfer sedang diperiksa, pembatalan ditahan sampai admin memastikan dana sudah masuk atau belum. Setelah pembayaran terverifikasi dan sebelum pekerjaan dimulai, pengembalian dana mengikuti persentase yang diatur admin. Saat pekerjaan sudah berjalan, pembatalan ditinjau admin dan baru selesai setelah pelanggan menyetujui hasil peninjauan. Pesanan selesai tidak dibatalkan langsung; gunakan pengajuan komplain. Status dana dikembalikan hanya berubah setelah pengembalian dikonfirmasi.',
    ],

    'faqs' => [
        [
            'q' => 'Bagaimana cara memesan jasa di Andallo?',
            'a' => 'Cari jasa, bandingkan harga dan cakupan, lalu pilih jadwal yang masih kosong. Setelah Anda mengonfirmasi, pesanan tercatat di Andallo. Penyedia menerima atau menolak melalui halaman Andallo, bukan hanya lewat chat.',
        ],
        [
            'q' => 'Apakah membuka WhatsApp berarti pesanan sudah diterima?',
            'a' => 'Tidak. WhatsApp dipakai untuk komunikasi. Status pesanan hanya berubah di Andallo setelah penyedia, pelanggan, atau admin melakukan tindakan di halaman yang sesuai. Jika yang terbuka adalah tautan wa.me, Anda masih perlu menekan kirim di WhatsApp.',
        ],
        [
            'q' => 'Bagaimana pembayaran dilakukan?',
            'a' => 'Untuk saat ini pembayaran memakai transfer manual ke rekening yang ditampilkan di halaman pesanan. Unggah bukti transfer, lalu tunggu admin memverifikasi dana. Bukti yang diunggah belum berarti pembayaran berhasil.',
        ],
        [
            'q' => 'Berapa lama penyedia harus merespons?',
            'a' => 'Batas respons penyedia dapat diatur admin. Nilai bawaan adalah dua menit sejak permintaan diteruskan. Jika terlewati, pesanan menjadi kedaluwarsa dan jadwal dilepas.',
        ],
        [
            'q' => 'Apakah jarak yang tampil adalah jarak perjalanan?',
            'a' => 'Bukan. Angka jarak adalah perkiraan garis lurus dari koordinat lokasi pilihan Anda ke koordinat usaha. Jika koordinat belum ada, jarak tidak ditampilkan.',
        ],
        [
            'q' => 'Kapan saya bisa memberi ulasan?',
            'a' => 'Setelah pesanan berstatus selesai dan Anda mengonfirmasi pekerjaan. Satu pesanan hanya dapat menerima satu ulasan dari pelanggan pemilik pesanan.',
        ],
    ],
];
