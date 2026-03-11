<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class SpmbSetting extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'section_key',
        'content',
    ];

    protected $casts = [
        'content' => 'array',
    ];

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('desktop')
            ->width(1280)
            ->format('webp')
            ->quality(90)
            ->nonQueued();

        $this->addMediaConversion('mobile')
            ->width(375)
            ->format('webp')
            ->quality(80)
            ->nonQueued();
    }

    // Define valid fields for each SPMB section
    public static function getSectionFields(): array
    {
        return [
            'pengaturan_umum' => [
                'title', 
                'description_html', 
                'is_registration_open', 
                'registration_year', 
                'announcement_text',
                'banner_image_url',
                'whatsapp_number',
                'video_guide_url'
            ],
            'jalur_pendaftaran' => ['items'], // List of paths (zonasi, prestasi, etc)
            'jadwal_penting' => ['items'], // items will be array of objects with title, date, description
            'persyaratan' => ['items', 'additional_notes'], // items array and additional notes
            'prosedur' => ['items', 'contact_info'], // items array and contact info
            'faq' => ['items'], // items will be array of objects with question and answer
        ];
    }

    public static function getDefaults(string $sectionKey): array
    {
        $defaults = [
            'pengaturan_umum' => [
                'title' => 'Informasi SPMB SMA Negeri 1 Baleendah',
                'description_html' => '<p>Selamat datang di halaman informasi Seleksi Penerimaan Murid Baru (SPMB) SMA Negeri 1 Baleendah. Temukan semua informasi penting mengenai pendaftaran siswa baru di sini.</p>',
                'is_registration_open' => false,
                'registration_year' => '2025/2026',
                'announcement_text' => 'Pendaftaran SPMB akan segera dibuka. Pantau terus website ini untuk informasi terbaru.',
                'banner_image_url' => '/images/hero-bg-sman1baleendah.jpeg',
                'whatsapp_number' => '628123456789',
                'video_guide_url' => '#',
            ],
            'jalur_pendaftaran' => [
                'items' => [
                    [
                        'label' => 'Zonasi',
                        'quota' => '50%',
                        'description' => 'Jalur pendaftaran bagi calon peserta didik yang berdomisili di dalam wilayah zonasi yang ditetapkan.',
                        'requirements' => [
                            'Kartu Keluarga (minimal 1 tahun)',
                            'Akta Kelahiran / Surat Keterangan Lahir',
                            'Ijazah / Surat Keterangan Lulus',
                            'Titik koordinat tempat tinggal'
                        ]
                    ],
                    [
                        'label' => 'Prestasi',
                        'quota' => '25%',
                        'description' => 'Jalur bagi siswa dengan pencapaian akademik (nilai rapor) atau non-akademik (kejuaraan/lomba).',
                        'requirements' => [
                            'Nilai Rapor semester 1-5',
                            'Sertifikat/Piagam Kejuaraan (min. tingkat Kota/Kab)',
                            'Surat Tanggung Jawab Mutlak (SPTJM)',
                            'Akta Kelahiran & KK'
                        ]
                    ],
                    [
                        'label' => 'Afirmasi',
                        'quota' => '20%',
                        'description' => 'Diperuntukkan bagi calon peserta didik dari keluarga ekonomi tidak mampu (KETM) dan penyandang disabilitas.',
                        'requirements' => [
                            'Kartu KIP / PKH / KKS / Terdaftar DTKS',
                            'Surat Keterangan Tidak Mampu (jika ada)',
                            'Akta Kelahiran & KK',
                            'Surat Pernyataan Orang Tua'
                        ]
                    ],
                    [
                        'label' => 'Perpindahan',
                        'quota' => '5%',
                        'description' => 'Bagi calon peserta didik yang mengikuti perpindahan tugas orang tua/wali atau anak guru.',
                        'requirements' => [
                            'Surat Keputusan Pindah Tugas Orang Tua',
                            'Surat Keterangan Domisili',
                            'Akta Kelahiran & KK',
                            'Ijazah / Surat Keterangan Lulus'
                        ]
                    ]
                ],
            ],
            'jadwal_penting' => [
                'items' => [
                    [
                        'title' => 'Sosialisasi SPMB',
                        'date' => '2025-02-15',
                        'description' => 'Sosialisasi informasi SPMB ke sekolah-sekolah SMP',
                    ],
                    [
                        'title' => 'Pendaftaran Tahap 1',
                        'date' => '2025-03-01',
                        'description' => 'Jalur Afirmasi, Perpindahan Tugas, dan Prestasi',
                    ],
                    [
                        'title' => 'Pengumuman Tahap 1',
                        'date' => '2025-04-01',
                        'description' => 'Pengumuman hasil seleksi tahap pertama',
                    ],
                ],
            ],
            'persyaratan' => [
                'items' => [
                    [
                        'name' => 'Ijazah SMP/sederajat',
                        'description' => 'Fotokopi ijazah yang telah dilegalisir',
                        'required' => true,
                    ],
                    [
                        'name' => 'Kartu Keluarga',
                        'description' => 'Fotokopi Kartu Keluarga yang masih berlaku',
                        'required' => true,
                    ],
                    [
                        'name' => 'Akta Kelahiran',
                        'description' => 'Fotokopi akta kelahiran',
                        'required' => true,
                    ],
                ],
                'additional_notes' => '<p>Semua dokumen harus dalam keadaan jelas dan dapat dibaca. Dokumen palsu akan berakibat pada pembatalan pendaftaran.</p>',
            ],
            'prosedur' => [
                'items' => [
                    [
                        'title' => 'Daftar Online',
                        'description' => '<p>Akses portal SPMB dan buat akun menggunakan NISN</p>',
                    ],
                    [
                        'title' => 'Isi Biodata',
                        'description' => '<p>Lengkapi semua data pribadi dan orang tua dengan benar</p>',
                    ],
                    [
                        'title' => 'Upload Dokumen',
                        'description' => '<p>Unggah semua dokumen persyaratan sesuai format yang ditentukan</p>',
                    ],
                ],
                'contact_info' => '<p>Untuk informasi lebih lanjut, hubungi: <br>Telp: (022) 7654321<br>Email: info@sman1baleendah.sch.id<br>Alamat: Jl. Raya Baleendah No. 456, Baleendah, Kabupaten Bandung</p>',
            ],
            'faq' => [
                'items' => [
                    [
                        'question' => 'Kapan pendaftaran SPMB dibuka?',
                        'answer' => '<p>Pendaftaran SPMB biasanya dibuka pada bulan Maret-Juni. Tanggal pasti akan diumumkan melalui website resmi.</p>',
                    ],
                    [
                        'question' => 'Apa saja program studi yang tersedia?',
                        'answer' => '<p>SMA Negeri 1 Baleendah memiliki 3 program studi: MIPA (Matematika dan Ilmu Pengetahuan Alam), IPS (Ilmu Pengetahuan Sosial), dan Bahasa (Ilmu Bahasa dan Budaya).</p>',
                    ],
                    [
                        'question' => 'Bisakah memilih lebih dari satu program studi?',
                        'answer' => '<p>Tidak, pada saat pendaftaran calon siswa hanya dapat memilih satu program studi sesuai minat dan kemampuan akademik.</p>',
                    ],
                ],
            ],
        ];

        return $defaults[$sectionKey] ?? [];
    }

    public static function getContent(string $sectionKey, ?array $dbContent = null): array
    {
        $defaults = self::getDefaults($sectionKey);

        return $dbContent ? array_merge($defaults, $dbContent) : $defaults;
    }
}
