<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Extracurricular;
use Illuminate\Support\Facades\File;

class PopulateExtracurricularsSeeder extends Seeder
{
    public function run()
    {
        Extracurricular::truncate();

        $data = [
            // ==========================================
            // ORGANISASI SISWA (type: organisasi)
            // ==========================================
            [
                'name' => 'OSIS (Organisasi Siswa Intra Sekolah)',
                'type' => 'organisasi',
                'category' => 'Organisasi Siswa',
                'description' => 'Organisasi kesiswaan tertinggi di sekolah yang bertugas merencanakan dan melaksanakan berbagai kegiatan siswa.',
                'activity_description' => 'OSIS mengadakan berbagai kegiatan seperti LDKS, bakti sosial, class meeting, dan event-event sekolah lainnya. Anggota OSIS dilatih untuk menjadi pemimpin yang bertanggung jawab dan mampu bekerja sama dalam tim.',
                'achievements_data' => [
                    ['title' => 'OSIS Terbaik Tingkat Kabupaten 2024', 'level' => 'Kabupaten', 'year' => '2024'],
                    ['title' => 'Penyelenggara LDKS Regional', 'level' => 'Regional', 'year' => '2023']
                ],
                'training_info' => [
                    'days' => ['Senin', 'Kamis'],
                    'start_time' => '14:00',
                    'end_time' => '16:00',
                    'location' => 'Ruang OSIS',
                    'coach' => 'Wakil Kepala Sekolah Bidang Kesiswaan'
                ],
                'main_image' => 'SMANSA.jpeg',
                'bg_image' => 'UPACARA.jpeg',
                'profile_image' => 'H. Dudi Rohdiana, S.Pd., M.M.jpg'
            ],
            [
                'name' => 'MPK (Majelis Perwakilan Kelas)',
                'type' => 'organisasi',
                'category' => 'Organisasi Siswa',
                'description' => 'Lembaga legislatif siswa yang bertugas mengawasi kinerja OSIS dan menyalurkan aspirasi siswa.',
                'activity_description' => 'MPK mengadakan sidang pleno, forum diskusi, dan menampung aspirasi dari setiap kelas. Anggota MPK dilatih untuk berpikir kritis dan berani menyuarakan pendapat.',
                'achievements_data' => [],
                'training_info' => [
                    'days' => ['Rabu'],
                    'start_time' => '14:00',
                    'end_time' => '15:30',
                    'location' => 'Ruang MPK',
                    'coach' => 'Pembina MPK'
                ],
                'main_image' => 'SMANSA.jpeg',
                'bg_image' => 'UPACARA.jpeg',
                'profile_image' => null
            ],

            // ==========================================
            // EKSTRAKURIKULER (type: ekstrakurikuler)
            // ==========================================
            
            // KEPEMIMPINAN & BELA NEGARA
            [
                'name' => 'Paskibra (Pasukan Pengibar Bendera)',
                'type' => 'ekstrakurikuler',
                'category' => 'Kepemimpinan & Bela Negara',
                'description' => 'Pasukan pengibar bendera yang melatih kedisiplinan, kekompakan, dan rasa nasionalisme.',
                'activity_description' => 'Latihan Paskibra mencakup baris-berbaris, formasi, pengibaran bendera, dan latihan fisik. Anggota juga dilatih untuk menjadi pembina upacara di sekolah.',
                'achievements_data' => [
                    ['title' => 'Pasukan Pengibar Bendera HUT RI Tingkat Kecamatan', 'level' => 'Kecamatan', 'year' => '2024'],
                    ['title' => 'Juara 2 Lomba Baris Berbaris Tingkat Kabupaten', 'level' => 'Kabupaten', 'year' => '2023']
                ],
                'training_info' => [
                    'days' => ['Senin', 'Sabtu'],
                    'start_time' => '06:00',
                    'end_time' => '08:00',
                    'location' => 'Lapangan Upacara',
                    'coach' => 'Pembina Paskibra'
                ],
                'main_image' => 'UPACARA.jpeg',
                'bg_image' => 'SMANSA.jpeg',
                'profile_image' => null
            ],
            [
                'name' => 'Pramuka',
                'type' => 'ekstrakurikuler',
                'category' => 'Kepemimpinan & Bela Negara',
                'description' => 'Membentuk karakter disiplin, mandiri, dan cinta alam. Kegiatan perkemahan, survival, dan kepemimpinan.',
                'activity_description' => 'Kegiatan Pramuka meliputi perkemahan, hiking, tali temali, navigasi, dan kegiatan sosial. Anggota dilatih untuk mandiri dan peduli lingkungan.',
                'achievements_data' => [
                    ['title' => 'Juara Umum Lomba Tingkat III', 'level' => 'Kwartir Cabang', 'year' => '2024']
                ],
                'training_info' => [
                    'days' => ['Jumat'],
                    'start_time' => '14:00',
                    'end_time' => '16:00',
                    'location' => 'Lapangan Pramuka',
                    'coach' => 'Kak Pembina'
                ],
                'main_image' => 'SMANSA.jpeg',
                'bg_image' => 'UPACARA.jpeg',
                'profile_image' => null
            ],
            
            // OLAHRAGA
            [
                'name' => 'Futsal',
                'type' => 'ekstrakurikuler',
                'category' => 'Olahraga',
                'description' => 'Mengembangkan bakat sepak bola mini dengan teknik dan strategi permainan yang solid.',
                'activity_description' => 'Latihan futsal mencakup teknik dasar dribbling, passing, shooting, dan strategi permainan. Tim rutin mengikuti kompetisi antar sekolah.',
                'achievements_data' => [
                    ['title' => 'Juara 1 Liga Pelajar 2024', 'level' => 'Kota', 'year' => '2024'],
                    ['title' => 'Juara 2 Turnamen Kota 2023', 'level' => 'Kota', 'year' => '2023']
                ],
                'training_info' => [
                    'days' => ['Senin', 'Kamis'],
                    'start_time' => '15:30',
                    'end_time' => '17:30',
                    'location' => 'Lapangan Futsal',
                    'coach' => 'Coach Budi'
                ],
                'main_image' => 'SMANSA.jpeg',
                'bg_image' => null,
                'profile_image' => null
            ],
            [
                'name' => 'Basket',
                'type' => 'ekstrakurikuler',
                'category' => 'Olahraga',
                'description' => 'Klub basket unggulan sekolah yang fokus pada fundamental dribble, shooting, dan defense.',
                'activity_description' => 'Latihan basket mencakup fundamental dribble, shooting, passing, dan strategi tim. Tim basket sekolah rutin mengikuti kompetisi DBL.',
                'achievements_data' => [
                    ['title' => 'Semifinalis DBL West Java 2024', 'level' => 'Provinsi', 'year' => '2024']
                ],
                'training_info' => [
                    'days' => ['Rabu', 'Jumat'],
                    'start_time' => '15:00',
                    'end_time' => '17:00',
                    'location' => 'Lapangan Basket',
                    'coach' => 'Coach Andi'
                ],
                'main_image' => 'UPACARA.jpeg',
                'bg_image' => null,
                'profile_image' => null
            ],
            [
                'name' => 'Voli',
                'type' => 'ekstrakurikuler',
                'category' => 'Olahraga',
                'description' => 'Melatih ketangkasan dan kekompakan tim dalam olahraga bola voli.',
                'activity_description' => 'Latihan voli mencakup teknik passing, serving, blocking, dan smashing. Tim voli sekolah aktif dalam kompetisi antar sekolah.',
                'achievements_data' => [
                    ['title' => 'Juara 3 O2SN Tingkat Kota', 'level' => 'Kota', 'year' => '2023']
                ],
                'training_info' => [
                    'days' => ['Selasa'],
                    'start_time' => '16:00',
                    'end_time' => '18:00',
                    'location' => 'Lapangan Voli',
                    'coach' => 'Pak Asep'
                ],
                'main_image' => 'SMANSA.jpeg',
                'bg_image' => null,
                'profile_image' => null
            ],

            // SENI & BUDAYA
            [
                'name' => 'Paduan Suara (Choir)',
                'type' => 'ekstrakurikuler',
                'category' => 'Seni & Budaya',
                'description' => 'Mengasah vokal dan harmoni dalam paduan suara. Rutin tampil dalam upacara bendera dan event-event besar.',
                'activity_description' => 'Latihan paduan suara mencakup teknik vokal, harmoni, dan interpretasi lagu. Paduan suara rutin tampil dalam berbagai acara sekolah dan kompetisi.',
                'achievements_data' => [
                    ['title' => 'Gold Medal Festival Paduan Suara ITB 2023', 'level' => 'Nasional', 'year' => '2023']
                ],
                'training_info' => [
                    'days' => ['Senin'],
                    'start_time' => '15:00',
                    'end_time' => '17:00',
                    'location' => 'Ruang Musik',
                    'coach' => 'Ibu Rina'
                ],
                'main_image' => 'UPACARA.jpeg',
                'bg_image' => 'SMANSA.jpeg',
                'profile_image' => null
            ],
            [
                'name' => 'Teater & Drama',
                'type' => 'ekstrakurikuler',
                'category' => 'Seni & Budaya',
                'description' => 'Wadah ekspresi seni peran, penulisan naskah, dan artistik panggung.',
                'activity_description' => 'Kegiatan teater mencakup latihan akting, penulisan naskah, dan produksi panggung. Anggota dilatih untuk berani tampil dan berbicara di depan umum.',
                'achievements_data' => [
                    ['title' => 'Penyaji Terbaik FLS2N Tingkat Provinsi', 'level' => 'Provinsi', 'year' => '2024']
                ],
                'training_info' => [
                    'days' => ['Kamis'],
                    'start_time' => '16:00',
                    'end_time' => '18:00',
                    'location' => 'Aula Sekolah',
                    'coach' => 'Kang Deden'
                ],
                'main_image' => 'SMANSA.jpeg',
                'bg_image' => null,
                'profile_image' => null
            ],
            [
                'name' => 'Seni Tari Tradisional',
                'type' => 'ekstrakurikuler',
                'category' => 'Seni & Budaya',
                'description' => 'Melestarikan budaya bangsa melalui seni tari daerah.',
                'activity_description' => 'Latihan tari tradisional mencakup gerakan dasar, kostum, dan makna tari. Anggota dilatih untuk melestarikan budaya daerah.',
                'achievements_data' => [],
                'training_info' => [
                    'days' => ['Rabu'],
                    'start_time' => '15:30',
                    'end_time' => '17:00',
                    'location' => 'Ruang Tari',
                    'coach' => 'Teh Nining'
                ],
                'main_image' => 'UPACARA.jpeg',
                'bg_image' => null,
                'profile_image' => null
            ],

            // AKADEMIK & SAINS
            [
                'name' => 'KIR (Karya Ilmiah Remaja)',
                'type' => 'ekstrakurikuler',
                'category' => 'Akademik & Sains',
                'description' => 'Komunitas peneliti muda yang kritis dan inovatif.',
                'activity_description' => 'Kegiatan KIR mencakup penelitian ilmiah, eksperimen, dan presentasi hasil penelitian. Anggota dilatih untuk berpikir kritis dan inovatif.',
                'achievements_data' => [
                    ['title' => 'Finalis LIPI LKIR 2024', 'level' => 'Nasional', 'year' => '2024']
                ],
                'training_info' => [
                    'days' => ['Jumat'],
                    'start_time' => '13:00',
                    'end_time' => '15:00',
                    'location' => 'Laboratorium IPA',
                    'coach' => 'Pak Heri'
                ],
                'main_image' => 'SMANSA.jpeg',
                'bg_image' => null,
                'profile_image' => null
            ],
            [
                'name' => 'English Club',
                'type' => 'ekstrakurikuler',
                'category' => 'Akademik & Sains',
                'description' => 'Meningkatkan kemampuan bahasa Inggris melalui debate, storytelling, dan speech.',
                'activity_description' => 'Kegiatan English Club mencakup debate, storytelling, speech, dan watching English movies. Anggota dilatih untuk berbicara bahasa Inggris dengan percaya diri.',
                'achievements_data' => [
                    ['title' => 'Best Speaker English Debate Open 2024', 'level' => 'Regional', 'year' => '2024']
                ],
                'training_info' => [
                    'days' => ['Selasa'],
                    'start_time' => '15:00',
                    'end_time' => '16:30',
                    'location' => 'Ruang Bahasa',
                    'coach' => 'Mr. John'
                ],
                'main_image' => 'UPACARA.jpeg',
                'bg_image' => null,
                'profile_image' => null
            ],
            [
                'name' => 'Robotik & IT',
                'type' => 'ekstrakurikuler',
                'category' => 'Teknologi & Inovasi',
                'description' => 'Belajar coding, mikrokontroler, dan perakitan robot.',
                'activity_description' => 'Kegiatan Robotik & IT mencakup coding, perakitan robot, dan kompetisi robot. Anggota dilatih untuk menguasai teknologi modern.',
                'achievements_data' => [
                    ['title' => 'Juara 1 Line Follower Robot Contest', 'level' => 'Regional', 'year' => '2024']
                ],
                'training_info' => [
                    'days' => ['Sabtu'],
                    'start_time' => '09:00',
                    'end_time' => '12:00',
                    'location' => 'Laboratorium Komputer',
                    'coach' => 'Kak Indra'
                ],
                'main_image' => 'SMANSA.jpeg',
                'bg_image' => null,
                'profile_image' => null
            ],

            // KEAGAMAAN & SOSIAL
            [
                'name' => 'PMR (Palang Merah Remaja)',
                'type' => 'ekstrakurikuler',
                'category' => 'Keagamaan & Sosial',
                'description' => 'Melatih keterampilan pertolongan pertama dan kepedulian sosial kemanusiaan.',
                'activity_description' => 'Kegiatan PMR mencakup pelatihan P3K, bakti sosial, dan kegiatan kemanusiaan. Anggota dilatih untuk siap membantu dalam keadaan darurat.',
                'achievements_data' => [],
                'training_info' => [
                    'days' => ['Rabu'],
                    'start_time' => '15:00',
                    'end_time' => '17:00',
                    'location' => 'Ruang PMR',
                    'coach' => 'Ibu Yanti'
                ],
                'main_image' => 'UPACARA.jpeg',
                'bg_image' => null,
                'profile_image' => null
            ],
            [
                'name' => 'Rohis (Kerohanian Islam)',
                'type' => 'ekstrakurikuler',
                'category' => 'Keagamaan & Sosial',
                'description' => 'Memperdalam ilmu agama Islam, tahsin Al-Quran, dan kegiatan sosial keagamaan.',
                'activity_description' => 'Kegiatan Rohis mencakup kajian agama, tahsin Al-Quran, dan kegiatan sosial keagamaan. Anggota dilatih untuk memperdalam ilmu agama Islam.',
                'achievements_data' => [],
                'training_info' => [
                    'days' => ['Jumat'],
                    'start_time' => '11:30',
                    'end_time' => '13:00',
                    'location' => 'Masjid Sekolah',
                    'coach' => 'Ust. Ahmad'
                ],
                'main_image' => 'SMANSA.jpeg',
                'bg_image' => null,
                'profile_image' => null
            ],
        ];

        foreach ($data as $item) {
            $mainImage = $item['main_image'] ?? null;
            $bgImage = $item['bg_image'] ?? null;
            $profileImage = $item['profile_image'] ?? null;
            
            unset($item['main_image'], $item['bg_image'], $item['profile_image']);

            $ekskul = Extracurricular::create($item);

            // Attach Main Image
            if ($mainImage) {
                $sourcePath = base_path("foto-guru/{$mainImage}");
                if (File::exists($sourcePath)) {
                    $ekskul->addMedia($sourcePath)
                        ->preservingOriginal()
                        ->toMediaCollection('images');
                }
            }

            // Attach Background Image
            if ($bgImage) {
                $sourcePath = base_path("foto-guru/{$bgImage}");
                if (File::exists($sourcePath)) {
                    $ekskul->addMedia($sourcePath)
                        ->preservingOriginal()
                        ->toMediaCollection('bg_images');
                }
            }

            // Attach Profile Image
            if ($profileImage) {
                $sourcePath = base_path("foto-guru/{$profileImage}");
                if (File::exists($sourcePath)) {
                    $ekskul->addMedia($sourcePath)
                        ->preservingOriginal()
                        ->toMediaCollection('profile_images');
                }
            }
        }

        $this->command->info('Organisasi & Ekstrakurikuler populated successfully.');
    }
}
