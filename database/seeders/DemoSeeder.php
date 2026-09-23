<?php

namespace Database\Seeders;

use App\Enums\ProviderStatus;
use App\Enums\UserRole;
use App\Models\AvailabilitySlot;
use App\Models\Category;
use App\Models\Portfolio;
use App\Models\Profile;
use App\Models\Provider;
use App\Models\Review;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            $this->command?->warn('DemoSeeder dilewati karena APP_ENV bukan local.');

            return;
        }

        $password = 'AndalloDemo!';

        $admin = User::query()->updateOrCreate(['email' => 'admin@andallo.test'], [
            'name' => 'Admin Andallo',
            'phone' => '6281110000001',
            'password' => $password,
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);
        Profile::query()->updateOrCreate(['user_id' => $admin->id], ['description' => 'Akun admin lokal.', 'provider_id' => null]);

        $customer = User::query()->updateOrCreate(['email' => 'pelanggan@andallo.test'], [
            'name' => 'Sinta Pelanggan',
            'phone' => '6281200000001',
            'password' => $password,
            'role' => UserRole::Customer,
            'address' => 'Jl. Demo No. 8, Jakarta Selatan',
            'is_active' => true,
        ]);
        Profile::query()->updateOrCreate(['user_id' => $customer->id], ['description' => 'Akun pelanggan demo.', 'provider_id' => null]);

        $catalog = [
            'kecantikan' => [
                'image' => 'images/services/kecantikan.png',
                'unit' => 'per sesi',
                'providers' => [
                    ['Melati Beauty', 'Perawatan wajah rumahan', 180000, 4.7, 40],
                    ['Cantik Rumah', 'Creambath dan tata rambut', 150000, 4.6, 28],
                    ['Nara Salon', 'Potong rambut keluarga', 75000, 4.8, 55],
                    ['Kinasih Care', 'Perawatan kuku', 90000, 4.5, 18],
                    ['Pesona Spa', 'Pijat relaksasi wajah', 200000, 4.9, 33],
                ],
            ],
            'kebersihan' => [
                'image' => 'images/services/bersih-rumah.png',
                'unit' => 'per kunjungan',
                'providers' => [
                    ['Sari Clean', 'Bersih Rumah', 120000, 4.9, 120, true, -1.2, 0],
                    ['Berkah Sapu', 'Bersih kos dan apartemen', 100000, 4.6, 44, false, -0.4, 0.6],
                    ['Rumah Rapi', 'Setrika dan beres rumah', 90000, 4.7, 31, false, 0.3, -0.5],
                    ['Asri Home', 'Bersih dapur menyeluruh', 140000, 4.8, 22, false, -0.8, 0.8],
                    ['Laundry Kinanti', 'Cuci lipat kiloan', 8000, 4.5, 60, false, 0.2, 1.1, 'per kg', 'images/laundry-umkm.png'],
                ],
            ],
            'teknisi' => [
                'image' => 'images/services/servis-ac.png',
                'unit' => 'per kunjungan',
                'providers' => [
                    ['Dika Teknik', 'Servis AC', 85000, 4.8, 98, true, 0, 2.1],
                    ['Andi Servis', 'Cuci AC', 75000, 4.6, 40],
                    ['Cahaya Listrik', 'Perbaikan listrik rumah', 150000, 4.7, 27],
                    ['Bima AC', 'Isi freon AC', 200000, 4.5, 19],
                    ['Tukang Kita', 'Perbaikan pintu dan engsel', 125000, 4.8, 36],
                ],
            ],
            'kesehatan' => [
                'image' => 'images/services/kebugaran.png',
                'unit' => 'per sesi',
                'providers' => [
                    ['Sehat Langkah', 'Pijat relaksasi', 160000, 4.8, 41],
                    ['Bugar Kampung', 'Latihan kebugaran rumah', 130000, 4.7, 24],
                    ['Nadi Fit', 'Pendamping olahraga', 140000, 4.6, 18],
                    ['Lentera Yoga', 'Yoga pemula', 110000, 4.9, 29],
                    ['Prima Massage', 'Pijat punggung', 150000, 4.5, 21],
                ],
            ],
            'kursus' => [
                'image' => 'images/services/les-privat.png',
                'unit' => 'per sesi',
                'providers' => [
                    ['Mentari Edu', 'Les Privat', 75000, 4.9, 76, true, 1.5, 0],
                    ['Cerdas Pagi', 'Les matematika SMP', 80000, 4.7, 34],
                    ['Bimbel Pelita', 'Les bahasa Inggris', 90000, 4.8, 45],
                    ['Nada Musik', 'Les gitar pemula', 100000, 4.6, 16],
                    ['Aksara Les', 'Bimbingan membaca', 70000, 4.9, 22],
                ],
            ],
            'otomotif' => [
                'image' => 'images/services/otomotif.png',
                'unit' => 'per kunjungan',
                'providers' => [
                    ['Bengkel Jaya', 'Servis ringan motor', 50000, 4.7, 80],
                    ['Motor Rapi', 'Ganti oli motor', 45000, 4.6, 52],
                    ['Ban Setia', 'Tambal dan spooring', 70000, 4.5, 19],
                    ['Oli Segar', 'Servis berkala mobil', 350000, 4.8, 27],
                    ['Garasi Aman', 'Cek aki dan kelistrikan', 85000, 4.4, 14],
                ],
            ],
            'acara' => [
                'image' => 'images/services/foto-produk.png',
                'unit' => 'per sesi',
                'providers' => [
                    ['Raka Visual', 'Foto Produk', 150000, 4.8, 64, true, -2.4, 2.4],
                    ['Lensa Rumah', 'Foto keluarga', 400000, 4.7, 21],
                    ['Dokumenter Kecil', 'Dokumentasi arisan', 500000, 4.6, 12],
                    ['Panggung Akrab', 'Foto acara komunitas', 450000, 4.8, 18],
                    ['Foto Hajatan', 'Dokumentasi syukuran', 750000, 4.9, 25],
                ],
            ],
            'digital' => [
                'image' => 'images/services/digital.png',
                'unit' => 'per item',
                'providers' => [
                    ['Piksel Lokal', 'Desain feed UMKM', 125000, 4.8, 39, false, 0.5, -1.2, 'per item', null, false],
                    ['Ketik Rapi', 'Ketik dan rapikan dokumen', 40000, 4.6, 20, false, null, null, 'per item', null, false],
                    ['Studio Warung', 'Edit foto produk', 60000, 4.7, 33, false, null, null, 'per item', null, false],
                    ['Desain Sebelah', 'Logo sederhana', 250000, 4.9, 17, false, null, null, 'per item', null, false],
                    ['Web Kampung', 'Halaman profil usaha', 450000, 4.5, 11, false, null, null, 'per item', null, false],
                ],
            ],
        ];

        $cities = config('cities');
        $i = 0;

        foreach ($catalog as $slug => $group) {
            $category = Category::query()->where('slug', $slug)->firstOrFail();
            foreach ($group['providers'] as $row) {
                [$business, $serviceName, $price, $rating, $count] = array_slice($row, 0, 5);
                $featured = $row[5] ?? false;
                $north = $row[6] ?? null;
                $east = $row[7] ?? null;
                $unit = $row[8] ?? $group['unit'];
                $image = $row[9] ?? $group['image'];
                $visit = $row[10] ?? true;

                $city = $cities[$i % count($cities)];
                if ($north !== null) {
                    $lat = -6.2615 + ($north / 111.32);
                    $lng = 106.8106 + ($east / (111.32 * cos(deg2rad(-6.2615))));
                    $cityName = 'Jakarta Selatan';
                } else {
                    $lat = $city['lat'] + (($i % 5) - 2) * 0.01;
                    $lng = $city['lng'] + (($i % 3) - 1) * 0.01;
                    $cityName = $city['name'];
                }

                $email = $business === 'Sari Clean'
                    ? 'mitra@andallo.test'
                    : 'mitra.'.Str::slug($business).'@andallo.test';

                $user = User::query()->updateOrCreate(['email' => $email], [
                    'name' => 'Pemilik '.$business,
                    'phone' => '628110000'.str_pad((string) (1000 + $i), 4, '0', STR_PAD_LEFT),
                    'password' => $password,
                    'role' => UserRole::Provider,
                    'address' => 'Alamat usaha demo, '.$cityName,
                    'is_active' => true,
                ]);

                $provider = Provider::query()->updateOrCreate(['user_id' => $user->id], [
                    'business_name' => $business,
                    'whatsapp' => $user->phone,
                    'description' => $business.' adalah usaha jasa lokal di '.$cityName.'. Data ini contoh untuk lingkungan pengembangan.',
                    'portfolio_summary' => 'Portofolio contoh, bukan pekerjaan pelanggan nyata.',
                    'address' => 'Jl. Usaha Demo No. '.($i + 1).', '.$cityName,
                    'city' => $cityName,
                    'district' => 'Kelurahan Demo',
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'service_area' => $cityName,
                    'verification_status' => ProviderStatus::Approved,
                    'is_active' => true,
                    'is_demo' => true,
                    'demo_rating' => $rating,
                    'demo_review_count' => $count,
                    'verified_at' => now(),
                    'verified_by' => $admin->id,
                ]);

                Profile::query()->updateOrCreate(['provider_id' => $provider->id], [
                    'user_id' => null,
                    'description' => $provider->description,
                ]);

                $service = Service::query()->updateOrCreate(
                    ['provider_id' => $provider->id, 'slug' => Str::slug($serviceName)],
                    [
                        'category_id' => $category->id,
                        'name' => $serviceName,
                        'description' => 'Layanan '.$serviceName.' dari '.$business.'. Cocok untuk kebutuhan rumah tangga dan usaha kecil di sekitar '.$cityName.'.',
                        'price' => $price,
                        'price_unit' => $unit,
                        'duration_minutes' => [60, 90, 120][$i % 3],
                        'includes' => 'Pengerjaan standar, peralatan dasar penyedia, dan laporan singkat setelah selesai.',
                        'excludes' => 'Material khusus, biaya parkir, dan pekerjaan di luar cakupan yang disepakati.',
                        'requires_visit' => $visit,
                        'image_path' => $image,
                        'is_active' => true,
                        'is_featured' => $featured,
                        'is_demo' => true,
                        'demo_rating' => $rating,
                        'demo_review_count' => $count,
                    ],
                );

                Portfolio::query()->updateOrCreate(
                    ['provider_id' => $provider->id, 'title' => 'Contoh pekerjaan '.$serviceName],
                    [
                        'service_id' => $service->id,
                        'description' => 'Foto contoh untuk demo. Bukan portofolio pelanggan nyata.',
                        'image_path' => $image,
                        'is_demo' => true,
                    ],
                );

                $provider->availabilitySlots()->delete();
                $startHour = [8, 9, 10, 7, 13][$i % 5];
                $endHour = $startHour + 8;
                for ($day = 1; $day <= 6; $day++) {
                    AvailabilitySlot::query()->create([
                        'provider_id' => $provider->id,
                        'weekday' => $day,
                        'start_time' => sprintf('%02d:00:00', $startHour),
                        'end_time' => sprintf('%02d:00:00', min($endHour, 20)),
                    ]);
                }

                $i++;
            }
        }

        $this->seedDemoReviews($customer);
    }

    private function seedDemoReviews(User $customer): void
    {
        Service::query()->where('is_featured', true)->each(function (Service $service) use ($customer) {
            Review::query()->updateOrCreate(
                ['service_id' => $service->id, 'is_demo' => true],
                [
                    'booking_id' => null,
                    'customer_id' => $customer->id,
                    'provider_id' => $service->provider_id,
                    'rating' => (int) round((float) $service->demo_rating),
                    'comment' => 'Contoh ulasan demo untuk tampilan katalog. Ini bukan pengalaman pelanggan nyata.',
                ],
            );
        });
    }
}
