<?php

namespace Database\Seeders;

use App\Models\Facility;
use Illuminate\Database\Seeder;

class FacilitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $facilities = [
            [
                'title' => 'Perpustakaan Digital',
                'image_url' => 'https://placehold.co/100x100/png',
                'image_name' => 'perpustakaan.jpg',
                'description' => 'Perpustakaan modern dilengkapi dengan komputer dan akses e-book'
            ],
            [
                'title' => 'Laboratorium Komputer',
                'image_url' => 'https://placehold.co/100x100/png',
                'image_name' => 'lab-komputer.jpg',
                'description' => 'Lab komputer dengan 40 unit PC terbaru dan koneksi internet cepat'
            ],
            [
                'title' => 'Lapangan Olahraga',
                'image_url' => 'https://placehold.co/100x100/png',
                'image_name' => 'lapangan.jpg',
                'description' => 'Lapangan multifungsi untuk basket, futsal, dan voli'
            ],
            [
                'title' => 'Laboratorium Sains',
                'image_url' => 'https://placehold.co/100x100/png',
                'image_name' => 'lab-sains.jpg',
                'description' => 'Laboratorium lengkap untuk praktikum Fisika, Kimia, dan Biologi'
            ],
            [
                'title' => 'Ruang Musik',
                'image_url' => 'https://placehold.co/100x100/png',
                'image_name' => 'ruang-musik.jpg',
                'description' => 'Studio musik dengan berbagai alat musik modern dan tradisional'
            ],
            [
                'title' => 'Kantin Sehat',
                'image_url' => 'https://placehold.co/100x100/png',
                'image_name' => 'kantin.jpg',
                'description' => 'Kantin bersih dengan menu sehat dan bergizi'
            ],
            [
                'title' => 'Ruang UKS',
                'image_url' => 'https://placehold.co/100x100/png',
                'image_name' => 'uks.jpg',
                'description' => 'Unit Kesehatan Sekolah dengan peralatan medis dasar'
            ],
            [
                'title' => 'Masjid Sekolah',
                'image_url' => 'https://placehold.co/100x100/png',
                'image_name' => 'masjid.jpg',
                'description' => 'Tempat ibadah yang nyaman dan luas'
            ],
            [
                'title' => 'Aula Serbaguna',
                'image_url' => 'https://placehold.co/100x100/png',
                'image_name' => 'aula.jpg',
                'description' => 'Ruang pertemuan dan acara dengan kapasitas 500 orang'
            ],
            [
                'title' => 'Studio Seni',
                'image_url' => 'https://placehold.co/100x100/png',
                'image_name' => 'studio-seni.jpg',
                'description' => 'Ruang praktek seni rupa dan kerajinan tangan'
            ],
            [
                'title' => 'Greenhouse',
                'image_url' => 'https://placehold.co/100x100/png',
                'image_name' => 'greenhouse.jpg',
                'description' => 'Kebun sekolah untuk pembelajaran biologi dan pertanian'
            ],
            [
                'title' => 'Ruang Konseling',
                'image_url' => 'https://placehold.co/100x100/png',
                'image_name' => 'konseling.jpg',
                'description' => 'Ruang bimbingan dan konseling yang nyaman dan privat'
            ],
            [
                'title' => 'Kolam Renang',
                'image_url' => 'https://placehold.co/100x100/png',
                'image_name' => 'kolam-renang.jpg',
                'description' => 'Fasilitas renang standar nasional'
            ],
            [
                'title' => 'Asrama Siswa',
                'image_url' => 'https://placehold.co/100x100/png',
                'image_name' => 'asrama.jpg',
                'description' => 'Asrama modern dengan fasilitas lengkap'
            ],
            [
                'title' => 'Ruang Teater',
                'image_url' => 'https://placehold.co/100x100/png',
                'image_name' => 'teater.jpg',
                'description' => 'Ruang pertunjukan dengan sistem audio visual modern'
            ],
            [
                'title' => 'Laboratorium Bahasa',
                'image_url' => 'https://placehold.co/100x100/png',
                'image_name' => 'lab-bahasa.jpg',
                'description' => 'Lab bahasa interaktif dengan perangkat multimedia'
            ],
            [
                'title' => 'Ruang OSIS',
                'image_url' => 'https://placehold.co/100x100/png',
                'image_name' => 'osis.jpg',
                'description' => 'Sekretariat OSIS dengan fasilitas rapat dan kerja'
            ],
            [
                'title' => 'Taman Belajar',
                'image_url' => 'https://placehold.co/100x100/png',
                'image_name' => 'taman.jpg',
                'description' => 'Area outdoor untuk belajar dan diskusi'
            ],
            [
                'title' => 'Ruang Robotika',
                'image_url' => 'https://placehold.co/100x100/png',
                'image_name' => 'robotika.jpg',
                'description' => 'Lab robotika dengan peralatan pemrograman dan mekanika'
            ],
            [
                'title' => 'Parkir Sepeda',
                'image_url' => 'https://placehold.co/100x100/png',
                'image_name' => 'parkir-sepeda.jpg',
                'description' => 'Area parkir sepeda yang aman dan tertata'
            ]
        ];

        foreach ($facilities as $facility) {
            Facility::create($facility);
        }
    }
}
