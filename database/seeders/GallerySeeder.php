<?php

namespace Database\Seeders;

use App\Models\Gallery;
use Illuminate\Database\Seeder;

class GallerySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $galleries = [
            [
                'title' => 'Upacara Bendera',
                'image_url' => 'https://placehold.co/800x600/png',
                'image_name' => 'upacara-bendera.jpg',
                'description' => 'Kegiatan upacara bendera setiap hari Senin'
            ],
            [
                'title' => 'Kompetisi Sains',
                'image_url' => 'https://placehold.co/800x600/png',
                'image_name' => 'kompetisi-sains.jpg',
                'description' => 'Siswa mengikuti olimpiade sains nasional'
            ],
            [
                'title' => 'Pentas Seni Tahunan',
                'image_url' => 'https://placehold.co/800x600/png',
                'image_name' => 'pentas-seni.jpg',
                'description' => 'Pertunjukan bakat siswa dalam acara pentas seni'
            ],
            [
                'title' => 'Wisuda Angkatan 2023',
                'image_url' => 'https://placehold.co/800x600/png',
                'image_name' => 'wisuda-2023.jpg',
                'description' => 'Prosesi wisuda dan pelepasan siswa angkatan 2023'
            ],
            [
                'title' => 'Pertandingan Basket',
                'image_url' => 'https://placehold.co/800x600/png',
                'image_name' => 'basket.jpg',
                'description' => 'Tim basket sekolah dalam turnamen antar SMA'
            ],
            [
                'title' => 'Praktikum Biologi',
                'image_url' => 'https://placehold.co/800x600/png',
                'image_name' => 'praktikum-biologi.jpg',
                'description' => 'Siswa melakukan praktikum di laboratorium biologi'
            ],
            [
                'title' => 'Camping OSIS',
                'image_url' => 'https://placehold.co/800x600/png',
                'image_name' => 'camping-osis.jpg',
                'description' => 'Kegiatan camping dan leadership training OSIS'
            ],
            [
                'title' => 'Festival Budaya',
                'image_url' => 'https://placehold.co/800x600/png',
                'image_name' => 'festival-budaya.jpg',
                'description' => 'Pameran dan pertunjukan budaya nusantara'
            ],
            [
                'title' => 'Lomba Robotika',
                'image_url' => 'https://placehold.co/800x600/png',
                'image_name' => 'lomba-robotika.jpg',
                'description' => 'Tim robotika sekolah dalam kompetisi nasional'
            ],
            [
                'title' => 'Donor Darah',
                'image_url' => 'https://placehold.co/800x600/png',
                'image_name' => 'donor-darah.jpg',
                'description' => 'Kegiatan donor darah rutin siswa dan guru'
            ],
            [
                'title' => 'Perayaan HUT RI',
                'image_url' => 'https://placehold.co/800x600/png',
                'image_name' => 'hut-ri.jpg',
                'description' => 'Lomba dan perayaan kemerdekaan Indonesia'
            ],
            [
                'title' => 'Studi Tour',
                'image_url' => 'https://placehold.co/800x600/png',
                'image_name' => 'studi-tour.jpg',
                'description' => 'Kunjungan edukatif ke situs sejarah'
            ],
            [
                'title' => 'Pameran Karya Seni',
                'image_url' => 'https://placehold.co/800x600/png',
                'image_name' => 'pameran-seni.jpg',
                'description' => 'Eksibisi karya seni siswa'
            ],
            [
                'title' => 'Workshop Kewirausahaan',
                'image_url' => 'https://placehold.co/800x600/png',
                'image_name' => 'workshop.jpg',
                'description' => 'Pelatihan kewirausahaan untuk siswa'
            ],
            [
                'title' => 'Pertunjukan Teater',
                'image_url' => 'https://placehold.co/800x600/png',
                'image_name' => 'teater.jpg',
                'description' => 'Pementasan drama oleh klub teater'
            ],
            [
                'title' => 'Kompetisi Debat',
                'image_url' => 'https://placehold.co/800x600/png',
                'image_name' => 'debat.jpg',
                'description' => 'Tim debat sekolah dalam kompetisi nasional'
            ],
            [
                'title' => 'Pekan Olahraga',
                'image_url' => 'https://placehold.co/800x600/png',
                'image_name' => 'pekan-olahraga.jpg',
                'description' => 'Turnamen olahraga antar kelas'
            ],
            [
                'title' => 'Bakti Sosial',
                'image_url' => 'https://placehold.co/800x600/png',
                'image_name' => 'baksos.jpg',
                'description' => 'Kegiatan sosial di panti asuhan'
            ],
            [
                'title' => 'Seminar Motivasi',
                'image_url' => 'https://placehold.co/800x600/png',
                'image_name' => 'seminar.jpg',
                'description' => 'Seminar motivasi dengan pembicara nasional'
            ],
            [
                'title' => 'Perayaan Hari Guru',
                'image_url' => 'https://placehold.co/800x600/png',
                'image_name' => 'hari-guru.jpg',
                'description' => 'Perayaan dan penghargaan untuk para guru'
            ]
        ];

        foreach ($galleries as $gallery) {
            Gallery::create($gallery);
        }
    }
}
