# ERD Andallo

Autentikasi memakai satu tabel `users` dengan kolom `role` (`customer`, `provider`, `admin`). Ini memetakan entitas Admin, Pengguna, dan Penyedia Jasa pada ERD tanpa sistem login kedua. Nama, email, kata sandi, telepon, dan alamat akun ada di `users`. Profil usaha ada di `providers`.

```mermaid
erDiagram
    USERS ||--o| PROFILES : "pelanggan memiliki"
    USERS ||--o| PROVIDERS : "akun mitra"
    PROVIDERS ||--o| PROFILES : "usaha memiliki"
    PROVIDERS ||--o{ SERVICES : "menawarkan"
    CATEGORIES ||--o{ SERVICES : "mengelompokkan"
    PROVIDERS ||--o{ PORTFOLIOS : "menampilkan"
    PROVIDERS ||--o{ AVAILABILITY_SLOTS : "menjadwalkan"
    USERS ||--o{ BOOKINGS : "memesan"
    SERVICES ||--o{ BOOKINGS : "dipesan"
    PROVIDERS ||--o{ BOOKINGS : "mengerjakan"
    BOOKINGS ||--o{ BOOKING_STATUS_HISTORIES : "mencatat"
    BOOKINGS ||--o| PAYMENTS : "memiliki"
    USERS ||--o{ PAYMENTS : "memverifikasi"
    PAYMENTS ||--o{ PAYMENT_PROOFS : "menyimpan"
    BOOKINGS ||--o{ CANCELLATION_REQUESTS : "diajukan"
    BOOKINGS ||--o{ REFUNDS : "menghasilkan"
    PAYMENTS ||--o{ REFUNDS : "dikembalikan"
    BOOKINGS ||--o| REVIEWS : "dinilai"
    USERS ||--o{ REVIEWS : "menulis"
    BOOKINGS ||--o{ COMPLAINTS : "dilaporkan"
    USERS ||--o{ NOTIFICATIONS : "menerima"
    BOOKINGS ||--o{ NOTIFICATION_LOGS : "mengirim"
    BOOKINGS ||--o| PROVIDER_SLOT_RESERVATIONS : "menahan"

    USERS {
        bigint id PK
        string name
        string email UK
        string password
        string phone
        string role
        text address
        boolean is_active
    }
    PROFILES {
        bigint id PK
        bigint user_id FK "UK, nullable"
        bigint provider_id FK "UK, nullable"
        text description
        string photo_path
    }
    PROVIDERS {
        bigint id PK
        bigint user_id FK "UK"
        string business_name
        string whatsapp
        string city
        decimal latitude
        decimal longitude
        string verification_status
    }
    CATEGORIES {
        bigint id PK
        string name
        string slug UK
    }
    SERVICES {
        bigint id PK
        bigint provider_id FK
        bigint category_id FK
        string name
        decimal price
        string price_unit
    }
    BOOKINGS {
        bigint id PK
        string code UK
        bigint customer_id FK
        bigint provider_id FK
        bigint service_id FK
        decimal total
        string status
        string payment_status
        string refund_status
    }
    PAYMENTS {
        bigint id PK
        bigint booking_id FK "UK"
        decimal amount
        string status
        bigint verified_by FK "nullable, users admin"
        string proof_path
    }
    REFUNDS {
        bigint id PK
        bigint booking_id FK
        bigint payment_id FK
        decimal amount
        string status
        bigint responsible_admin_id FK
    }
    REVIEWS {
        bigint id PK
        bigint booking_id FK "UK, nullable untuk contoh demo"
        bigint customer_id FK
        int rating
        boolean is_demo
    }
```

Kardinalitas yang diterapkan:

- Pengguna pelanggan ke profil: satu ke satu lewat `profiles.user_id`.
- Penyedia ke profil: satu ke satu lewat `profiles.provider_id`.
- Tepat satu pemilik profil diisi. Di MySQL ini dijaga constraint `profiles_one_owner`, di aplikasi dijaga saat menyimpan model.
- Penyedia ke jasa: satu ke banyak.
- Kategori ke jasa: satu ke banyak.
- Pelanggan ke pemesanan: satu ke banyak.
- Jasa ke pemesanan: satu ke banyak.
- Pemesanan ke pembayaran: satu ke satu. Pembayaran dibuat saat penyedia menerima.
- Admin ke pembayaran: satu ke banyak lewat `payments.verified_by`.
- Pemesanan selesai ke ulasan pelanggan: satu ke satu.
- Riwayat transaksi tidak dihapus lewat cascade. Akun dan jasa yang sudah bertransaksi dinonaktifkan atau soft delete.
