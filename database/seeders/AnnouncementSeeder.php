<?php

namespace Database\Seeders;

use App\Models\Announcement;
use Illuminate\Database\Seeder;

class AnnouncementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $announcements = [
            [
                'title' => 'Pengumuman Libur Hari Raya',
                'description' => 'Diberitahukan kepada seluruh civitas akademika bahwa akan ada libur hari raya selama 1 minggu.',
            ],
            [
                'title' => 'Jadwal Ujian Semester',
                'description' => 'Jadwal ujian semester telah dirilis, silakan cek di portal akademik.',
            ],
            [
                'title' => 'Maintenance Sistem',
                'description' => 'Akan dilakukan maintenance sistem pada tanggal 15 Juli 2024.',
            ],
            [
                'title' => 'Pembukaan Pendaftaran Beasiswa',
                'description' => 'Pendaftaran beasiswa periode 2024 telah dibuka.',
            ],
            [
                'title' => 'Workshop Kewirausahaan',
                'description' => 'Workshop kewirausahaan akan diadakan pada bulan Agustus 2024.',
            ],
            [
                'title' => 'Pengumuman Wisuda',
                'description' => 'Wisuda periode I tahun 2024 akan dilaksanakan pada bulan September.',
            ],
            [
                'title' => 'Vaksinasi Covid-19',
                'description' => 'Program vaksinasi booster akan dilaksanakan di kampus.',
            ],
            [
                'title' => 'Kompetisi Karya Ilmiah',
                'description' => 'Pendaftaran kompetisi karya ilmiah mahasiswa telah dibuka.',
            ],
            [
                'title' => 'Pelatihan Digital Marketing',
                'description' => 'Pelatihan digital marketing gratis untuk mahasiswa tingkat akhir.',
            ],
            [
                'title' => 'Pembaruan Sistem Akademik',
                'description' => 'Sistem akademik telah diperbarui dengan fitur-fitur baru.',
            ],
            [
                'title' => 'Seminar Nasional',
                'description' => 'Seminar nasional dengan tema "Teknologi di Era Digital" akan diselenggarakan bulan depan.',
            ],
            [
                'title' => 'Pembukaan Lab Komputer',
                'description' => 'Lab komputer baru telah dibuka untuk mahasiswa.',
            ],
            [
                'title' => 'Program Magang',
                'description' => 'Dibuka pendaftaran program magang di perusahaan partner.',
            ],
            [
                'title' => 'Perubahan Jadwal Kuliah',
                'description' => 'Terdapat perubahan jadwal kuliah untuk semester ganjil.',
            ],
            [
                'title' => 'Kompetisi Olahraga',
                'description' => 'Pendaftaran kompetisi olahraga antar jurusan telah dibuka.',
            ],
            [
                'title' => 'Pengumuman Hasil Seleksi',
                'description' => 'Hasil seleksi beasiswa telah diumumkan.',
            ],
            [
                'title' => 'Webinar Karir',
                'description' => 'Webinar karir dengan pembicara dari industri teknologi.',
            ],
            [
                'title' => 'Pemilihan Ketua BEM',
                'description' => 'Pemilihan ketua BEM periode 2024-2025 akan segera dilaksanakan.',
            ],
            [
                'title' => 'Festival Budaya',
                'description' => 'Festival budaya tahunan akan diselenggarakan bulan depan.',
            ],
            [
                'title' => 'Pengumuman Dosen Baru',
                'description' => 'Selamat datang kepada dosen-dosen baru yang bergabung semester ini.',
            ],
        ];

        foreach ($announcements as $announcement) {
            Announcement::create($announcement);
        }
    }
}
