<?php

namespace Database\Seeders;

use App\Models\Agenda;
use Illuminate\Database\Seeder;

class AgendaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $agendas = [
            [
                'title' => 'Rapat Orang Tua Murid',
                'description' => 'Rapat koordinasi dengan orang tua murid membahas program semester genap',
                'date' => '2024-03-25'
            ],
            [
                'title' => 'Ujian Tengah Semester',
                'description' => 'Pelaksanaan Ujian Tengah Semester untuk seluruh siswa',
                'date' => '2024-04-15'
            ],
            [
                'title' => 'Perayaan Hari Kartini',
                'description' => 'Lomba dan pentas seni dalam rangka memperingati Hari Kartini',
                'date' => '2024-04-21'
            ],
            [
                'title' => 'Workshop Pengembangan Kurikulum',
                'description' => 'Pelatihan pengembangan kurikulum untuk guru-guru',
                'date' => '2024-05-05'
            ],
            [
                'title' => 'Kompetisi Sains Nasional',
                'description' => 'Seleksi tingkat sekolah untuk Kompetisi Sains Nasional',
                'date' => '2024-05-20'
            ],
            [
                'title' => 'Pekan Olahraga Sekolah',
                'description' => 'Kompetisi olahraga antar kelas meliputi futsal, basket, dan voli',
                'date' => '2024-06-01'
            ],
            [
                'title' => 'Seminar Motivasi',
                'description' => 'Seminar motivasi belajar dengan pembicara tamu dari praktisi pendidikan',
                'date' => '2024-06-15'
            ],
            [
                'title' => 'Ujian Akhir Semester',
                'description' => 'Pelaksanaan Ujian Akhir Semester untuk seluruh siswa',
                'date' => '2024-06-20'
            ],
            [
                'title' => 'Pembagian Rapor',
                'description' => 'Pembagian hasil belajar siswa semester genap tahun ajaran 2023/2024',
                'date' => '2024-06-30'
            ],
            [
                'title' => 'Masa Orientasi Siswa Baru',
                'description' => 'Kegiatan pengenalan lingkungan sekolah untuk siswa baru',
                'date' => '2024-07-15'
            ],
            [
                'title' => 'Upacara Kemerdekaan',
                'description' => 'Upacara bendera memperingati HUT Kemerdekaan RI',
                'date' => '2024-08-17'
            ],
            [
                'title' => 'Lomba Kebersihan Kelas',
                'description' => 'Kompetisi kebersihan dan kerapihan antar kelas',
                'date' => '2024-09-01'
            ],
            [
                'title' => 'Kunjungan Industri',
                'description' => 'Kunjungan ke beberapa perusahaan untuk mengenal dunia kerja',
                'date' => '2024-09-15'
            ],
            [
                'title' => 'Festival Seni dan Budaya',
                'description' => 'Pentas seni dan pameran karya siswa',
                'date' => '2024-10-01'
            ],
            [
                'title' => 'Peringatan Hari Guru',
                'description' => 'Acara apresiasi untuk guru-guru dalam rangka Hari Guru Nasional',
                'date' => '2024-11-25'
            ],
            [
                'title' => 'Simulasi Ujian Nasional',
                'description' => 'Tryout persiapan Ujian Nasional untuk kelas akhir',
                'date' => '2024-12-05'
            ],
            [
                'title' => 'Pemilihan Ketua OSIS',
                'description' => 'Pemilihan dan pelantikan ketua OSIS periode 2024/2025',
                'date' => '2024-12-10'
            ],
            [
                'title' => 'Perayaan Natal Bersama',
                'description' => 'Perayaan Natal bersama untuk seluruh warga sekolah',
                'date' => '2024-12-20'
            ],
            [
                'title' => 'Workshop Teknologi Informasi',
                'description' => 'Pelatihan penggunaan teknologi dalam pembelajaran untuk guru',
                'date' => '2025-01-05'
            ],
            [
                'title' => 'Donor Darah',
                'description' => 'Kegiatan donor darah bekerjasama dengan PMI',
                'date' => '2025-01-15'
            ]
        ];

        foreach ($agendas as $agenda) {
            Agenda::create($agenda);
        }
    }
}
