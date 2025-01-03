<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Pengumuman Sekolah',
            'Kegiatan Ekstrakurikuler',
            'Prestasi Siswa',
            'Prestasi Guru',
            'Informasi Akademik',
            'Jadwal Ujian',
            'Hari Libur dan Cuti Bersama',
            'Penerimaan Siswa Baru (PPDB)',
            'Kegiatan OSIS',
            'Lomba dan Kompetisi',
            'Berita Alumni',
            'Kegiatan Belajar Mengajar',
            'Seminar dan Pelatihan',
            'Kerja Sama dengan Industri',
            'Kegiatan Sosial dan Bakti Masyarakat',
            'Peringatan Hari Besar Nasional',
            'Kesehatan dan Keselamatan di Sekolah',
            'Program Beasiswa',
            'Pengembangan Infrastruktur Sekolah',
            'Kunjungan Industri atau Study Tour',
        ];

        foreach ($categories as $category) {
            Category::create(['category' => $category]);
        }
    }
}
