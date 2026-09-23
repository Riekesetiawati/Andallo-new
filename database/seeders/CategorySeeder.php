<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['Kecantikan & Perawatan', 'kecantikan', 'lotus', 'Perawatan diri di studio atau kunjungan rumah.'],
            ['Kebersihan & Rumah Tangga', 'kebersihan', 'broom', 'Bersih rumah, laundry, dan perawatan rumah.'],
            ['Teknisi & Perbaikan', 'teknisi', 'tools', 'Servis AC, listrik, dan perbaikan rumah.'],
            ['Kesehatan & Kebugaran', 'kesehatan', 'heart', 'Pijat, yoga, dan pendamping kebugaran.'],
            ['Kursus & Les', 'kursus', 'graduation', 'Les privat dan kursus keterampilan.'],
            ['Otomotif', 'otomotif', 'car', 'Servis motor dan mobil di bengkel lokal.'],
            ['Acara & Fotografi', 'acara', 'camera', 'Foto produk, dokumentasi, dan acara kecil.'],
            ['Jasa Digital', 'digital', 'laptop', 'Desain, ketik, dan bantuan digital UMKM.'],
        ];

        foreach ($categories as $index => [$name, $slug, $icon, $description]) {
            Category::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'icon' => $icon,
                    'description' => $description,
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ],
            );
        }
    }
}
