<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tags = [
            'Pengumuman',
            'Ekstrakurikuler',
            'Lomba',
            'Prestasi',
            'Akademik',
            'OSIS',
            'Guru',
            'Siswa',
            'PPDB',
            'Beasiswa',
            'Libur',
            'Seminar',
            'Workshop',
            'Kesehatan',
            'Keamanan',
            'Kerja Sama',
            'Infrastruktur',
            'Kegiatan Sosial',
            'Study Tour',
            'Hari Nasional',
            'Kompetisi',
            'Alumni',
            'Pendidikan',
            'Pelatihan',
            'Festival',
            'Seni',
            'Olahraga',
            'Bakti Sosial',
            'Lingkungan',
            'Teknologi',
            'Inovasi',
            'Peningkatan Mutu',
        ];

        foreach ($tags as $tag) {
            Tag::create(['tag' => $tag]);
        }
    }
}
