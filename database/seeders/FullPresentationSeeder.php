<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\AttemptAnswer;
use App\Models\CaseSection;
use App\Models\CaseSectionItem;
use App\Models\CaseSubmission;
use App\Models\Kelas;
use App\Models\Lesson;
use App\Models\MataPelajaran;
use App\Models\Option;
use App\Models\PblCase;
use App\Models\Question;
use App\Models\SessionDuration;
use App\Models\StudentActivityLog;
use App\Models\StudentRiskProfile;
use App\Models\SubjectMastery;
use App\Models\TeacherInsight;
use App\Models\User;
use App\Models\UserLesson;
use App\Enums\UserRole;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class FullPresentationSeeder extends Seeder
{
    // ──────────────────────────────────────────────────────────────
    //  KONFIGURASI DATA
    // ──────────────────────────────────────────────────────────────

    private array $subjects = [
        'Bahasa Isyarat Indonesia (BISINDO)',
        'Matematika Dasar',
        'Ilmu Pengetahuan Alam (IPA)',
        'Bahasa Indonesia',
        'Ilmu Pengetahuan Sosial (IPS)',
    ];

    /**
     * 10 siswa dengan profil terkontrol untuk demonstrasi WASPAS.
     * Distribusi target: 3 Low, 2 Medium, 2 High, 3 Critical
     */
    private array $studentProfiles = [
        [
            'name' => 'Aisyah Putri Rahmawati',
            'email' => 'aisyah@siswa.isyaratpintar.id',
            'attendance_days' => 7,
            'performance_scores' => [95, 88, 90, 92, 94, 91, 89, 93, 90, 98],
            'engagement_total_minutes' => 1500,
            'mastery' => [
                'Bahasa Isyarat Indonesia (BISINDO)' => 95,
                'Matematika Dasar' => 82,
                'Ilmu Pengetahuan Alam (IPA)' => 88,
                'Bahasa Indonesia' => 90,
                'Ilmu Pengetahuan Sosial (IPS)' => 85,
            ],
        ],
        [
            'name' => 'Bayu Adi Pratama',
            'email' => 'bayu@siswa.isyaratpintar.id',
            'attendance_days' => 2,
            'performance_scores' => [35, 42, 30, 38, 45, 28, 40, 38, 42, 32],
            'engagement_total_minutes' => 60,
            'mastery' => [
                'Bahasa Isyarat Indonesia (BISINDO)' => 35,
                'Matematika Dasar' => 18,
                'Ilmu Pengetahuan Alam (IPA)' => 22,
                'Bahasa Indonesia' => 40,
                'Ilmu Pengetahuan Sosial (IPS)' => 25,
            ],
        ],
        [
            'name' => 'Citra Dewi Lestari',
            'email' => 'citra@siswa.isyaratpintar.id',
            'attendance_days' => 6,
            'performance_scores' => [72, 78, 70, 75, 80, 73, 76, 74, 72, 80],
            'engagement_total_minutes' => 840,
            'mastery' => [
                'Bahasa Isyarat Indonesia (BISINDO)' => 78,
                'Matematika Dasar' => 62,
                'Ilmu Pengetahuan Alam (IPA)' => 68,
                'Bahasa Indonesia' => 75,
                'Ilmu Pengetahuan Sosial (IPS)' => 67,
            ],
        ],
        [
            'name' => 'Dimas Arya Nugraha',
            'email' => 'dimas@siswa.isyaratpintar.id',
            'attendance_days' => 1,
            'performance_scores' => [40, 45, 38, 42, 48, 35, 44, 40, 42, 46],
            'engagement_total_minutes' => 30,
            'mastery' => [
                'Bahasa Isyarat Indonesia (BISINDO)' => 42,
                'Matematika Dasar' => 20,
                'Ilmu Pengetahuan Alam (IPA)' => 28,
                'Bahasa Indonesia' => 45,
                'Ilmu Pengetahuan Sosial (IPS)' => 25,
            ],
        ],
        [
            'name' => 'Eka Sari Wulandari',
            'email' => 'eka@siswa.isyaratpintar.id',
            'attendance_days' => 5,
            'performance_scores' => [62, 68, 60, 65, 70, 63, 67, 64, 66, 65],
            'engagement_total_minutes' => 420,
            'mastery' => [
                'Bahasa Isyarat Indonesia (BISINDO)' => 68,
                'Matematika Dasar' => 48,
                'Ilmu Pengetahuan Alam (IPA)' => 55,
                'Bahasa Indonesia' => 65,
                'Ilmu Pengetahuan Sosial (IPS)' => 54,
            ],
        ],
        [
            'name' => 'Farhan Maulana Akbar',
            'email' => 'farhan@siswa.isyaratpintar.id',
            'attendance_days' => 4,
            'performance_scores' => [52, 58, 50, 55, 60, 53, 56, 54, 55, 57],
            'engagement_total_minutes' => 300,
            'mastery' => [
                'Bahasa Isyarat Indonesia (BISINDO)' => 58,
                'Matematika Dasar' => 38,
                'Ilmu Pengetahuan Alam (IPA)' => 45,
                'Bahasa Indonesia' => 55,
                'Ilmu Pengetahuan Sosial (IPS)' => 44,
            ],
        ],
        [
            'name' => 'Galuh Permata Sari',
            'email' => 'galuh@siswa.isyaratpintar.id',
            'attendance_days' => 7,
            'performance_scores' => [82, 88, 80, 85, 90, 83, 86, 84, 88, 84],
            'engagement_total_minutes' => 1200,
            'mastery' => [
                'Bahasa Isyarat Indonesia (BISINDO)' => 90,
                'Matematika Dasar' => 75,
                'Ilmu Pengetahuan Alam (IPA)' => 82,
                'Bahasa Indonesia' => 88,
                'Ilmu Pengetahuan Sosial (IPS)' => 75,
            ],
        ],
        [
            'name' => 'Hendra Wijaya Kusuma',
            'email' => 'hendra@siswa.isyaratpintar.id',
            'attendance_days' => 3,
            'performance_scores' => [48, 52, 45, 50, 55, 47, 51, 49, 52, 51],
            'engagement_total_minutes' => 180,
            'mastery' => [
                'Bahasa Isyarat Indonesia (BISINDO)' => 52,
                'Matematika Dasar' => 32,
                'Ilmu Pengetahuan Alam (IPA)' => 40,
                'Bahasa Indonesia' => 50,
                'Ilmu Pengetahuan Sosial (IPS)' => 36,
            ],
        ],
        [
            'name' => 'Indah Nur Fitriani',
            'email' => 'indah@siswa.isyaratpintar.id',
            'attendance_days' => 6,
            'performance_scores' => [75, 80, 74, 78, 82, 76, 79, 77, 78, 81],
            'engagement_total_minutes' => 960,
            'mastery' => [
                'Bahasa Isyarat Indonesia (BISINDO)' => 82,
                'Matematika Dasar' => 68,
                'Ilmu Pengetahuan Alam (IPA)' => 72,
                'Bahasa Indonesia' => 78,
                'Ilmu Pengetahuan Sosial (IPS)' => 70,
            ],
        ],
        [
            'name' => 'Joko Susanto Prasetyo',
            'email' => 'joko@siswa.isyaratpintar.id',
            'attendance_days' => 0,
            'performance_scores' => [22, 28, 20, 25, 30, 18, 26, 24, 28, 29],
            'engagement_total_minutes' => 15,
            'mastery' => [
                'Bahasa Isyarat Indonesia (BISINDO)' => 25,
                'Matematika Dasar' => 10,
                'Ilmu Pengetahuan Alam (IPA)' => 15,
                'Bahasa Indonesia' => 28,
                'Ilmu Pengetahuan Sosial (IPS)' => 12,
            ],
        ],
    ];

    // ──────────────────────────────────────────────────────────────
    //  DATA MATERI PELAJARAN (LESSONS)
    // ──────────────────────────────────────────────────────────────

    private function getLessonsData(): array
    {
        return [
            'Bahasa Isyarat Indonesia (BISINDO)' => [
                [
                    'title' => 'Pengenalan Alfabet BISINDO',
                    'description' => 'Pelajari huruf A hingga Z dalam Bahasa Isyarat Indonesia (BISINDO). Materi mencakup bentuk tangan, posisi jari, dan arah gerakan untuk setiap huruf alfabet. Peserta didik akan berlatih membentuk huruf dengan tangan dominan dan memahami perbedaan antara huruf yang mirip seperti M-N, U-V, dan R-S.',
                    'duration' => 45,
                ],
                [
                    'title' => 'Isyarat Angka dan Bilangan',
                    'description' => 'Mengenal isyarat angka 0-100 dalam BISINDO. Materi ini mengajarkan cara menunjukkan angka satuan (1-9), puluhan (10-90), dan bilangan komposit. Peserta didik juga akan mempelajari isyarat untuk operasi matematika dasar seperti tambah, kurang, kali, dan bagi.',
                    'duration' => 40,
                ],
                [
                    'title' => 'Kosa Kata Sehari-hari',
                    'description' => 'Mempelajari 50 kosa kata BISINDO yang paling sering digunakan dalam percakapan sehari-hari. Meliputi salam, perkenalan, keluarga, makanan, minuman, waktu, cuaca, dan aktivitas umum. Setiap isyarat disertai dengan penjelasan gerakan dan ekspresi wajah yang menyertainya.',
                    'duration' => 60,
                ],
                [
                    'title' => 'Percakapan Dasar BISINDO',
                    'description' => 'Berlatih percakapan sederhana menggunakan BISINDO. Materi mencakup dialog perkenalan diri, bertanya kabar, berbelanja di toko, dan meminta bantuan. Peserta didik akan memahami struktur kalimat BISINDO yang berbeda dari bahasa lisan Indonesia.',
                    'duration' => 50,
                ],
            ],
            'Matematika Dasar' => [
                [
                    'title' => 'Penjumlahan dan Pengurangan',
                    'description' => 'Memahami konsep penjumlahan dan pengurangan bilangan bulat dengan bantuan visual. Materi disajikan menggunakan gambar, diagram, dan animasi untuk memudahkan pemahaman siswa tunarungu. Contoh soal dikaitkan dengan kehidupan sehari-hari seperti menghitung uang belanja dan jumlah barang.',
                    'duration' => 45,
                ],
                [
                    'title' => 'Perkalian dan Pembagian',
                    'description' => 'Menguasai konsep perkalian sebagai penjumlahan berulang dan pembagian sebagai kebalikan perkalian. Materi menggunakan pendekatan visual dengan tabel perkalian bergambar. Peserta didik akan berlatih soal cerita yang relevan dengan konteks tunarungu.',
                    'duration' => 50,
                ],
                [
                    'title' => 'Pecahan dan Desimal',
                    'description' => 'Mengenal konsep pecahan melalui visualisasi potongan kue, pizza, dan benda nyata lainnya. Materi mencakup pecahan biasa, pecahan campuran, dan konversi ke desimal. Penjelasan menggunakan diagram lingkaran dan batang yang mudah dipahami secara visual.',
                    'duration' => 55,
                ],
                [
                    'title' => 'Geometri Dasar: Bangun Datar',
                    'description' => 'Mempelajari sifat-sifat bangun datar: persegi, persegi panjang, segitiga, lingkaran, trapesium, dan jajargenjang. Materi meliputi perhitungan keliling dan luas dengan rumus serta contoh soal kontekstual. Menggunakan alat peraga visual dan interaktif.',
                    'duration' => 50,
                ],
            ],
            'Ilmu Pengetahuan Alam (IPA)' => [
                [
                    'title' => 'Tubuh Manusia dan Panca Indera',
                    'description' => 'Mengenal organ tubuh manusia dan fungsi panca indera. Materi khusus membahas bagaimana pendengaran bekerja, apa yang terjadi pada telinga siswa tunarungu, dan bagaimana indera lain (terutama penglihatan dan perabaan) dapat dioptimalkan untuk belajar. Disertai diagram anatomi berwarna.',
                    'duration' => 45,
                ],
                [
                    'title' => 'Tumbuhan dan Lingkungan',
                    'description' => 'Mempelajari bagian-bagian tumbuhan (akar, batang, daun, bunga, buah, biji) dan proses fotosintesis. Materi disajikan melalui pengamatan langsung dan eksperimen sederhana yang bisa dilakukan di kelas. Peserta didik belajar tentang pentingnya menjaga lingkungan dan ekosistem.',
                    'duration' => 50,
                ],
                [
                    'title' => 'Energi dan Perubahannya',
                    'description' => 'Memahami berbagai bentuk energi: panas, cahaya, listrik, gerak, dan bunyi. Fokus khusus pada energi yang dapat diamati secara visual (cahaya, gerak, panas). Eksperimen sederhana termasuk membuat rangkaian listrik sederhana dan mengamati perubahan energi dalam kehidupan sehari-hari.',
                    'duration' => 55,
                ],
                [
                    'title' => 'Siklus Air dan Cuaca',
                    'description' => 'Mengenal siklus air (evaporasi, kondensasi, presipitasi) melalui animasi visual dan eksperimen mini. Materi juga mencakup jenis-jenis cuaca, cara membaca termometer, dan dampak perubahan iklim. Penyajian mengutamakan diagram, infografis, dan video berteks.',
                    'duration' => 45,
                ],
            ],
            'Bahasa Indonesia' => [
                [
                    'title' => 'Membaca Pemahaman Teks Narasi',
                    'description' => 'Melatih kemampuan membaca dan memahami teks narasi sederhana. Materi berisi cerita pendek bergambar yang disesuaikan dengan konteks kehidupan anak tunarungu. Peserta didik akan berlatih menjawab pertanyaan tentang tokoh, alur, setting, dan pesan moral cerita.',
                    'duration' => 45,
                ],
                [
                    'title' => 'Menulis Kalimat Efektif',
                    'description' => 'Belajar menyusun kalimat yang jelas dan efektif. Materi mencakup struktur SPOK (Subjek-Predikat-Objek-Keterangan), penggunaan tanda baca yang benar, dan cara mengembangkan ide menjadi paragraf. Latihan menulis menggunakan tema keseharian siswa.',
                    'duration' => 50,
                ],
                [
                    'title' => 'Kosa Kata dan Sinonim-Antonim',
                    'description' => 'Memperkaya perbendaharaan kata bahasa Indonesia melalui permainan kata, teka-teki silang, dan latihan mencocokkan sinonim-antonim. Materi dilengkapi dengan gambar ilustrasi untuk setiap kata baru agar memudahkan pemahaman visual siswa tunarungu.',
                    'duration' => 40,
                ],
                [
                    'title' => 'Menulis Cerita Pendek',
                    'description' => 'Berlatih menulis cerita pendek dengan tema yang dekat dengan kehidupan siswa. Materi mengajarkan langkah-langkah menulis: menentukan tema, membuat kerangka karangan, mengembangkan alur, dan menyunting tulisan. Contoh cerita tentang pengalaman anak tunarungu sehari-hari.',
                    'duration' => 55,
                ],
            ],
            'Ilmu Pengetahuan Sosial (IPS)' => [
                [
                    'title' => 'Lingkungan Tempat Tinggalku',
                    'description' => 'Mengenal lingkungan sosial: keluarga, tetangga, dan masyarakat. Materi membahas peran setiap anggota keluarga, norma dan aturan dalam bermasyarakat, serta hak dan kewajiban anak. Konteks khusus: bagaimana anak tunarungu berinteraksi dengan lingkungan sekitar.',
                    'duration' => 40,
                ],
                [
                    'title' => 'Keragaman Budaya Indonesia',
                    'description' => 'Mempelajari keragaman suku, bahasa, agama, dan adat istiadat di Indonesia. Materi disertai peta budaya Indonesia, gambar pakaian adat, rumah adat, dan tarian daerah. Fokus pada nilai toleransi dan kebersamaan dalam keberagaman, termasuk penerimaan terhadap penyandang disabilitas.',
                    'duration' => 50,
                ],
                [
                    'title' => 'Sejarah Kemerdekaan Indonesia',
                    'description' => 'Mengenal peristiwa penting menjelang dan saat proklamasi kemerdekaan Indonesia. Materi mencakup tokoh-tokoh pejuang, kronologi peristiwa, dan makna kemerdekaan bagi bangsa Indonesia. Penyajian menggunakan timeline visual, komik sejarah, dan infografis.',
                    'duration' => 45,
                ],
                [
                    'title' => 'Aktivitas Ekonomi Masyarakat',
                    'description' => 'Memahami berbagai aktivitas ekonomi: produksi, distribusi, dan konsumsi. Materi membahas jenis-jenis pekerjaan, uang dan transaksi jual beli, serta kebutuhan primer, sekunder, dan tersier. Konteks inklusif: peluang kerja dan wirausaha bagi penyandang tunarungu.',
                    'duration' => 45,
                ],
            ],
        ];
    }

    // ──────────────────────────────────────────────────────────────
    //  DATA SOAL ASSESSMENT (PER MATA PELAJARAN)
    // ──────────────────────────────────────────────────────────────

    private function getAssessmentsData(): array
    {
        return [
            'Bahasa Isyarat Indonesia (BISINDO)' => [
                [
                    'title' => 'Ujian BISINDO: Alfabet dan Angka',
                    'description' => 'Menguji pemahaman siswa tentang alfabet dan angka dalam BISINDO.',
                    'time_limit' => 20,
                    'questions' => [
                        [
                            'text' => 'Berapa jumlah huruf dalam alfabet BISINDO?',
                            'options' => [
                                ['label' => 'a', 'text' => '24 huruf', 'is_correct' => false],
                                ['label' => 'b', 'text' => '26 huruf', 'is_correct' => true],
                                ['label' => 'c', 'text' => '28 huruf', 'is_correct' => false],
                                ['label' => 'd', 'text' => '30 huruf', 'is_correct' => false],
                            ],
                        ],
                        [
                            'text' => 'Dalam BISINDO, isyarat huruf apa yang menggunakan jari telunjuk dan jari tengah membentuk huruf V?',
                            'options' => [
                                ['label' => 'a', 'text' => 'Huruf U', 'is_correct' => false],
                                ['label' => 'b', 'text' => 'Huruf V', 'is_correct' => true],
                                ['label' => 'c', 'text' => 'Huruf W', 'is_correct' => false],
                                ['label' => 'd', 'text' => 'Huruf Y', 'is_correct' => false],
                            ],
                        ],
                        [
                            'text' => 'Apa perbedaan utama antara BISINDO dan SIBI?',
                            'options' => [
                                ['label' => 'a', 'text' => 'BISINDO menggunakan dua tangan, SIBI satu tangan', 'is_correct' => false],
                                ['label' => 'b', 'text' => 'BISINDO berkembang alami dari komunitas Tuli, SIBI dibuat berdasarkan tata bahasa Indonesia', 'is_correct' => true],
                                ['label' => 'c', 'text' => 'BISINDO hanya untuk anak-anak', 'is_correct' => false],
                                ['label' => 'd', 'text' => 'Tidak ada perbedaan', 'is_correct' => false],
                            ],
                        ],
                        [
                            'text' => 'Bagaimana cara mengisyaratkan angka 10 dalam BISINDO?',
                            'options' => [
                                ['label' => 'a', 'text' => 'Menunjukkan sepuluh jari sekaligus', 'is_correct' => true],
                                ['label' => 'b', 'text' => 'Menunjukkan satu jari lalu nol', 'is_correct' => false],
                                ['label' => 'c', 'text' => 'Membuat lingkaran dengan dua tangan', 'is_correct' => false],
                                ['label' => 'd', 'text' => 'Mengetuk meja sepuluh kali', 'is_correct' => false],
                            ],
                        ],
                        [
                            'text' => 'Ekspresi wajah dalam BISINDO berfungsi sebagai?',
                            'options' => [
                                ['label' => 'a', 'text' => 'Hiasan saja', 'is_correct' => false],
                                ['label' => 'b', 'text' => 'Bagian dari tata bahasa (gramatika)', 'is_correct' => true],
                                ['label' => 'c', 'text' => 'Pengganti isyarat tangan', 'is_correct' => false],
                                ['label' => 'd', 'text' => 'Tidak penting dalam BISINDO', 'is_correct' => false],
                            ],
                        ],
                    ],
                ],
                [
                    'title' => 'Ujian BISINDO: Kosa Kata dan Percakapan',
                    'description' => 'Menguji kemampuan kosa kata dan percakapan dasar BISINDO.',
                    'time_limit' => 25,
                    'questions' => [
                        [
                            'text' => 'Isyarat untuk "Terima kasih" dalam BISINDO adalah?',
                            'options' => [
                                ['label' => 'a', 'text' => 'Tangan kanan menyentuh dagu lalu bergerak ke depan', 'is_correct' => true],
                                ['label' => 'b', 'text' => 'Kedua tangan bertepuk', 'is_correct' => false],
                                ['label' => 'c', 'text' => 'Jari telunjuk menunjuk ke atas', 'is_correct' => false],
                                ['label' => 'd', 'text' => 'Tangan melambai ke depan', 'is_correct' => false],
                            ],
                        ],
                        [
                            'text' => 'Dalam struktur kalimat BISINDO, urutan yang umum digunakan adalah?',
                            'options' => [
                                ['label' => 'a', 'text' => 'Subjek - Predikat - Objek (sama seperti bahasa Indonesia)', 'is_correct' => false],
                                ['label' => 'b', 'text' => 'Topik - Komentar (topic-comment)', 'is_correct' => true],
                                ['label' => 'c', 'text' => 'Objek - Predikat - Subjek', 'is_correct' => false],
                                ['label' => 'd', 'text' => 'Tidak memiliki struktur tertentu', 'is_correct' => false],
                            ],
                        ],
                        [
                            'text' => 'Apa isyarat BISINDO untuk kata "Sekolah"?',
                            'options' => [
                                ['label' => 'a', 'text' => 'Kedua tangan membentuk atap rumah lalu membuka buku', 'is_correct' => true],
                                ['label' => 'b', 'text' => 'Tangan menunjuk ke depan', 'is_correct' => false],
                                ['label' => 'c', 'text' => 'Jari mengetuk kepala', 'is_correct' => false],
                                ['label' => 'd', 'text' => 'Tangan diayunkan ke samping', 'is_correct' => false],
                            ],
                        ],
                        [
                            'text' => 'Bagaimana cara menyatakan pertanyaan dalam BISINDO?',
                            'options' => [
                                ['label' => 'a', 'text' => 'Menggunakan intonasi suara tinggi', 'is_correct' => false],
                                ['label' => 'b', 'text' => 'Menaikkan alis dan sedikit memajukan kepala', 'is_correct' => true],
                                ['label' => 'c', 'text' => 'Menambahkan kata "kah" di akhir', 'is_correct' => false],
                                ['label' => 'd', 'text' => 'Menggelengkan kepala', 'is_correct' => false],
                            ],
                        ],
                        [
                            'text' => 'Berapa kira-kira jumlah pengguna BISINDO di Indonesia?',
                            'options' => [
                                ['label' => 'a', 'text' => 'Sekitar 50.000 orang', 'is_correct' => false],
                                ['label' => 'b', 'text' => 'Sekitar 500.000 orang', 'is_correct' => false],
                                ['label' => 'c', 'text' => 'Lebih dari 2 juta orang', 'is_correct' => true],
                                ['label' => 'd', 'text' => 'Kurang dari 10.000 orang', 'is_correct' => false],
                            ],
                        ],
                    ],
                ],
            ],

            'Matematika Dasar' => [
                [
                    'title' => 'Ujian Matematika: Operasi Hitung Dasar',
                    'description' => 'Menguji pemahaman operasi hitung dasar: penjumlahan, pengurangan, perkalian, dan pembagian.',
                    'time_limit' => 25,
                    'questions' => [
                        [
                            'text' => 'Hasil dari 347 + 285 = ...',
                            'options' => [
                                ['label' => 'a', 'text' => '622', 'is_correct' => false],
                                ['label' => 'b', 'text' => '632', 'is_correct' => true],
                                ['label' => 'c', 'text' => '642', 'is_correct' => false],
                                ['label' => 'd', 'text' => '532', 'is_correct' => false],
                            ],
                        ],
                        [
                            'text' => 'Hasil dari 1.000 - 467 = ...',
                            'options' => [
                                ['label' => 'a', 'text' => '543', 'is_correct' => false],
                                ['label' => 'b', 'text' => '433', 'is_correct' => false],
                                ['label' => 'c', 'text' => '533', 'is_correct' => true],
                                ['label' => 'd', 'text' => '637', 'is_correct' => false],
                            ],
                        ],
                        [
                            'text' => 'Hasil dari 24 × 15 = ...',
                            'options' => [
                                ['label' => 'a', 'text' => '340', 'is_correct' => false],
                                ['label' => 'b', 'text' => '350', 'is_correct' => false],
                                ['label' => 'c', 'text' => '360', 'is_correct' => true],
                                ['label' => 'd', 'text' => '370', 'is_correct' => false],
                            ],
                        ],
                        [
                            'text' => 'Hasil dari 756 ÷ 12 = ...',
                            'options' => [
                                ['label' => 'a', 'text' => '53', 'is_correct' => false],
                                ['label' => 'b', 'text' => '63', 'is_correct' => true],
                                ['label' => 'c', 'text' => '73', 'is_correct' => false],
                                ['label' => 'd', 'text' => '83', 'is_correct' => false],
                            ],
                        ],
                        [
                            'text' => 'Ibu membeli 3 kg jeruk seharga Rp15.000/kg dan 2 kg apel seharga Rp25.000/kg. Total belanja Ibu adalah...',
                            'options' => [
                                ['label' => 'a', 'text' => 'Rp85.000', 'is_correct' => false],
                                ['label' => 'b', 'text' => 'Rp95.000', 'is_correct' => true],
                                ['label' => 'c', 'text' => 'Rp90.000', 'is_correct' => false],
                                ['label' => 'd', 'text' => 'Rp100.000', 'is_correct' => false],
                            ],
                        ],
                    ],
                ],
                [
                    'title' => 'Ujian Matematika: Pecahan dan Geometri',
                    'description' => 'Menguji pemahaman pecahan, desimal, dan geometri dasar.',
                    'time_limit' => 30,
                    'questions' => [
                        [
                            'text' => 'Hasil dari 3/4 + 1/2 = ...',
                            'options' => [
                                ['label' => 'a', 'text' => '4/6', 'is_correct' => false],
                                ['label' => 'b', 'text' => '5/4 atau 1 1/4', 'is_correct' => true],
                                ['label' => 'c', 'text' => '4/4 atau 1', 'is_correct' => false],
                                ['label' => 'd', 'text' => '3/6 atau 1/2', 'is_correct' => false],
                            ],
                        ],
                        [
                            'text' => 'Luas persegi panjang dengan panjang 12 cm dan lebar 8 cm adalah...',
                            'options' => [
                                ['label' => 'a', 'text' => '40 cm²', 'is_correct' => false],
                                ['label' => 'b', 'text' => '80 cm²', 'is_correct' => false],
                                ['label' => 'c', 'text' => '96 cm²', 'is_correct' => true],
                                ['label' => 'd', 'text' => '20 cm²', 'is_correct' => false],
                            ],
                        ],
                        [
                            'text' => 'Keliling segitiga dengan sisi 5 cm, 7 cm, dan 10 cm adalah...',
                            'options' => [
                                ['label' => 'a', 'text' => '20 cm', 'is_correct' => false],
                                ['label' => 'b', 'text' => '22 cm', 'is_correct' => true],
                                ['label' => 'c', 'text' => '24 cm', 'is_correct' => false],
                                ['label' => 'd', 'text' => '35 cm', 'is_correct' => false],
                            ],
                        ],
                        [
                            'text' => 'Pecahan 3/5 jika diubah ke bentuk desimal menjadi...',
                            'options' => [
                                ['label' => 'a', 'text' => '0,35', 'is_correct' => false],
                                ['label' => 'b', 'text' => '0,6', 'is_correct' => true],
                                ['label' => 'c', 'text' => '0,53', 'is_correct' => false],
                                ['label' => 'd', 'text' => '0,75', 'is_correct' => false],
                            ],
                        ],
                        [
                            'text' => 'Sebuah lingkaran memiliki jari-jari 7 cm. Luas lingkaran tersebut adalah... (π = 22/7)',
                            'options' => [
                                ['label' => 'a', 'text' => '44 cm²', 'is_correct' => false],
                                ['label' => 'b', 'text' => '154 cm²', 'is_correct' => true],
                                ['label' => 'c', 'text' => '132 cm²', 'is_correct' => false],
                                ['label' => 'd', 'text' => '308 cm²', 'is_correct' => false],
                            ],
                        ],
                    ],
                ],
            ],

            'Ilmu Pengetahuan Alam (IPA)' => [
                [
                    'title' => 'Ujian IPA: Tubuh Manusia dan Tumbuhan',
                    'description' => 'Menguji pengetahuan tentang tubuh manusia dan dunia tumbuhan.',
                    'time_limit' => 20,
                    'questions' => [
                        [
                            'text' => 'Organ tubuh yang berfungsi memompa darah ke seluruh tubuh adalah...',
                            'options' => [
                                ['label' => 'a', 'text' => 'Paru-paru', 'is_correct' => false],
                                ['label' => 'b', 'text' => 'Jantung', 'is_correct' => true],
                                ['label' => 'c', 'text' => 'Hati', 'is_correct' => false],
                                ['label' => 'd', 'text' => 'Ginjal', 'is_correct' => false],
                            ],
                        ],
                        [
                            'text' => 'Bagian telinga yang mengubah getaran suara menjadi sinyal listrik untuk dikirim ke otak adalah...',
                            'options' => [
                                ['label' => 'a', 'text' => 'Daun telinga', 'is_correct' => false],
                                ['label' => 'b', 'text' => 'Gendang telinga', 'is_correct' => false],
                                ['label' => 'c', 'text' => 'Koklea (rumah siput)', 'is_correct' => true],
                                ['label' => 'd', 'text' => 'Saluran telinga', 'is_correct' => false],
                            ],
                        ],
                        [
                            'text' => 'Proses tumbuhan membuat makanan sendiri menggunakan cahaya matahari disebut...',
                            'options' => [
                                ['label' => 'a', 'text' => 'Respirasi', 'is_correct' => false],
                                ['label' => 'b', 'text' => 'Fotosintesis', 'is_correct' => true],
                                ['label' => 'c', 'text' => 'Evaporasi', 'is_correct' => false],
                                ['label' => 'd', 'text' => 'Fermentasi', 'is_correct' => false],
                            ],
                        ],
                        [
                            'text' => 'Bagian tumbuhan yang berfungsi menyerap air dan mineral dari tanah adalah...',
                            'options' => [
                                ['label' => 'a', 'text' => 'Batang', 'is_correct' => false],
                                ['label' => 'b', 'text' => 'Daun', 'is_correct' => false],
                                ['label' => 'c', 'text' => 'Akar', 'is_correct' => true],
                                ['label' => 'd', 'text' => 'Bunga', 'is_correct' => false],
                            ],
                        ],
                        [
                            'text' => 'Indera manusia yang paling dominan digunakan oleh siswa tunarungu dalam belajar adalah...',
                            'options' => [
                                ['label' => 'a', 'text' => 'Penciuman', 'is_correct' => false],
                                ['label' => 'b', 'text' => 'Pengecapan', 'is_correct' => false],
                                ['label' => 'c', 'text' => 'Penglihatan', 'is_correct' => true],
                                ['label' => 'd', 'text' => 'Perabaan', 'is_correct' => false],
                            ],
                        ],
                    ],
                ],
                [
                    'title' => 'Ujian IPA: Energi dan Siklus Air',
                    'description' => 'Menguji pemahaman tentang bentuk energi dan siklus air.',
                    'time_limit' => 20,
                    'questions' => [
                        [
                            'text' => 'Sumber energi terbesar di bumi adalah...',
                            'options' => [
                                ['label' => 'a', 'text' => 'Angin', 'is_correct' => false],
                                ['label' => 'b', 'text' => 'Matahari', 'is_correct' => true],
                                ['label' => 'c', 'text' => 'Air', 'is_correct' => false],
                                ['label' => 'd', 'text' => 'Batu bara', 'is_correct' => false],
                            ],
                        ],
                        [
                            'text' => 'Proses berubahnya air menjadi uap air disebut...',
                            'options' => [
                                ['label' => 'a', 'text' => 'Kondensasi', 'is_correct' => false],
                                ['label' => 'b', 'text' => 'Presipitasi', 'is_correct' => false],
                                ['label' => 'c', 'text' => 'Evaporasi', 'is_correct' => true],
                                ['label' => 'd', 'text' => 'Infiltrasi', 'is_correct' => false],
                            ],
                        ],
                        [
                            'text' => 'Contoh perubahan energi listrik menjadi energi cahaya adalah...',
                            'options' => [
                                ['label' => 'a', 'text' => 'Kipas angin', 'is_correct' => false],
                                ['label' => 'b', 'text' => 'Lampu bohlam', 'is_correct' => true],
                                ['label' => 'c', 'text' => 'Setrika', 'is_correct' => false],
                                ['label' => 'd', 'text' => 'Radio', 'is_correct' => false],
                            ],
                        ],
                        [
                            'text' => 'Hujan terjadi karena proses...',
                            'options' => [
                                ['label' => 'a', 'text' => 'Evaporasi → Kondensasi → Presipitasi', 'is_correct' => true],
                                ['label' => 'b', 'text' => 'Kondensasi → Evaporasi → Presipitasi', 'is_correct' => false],
                                ['label' => 'c', 'text' => 'Presipitasi → Evaporasi → Kondensasi', 'is_correct' => false],
                                ['label' => 'd', 'text' => 'Infiltrasi → Kondensasi → Evaporasi', 'is_correct' => false],
                            ],
                        ],
                        [
                            'text' => 'Energi yang tersimpan dalam baterai termasuk jenis energi...',
                            'options' => [
                                ['label' => 'a', 'text' => 'Energi kinetik', 'is_correct' => false],
                                ['label' => 'b', 'text' => 'Energi panas', 'is_correct' => false],
                                ['label' => 'c', 'text' => 'Energi kimia', 'is_correct' => true],
                                ['label' => 'd', 'text' => 'Energi nuklir', 'is_correct' => false],
                            ],
                        ],
                    ],
                ],
            ],

            'Bahasa Indonesia' => [
                [
                    'title' => 'Ujian Bahasa Indonesia: Membaca dan Menulis',
                    'description' => 'Menguji kemampuan membaca pemahaman dan menulis kalimat efektif.',
                    'time_limit' => 25,
                    'questions' => [
                        [
                            'text' => 'Kalimat efektif harus memiliki unsur utama, yaitu...',
                            'options' => [
                                ['label' => 'a', 'text' => 'Subjek dan Objek', 'is_correct' => false],
                                ['label' => 'b', 'text' => 'Subjek dan Predikat', 'is_correct' => true],
                                ['label' => 'c', 'text' => 'Predikat dan Keterangan', 'is_correct' => false],
                                ['label' => 'd', 'text' => 'Objek dan Keterangan', 'is_correct' => false],
                            ],
                        ],
                        [
                            'text' => 'Tanda baca yang digunakan di akhir kalimat tanya adalah...',
                            'options' => [
                                ['label' => 'a', 'text' => 'Titik (.)', 'is_correct' => false],
                                ['label' => 'b', 'text' => 'Koma (,)', 'is_correct' => false],
                                ['label' => 'c', 'text' => 'Tanda seru (!)', 'is_correct' => false],
                                ['label' => 'd', 'text' => 'Tanda tanya (?)', 'is_correct' => true],
                            ],
                        ],
                        [
                            'text' => 'Sinonim dari kata "gembira" adalah...',
                            'options' => [
                                ['label' => 'a', 'text' => 'Sedih', 'is_correct' => false],
                                ['label' => 'b', 'text' => 'Senang', 'is_correct' => true],
                                ['label' => 'c', 'text' => 'Marah', 'is_correct' => false],
                                ['label' => 'd', 'text' => 'Takut', 'is_correct' => false],
                            ],
                        ],
                        [
                            'text' => 'Antonim dari kata "rajin" adalah...',
                            'options' => [
                                ['label' => 'a', 'text' => 'Pintar', 'is_correct' => false],
                                ['label' => 'b', 'text' => 'Bodoh', 'is_correct' => false],
                                ['label' => 'c', 'text' => 'Malas', 'is_correct' => true],
                                ['label' => 'd', 'text' => 'Lambat', 'is_correct' => false],
                            ],
                        ],
                        [
                            'text' => 'Paragraf yang kalimat utamanya terletak di awal paragraf disebut paragraf...',
                            'options' => [
                                ['label' => 'a', 'text' => 'Induktif', 'is_correct' => false],
                                ['label' => 'b', 'text' => 'Deduktif', 'is_correct' => true],
                                ['label' => 'c', 'text' => 'Campuran', 'is_correct' => false],
                                ['label' => 'd', 'text' => 'Naratif', 'is_correct' => false],
                            ],
                        ],
                    ],
                ],
            ],

            'Ilmu Pengetahuan Sosial (IPS)' => [
                [
                    'title' => 'Ujian IPS: Lingkungan dan Budaya',
                    'description' => 'Menguji pemahaman tentang lingkungan sosial dan keragaman budaya Indonesia.',
                    'time_limit' => 20,
                    'questions' => [
                        [
                            'text' => 'Norma yang paling tegas dan memiliki sanksi hukum adalah norma...',
                            'options' => [
                                ['label' => 'a', 'text' => 'Agama', 'is_correct' => false],
                                ['label' => 'b', 'text' => 'Kesopanan', 'is_correct' => false],
                                ['label' => 'c', 'text' => 'Kesusilaan', 'is_correct' => false],
                                ['label' => 'd', 'text' => 'Hukum', 'is_correct' => true],
                            ],
                        ],
                        [
                            'text' => 'Indonesia memiliki berapa provinsi?',
                            'options' => [
                                ['label' => 'a', 'text' => '34 provinsi', 'is_correct' => false],
                                ['label' => 'b', 'text' => '37 provinsi', 'is_correct' => false],
                                ['label' => 'c', 'text' => '38 provinsi', 'is_correct' => true],
                                ['label' => 'd', 'text' => '36 provinsi', 'is_correct' => false],
                            ],
                        ],
                        [
                            'text' => 'Semboyan "Bhinneka Tunggal Ika" berarti...',
                            'options' => [
                                ['label' => 'a', 'text' => 'Bersatu kita teguh', 'is_correct' => false],
                                ['label' => 'b', 'text' => 'Berbeda-beda tetapi tetap satu jua', 'is_correct' => true],
                                ['label' => 'c', 'text' => 'Satu nusa satu bangsa', 'is_correct' => false],
                                ['label' => 'd', 'text' => 'Gotong royong untuk semua', 'is_correct' => false],
                            ],
                        ],
                        [
                            'text' => 'UU No. 8 Tahun 2016 mengatur tentang...',
                            'options' => [
                                ['label' => 'a', 'text' => 'Pendidikan Nasional', 'is_correct' => false],
                                ['label' => 'b', 'text' => 'Penyandang Disabilitas', 'is_correct' => true],
                                ['label' => 'c', 'text' => 'Ketenagakerjaan', 'is_correct' => false],
                                ['label' => 'd', 'text' => 'Perlindungan Anak', 'is_correct' => false],
                            ],
                        ],
                        [
                            'text' => 'Kegiatan ekonomi yang menghasilkan barang atau jasa disebut...',
                            'options' => [
                                ['label' => 'a', 'text' => 'Distribusi', 'is_correct' => false],
                                ['label' => 'b', 'text' => 'Konsumsi', 'is_correct' => false],
                                ['label' => 'c', 'text' => 'Produksi', 'is_correct' => true],
                                ['label' => 'd', 'text' => 'Ekspor', 'is_correct' => false],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    // ──────────────────────────────────────────────────────────────
    //  DATA PBL CASES
    // ──────────────────────────────────────────────────────────────

    private function getPblCasesData(): array
    {
        return [
            'Bahasa Isyarat Indonesia (BISINDO)' => [
                [
                    'case_number' => 1,
                    'title' => 'Membuat Video Perkenalan Diri dalam BISINDO',
                    'description' => 'Siswa diminta membuat video perkenalan diri menggunakan BISINDO yang mencakup nama, umur, hobi, dan cita-cita. Video harus menunjukkan isyarat yang benar dan ekspresi wajah yang tepat.',
                    'time_limit' => 60,
                    'sections' => [
                        [
                            'title' => 'Petunjuk Tugas',
                            'items' => [
                                ['type' => 'text', 'content' => 'Buatlah video perkenalan diri dalam BISINDO berdurasi 1-2 menit. Pastikan isyarat yang kamu gunakan jelas dan ekspresi wajah sesuai.'],
                                ['type' => 'text', 'content' => 'Video harus mencakup: (1) Salam pembuka, (2) Nama lengkap, (3) Umur, (4) Sekolah, (5) Hobi, (6) Cita-cita, (7) Salam penutup.'],
                            ],
                        ],
                        [
                            'title' => 'Kriteria Penilaian',
                            'items' => [
                                ['type' => 'text', 'content' => 'Ketepatan isyarat (30%), Kelancaran (25%), Ekspresi wajah dan gestur (25%), Kelengkapan informasi (20%).'],
                            ],
                        ],
                    ],
                ],
            ],
            'Matematika Dasar' => [
                [
                    'case_number' => 2,
                    'title' => 'Menghitung Anggaran Belanja Kelas',
                    'description' => 'Siswa bekerja dalam kelompok untuk merencanakan anggaran belanja kebutuhan kelas selama satu bulan. Mereka harus menghitung total biaya, mencari penawaran terbaik, dan menyusun laporan keuangan sederhana.',
                    'time_limit' => 90,
                    'sections' => [
                        [
                            'title' => 'Skenario Masalah',
                            'items' => [
                                ['type' => 'text', 'content' => 'Kelas kalian mendapat dana Rp500.000 per bulan untuk kebutuhan kelas. Kebutuhan yang harus dipenuhi: alat tulis, kertas, spidol whiteboard, tisu, sabun cuci tangan, dan snack untuk 2 kali pertemuan.'],
                                ['type' => 'text', 'content' => 'Daftar harga: Spidol whiteboard Rp15.000/pcs, Kertas HVS Rp45.000/rim, Tisu Rp12.000/pack, Sabun Rp8.000/botol, Pulpen Rp3.000/pcs (30 siswa), Snack Rp5.000/porsi.'],
                            ],
                        ],
                        [
                            'title' => 'Tugas',
                            'items' => [
                                ['type' => 'text', 'content' => '1. Hitung total biaya semua kebutuhan. 2. Apakah dana Rp500.000 cukup? 3. Jika tidak cukup, tentukan prioritas belanja. 4. Buat tabel laporan keuangan sederhana.'],
                            ],
                        ],
                    ],
                ],
            ],
            'Ilmu Pengetahuan Alam (IPA)' => [
                [
                    'case_number' => 3,
                    'title' => 'Eksperimen Siklus Air Mini',
                    'description' => 'Siswa membuat model siklus air sederhana menggunakan bahan-bahan yang mudah didapat dan mendokumentasikan proses evaporasi, kondensasi, dan presipitasi yang terjadi.',
                    'time_limit' => 120,
                    'sections' => [
                        [
                            'title' => 'Alat dan Bahan',
                            'items' => [
                                ['type' => 'text', 'content' => 'Alat: Mangkuk besar, gelas kecil, plastik wrap, karet gelang, batu kerikil kecil. Bahan: Air hangat, pewarna makanan biru.'],
                            ],
                        ],
                        [
                            'title' => 'Langkah Percobaan',
                            'items' => [
                                ['type' => 'text', 'content' => '1. Tuangkan air hangat yang sudah diberi pewarna biru ke mangkuk besar (setinggi 3 cm). 2. Letakkan gelas kecil kosong di tengah mangkuk. 3. Tutup mangkuk dengan plastik wrap dan kencangkan dengan karet gelang. 4. Letakkan batu kerikil di atas plastik wrap, tepat di atas gelas kecil. 5. Letakkan mangkuk di bawah sinar matahari selama 2-3 jam. 6. Amati dan catat apa yang terjadi setiap 30 menit.'],
                            ],
                        ],
                        [
                            'title' => 'Pertanyaan Analisis',
                            'items' => [
                                ['type' => 'text', 'content' => '1. Apa yang terjadi pada air di mangkuk? (Evaporasi) 2. Mengapa ada tetesan air di plastik wrap? (Kondensasi) 3. Mengapa air menetes ke gelas kecil? (Presipitasi) 4. Hubungkan eksperimen ini dengan siklus air di alam.'],
                            ],
                        ],
                    ],
                ],
            ],
            'Bahasa Indonesia' => [
                [
                    'case_number' => 4,
                    'title' => 'Menulis Cerita Pengalaman Pribadi',
                    'description' => 'Siswa menulis cerita pendek berdasarkan pengalaman pribadi yang berkesan. Cerita harus memiliki struktur yang jelas (orientasi, komplikasi, resolusi) dan menggunakan bahasa yang baik dan benar.',
                    'time_limit' => 60,
                    'sections' => [
                        [
                            'title' => 'Petunjuk Menulis',
                            'items' => [
                                ['type' => 'text', 'content' => 'Tulislah cerita pendek (minimal 3 paragraf) tentang pengalaman pribadimu yang paling berkesan. Cerita harus memiliki: judul yang menarik, paragraf pembuka (orientasi), inti cerita (komplikasi), dan penutup (resolusi).'],
                                ['type' => 'text', 'content' => 'Tips: Gunakan kalimat yang jelas dan efektif. Sertakan detail tentang tempat, waktu, dan perasaanmu. Gunakan tanda baca dengan benar.'],
                            ],
                        ],
                    ],
                ],
            ],
            'Ilmu Pengetahuan Sosial (IPS)' => [
                [
                    'case_number' => 5,
                    'title' => 'Proyek Peta Budaya Daerahku',
                    'description' => 'Siswa membuat poster atau infografis tentang kebudayaan daerah tempat tinggal mereka, mencakup pakaian adat, makanan khas, tarian, bahasa daerah, dan tempat wisata.',
                    'time_limit' => 90,
                    'sections' => [
                        [
                            'title' => 'Petunjuk Proyek',
                            'items' => [
                                ['type' => 'text', 'content' => 'Buatlah poster atau infografis (boleh digital/manual) tentang kebudayaan daerah tempat tinggalmu. Poster harus mencakup: (1) Nama provinsi dan kabupaten/kota, (2) Pakaian adat, (3) Makanan khas minimal 3, (4) Tarian daerah, (5) Bahasa daerah dan contoh kalimat, (6) Tempat wisata terkenal.'],
                            ],
                        ],
                        [
                            'title' => 'Kriteria Penilaian',
                            'items' => [
                                ['type' => 'text', 'content' => 'Kelengkapan informasi (30%), Kreativitas desain (25%), Keakuratan data (25%), Kerapihan (20%).'],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    // ══════════════════════════════════════════════════════════════
    //  RUN
    // ══════════════════════════════════════════════════════════════

    public function run(): void
    {
        $this->command->info('');
        $this->command->info('╔══════════════════════════════════════════════════════╗');
        $this->command->info('║  🚀 IsyaratPintar — Full Presentation Data Seeder  ║');
        $this->command->info('╚══════════════════════════════════════════════════════╝');
        $this->command->newLine();

        // ── 1. Mata Pelajaran ──
        $this->command->info('📚 [1/9] Membuat Mata Pelajaran...');
        $mataPelajaranMap = $this->seedMataPelajaran();

        // ── 2. Users (Admin + Guru + Siswa) ──
        $this->command->info('👥 [2/9] Membuat User (Admin, Guru, Siswa)...');
        [$admin, $guru, $students] = $this->seedUsers();

        // ── 3. Kelas ──
        $this->command->info('🏫 [3/9] Membuat Kelas dan Enrolling Siswa...');
        $kelas = $this->seedKelas($guru, $students);

        // ── 4. Lessons ──
        $this->command->info('📖 [4/9] Membuat Materi Pelajaran...');
        $lessons = $this->seedLessons($mataPelajaranMap, $kelas);

        // ── 5. Assessments + Questions + Options ──
        $this->command->info('📝 [5/9] Membuat Assessment, Soal, dan Pilihan Jawaban...');
        $assessments = $this->seedAssessments($mataPelajaranMap, $kelas);

        // ── 6. PBL Cases ──
        $this->command->info('🧩 [6/9] Membuat Problem-Based Learning Cases...');
        $pblCases = $this->seedPblCases($mataPelajaranMap, $kelas);

        // ── 7. Student Activity Data ──
        $this->command->info('📊 [7/9] Membuat Data Aktivitas Siswa...');
        $this->seedStudentData($students, $mataPelajaranMap, $assessments, $lessons, $pblCases);

        // ── 8. User-Lesson Progress ──
        $this->command->info('✅ [8/9] Membuat Progress Pelajaran Siswa...');
        $this->seedLessonProgress($students, $lessons);

        // ── 9. WASPAS Risk Profiling ──
        $this->command->info('🔬 [9/9] Menghitung WASPAS Risk Profiles...');
        $this->calculateRiskProfiles();

        $this->command->newLine();
        $this->command->info('╔══════════════════════════════════════════════════════╗');
        $this->command->info('║  ✅ Semua data berhasil di-seed!                    ║');
        $this->command->info('╚══════════════════════════════════════════════════════╝');
        $this->command->newLine();
        $this->command->info('📋 Ringkasan Data:');
        $this->command->table(
            ['Entitas', 'Jumlah'],
            [
                ['Mata Pelajaran', count($mataPelajaranMap)],
                ['User (Admin)', 1],
                ['User (Guru)', 1],
                ['User (Siswa)', count($students)],
                ['Kelas', 1],
                ['Materi Pelajaran', Lesson::count()],
                ['Assessment', Assessment::count()],
                ['Soal (Questions)', Question::count()],
                ['PBL Cases', PblCase::count()],
                ['Assessment Attempts', AssessmentAttempt::count()],
                ['Activity Logs', StudentActivityLog::count()],
                ['Session Durations', SessionDuration::count()],
                ['Subject Mastery', SubjectMastery::count()],
                ['Risk Profiles', StudentRiskProfile::count()],
            ]
        );

        $this->command->newLine();
        $this->command->info('🔑 Akun Login:');
        $this->command->table(
            ['Role', 'Email', 'Password'],
            [
                ['Admin', 'admin@isyaratpintar.id', 'password123'],
                ['Guru', 'bu.ratna@guru.isyaratpintar.id', 'password123'],
                ['Siswa (contoh)', 'aisyah@siswa.isyaratpintar.id', 'password123'],
            ]
        );
    }

    // ──────────────────────────────────────────────────────────────
    //  SEED HELPERS
    // ──────────────────────────────────────────────────────────────

    private function seedMataPelajaran(): array
    {
        $map = [];
        foreach ($this->subjects as $name) {
            $mp = MataPelajaran::firstOrCreate(['name' => $name]);
            $map[$name] = $mp->id;
            $this->command->comment("   ✓ {$name}");
        }
        return $map;
    }

    private function seedUsers(): array
    {
        // Admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@isyaratpintar.id'],
            [
                'name' => 'Administrator IsyaratPintar',
                'username' => 'admin',
                'password' => Hash::make('password123'),
                'role' => UserRole::ADMIN,
                'is_active' => true,
            ]
        );
        $this->command->comment("   ✓ Admin: {$admin->email}");

        // Guru
        $guru = User::firstOrCreate(
            ['email' => 'bu.ratna@guru.isyaratpintar.id'],
            [
                'name' => 'Ratna Dewi Anggraini, S.Pd.',
                'username' => 'bu.ratna',
                'password' => Hash::make('password123'),
                'role' => UserRole::GURU,
                'is_active' => true,
            ]
        );
        $this->command->comment("   ✓ Guru: {$guru->name}");

        // Siswa
        $students = [];
        foreach ($this->studentProfiles as $profile) {
            $student = User::firstOrCreate(
                ['email' => $profile['email']],
                [
                    'name' => $profile['name'],
                    'username' => explode('@', $profile['email'])[0],
                    'password' => Hash::make('password123'),
                    'role' => UserRole::SISWA,
                    'is_active' => true,
                ]
            );

            if ($student->name !== $profile['name']) {
                $student->update(['name' => $profile['name']]);
            }

            $students[] = $student;
            $this->command->comment("   ✓ Siswa: {$student->name}");
        }

        return [$admin, $guru, $students];
    }

    private function seedKelas(User $guru, array $students): Kelas
    {
        $kelas = Kelas::firstOrCreate(
            ['nama' => 'Kelas 5 - Inklusi Tunarungu'],
            ['guru_id' => $guru->id]
        );

        // Enroll semua siswa
        foreach ($students as $student) {
            if (!$kelas->siswa()->where('users.id', $student->id)->exists()) {
                $kelas->siswa()->attach($student->id, [
                    'enrolled_at' => now()->subDays(rand(30, 90)),
                ]);
            }
        }

        $this->command->comment("   ✓ {$kelas->nama} — {$kelas->siswa()->count()} siswa enrolled");
        return $kelas;
    }

    private function seedLessons(array $mataPelajaranMap, Kelas $kelas): array
    {
        $lessons = [];
        $lessonsData = $this->getLessonsData();

        foreach ($lessonsData as $subjectName => $subjectLessons) {
            $mpId = $mataPelajaranMap[$subjectName] ?? null;
            if (!$mpId) continue;

            foreach ($subjectLessons as $lessonData) {
                $lesson = Lesson::firstOrCreate(
                    ['title' => $lessonData['title']],
                    [
                        'mata_pelajaran_id' => $mpId,
                        'kelas_id' => $kelas->id,
                        'description' => $lessonData['description'],
                        'duration' => $lessonData['duration'],
                    ]
                );

                $lessons[] = $lesson;
                $this->command->comment("   ✓ [{$subjectName}] {$lesson->title}");
            }
        }

        return $lessons;
    }

    private function seedAssessments(array $mataPelajaranMap, Kelas $kelas): array
    {
        $assessments = [];
        $assessmentsData = $this->getAssessmentsData();

        foreach ($assessmentsData as $subjectName => $subjectAssessments) {
            $mpId = $mataPelajaranMap[$subjectName] ?? null;
            if (!$mpId) continue;

            foreach ($subjectAssessments as $assessmentData) {
                $slug = Str::slug($assessmentData['title']);
                $assessment = Assessment::firstOrCreate(
                    ['slug' => $slug],
                    [
                        'title' => $assessmentData['title'],
                        'description' => $assessmentData['description'],
                        'time_limit' => $assessmentData['time_limit'],
                        'mata_pelajaran_id' => $mpId,
                        'kelas_id' => $kelas->id,
                    ]
                );

                // Seed questions and options
                foreach ($assessmentData['questions'] as $qData) {
                    $question = $assessment->questions()->firstOrCreate(
                        ['text' => $qData['text']],
                        ['text' => $qData['text']]
                    );

                    foreach ($qData['options'] as $optData) {
                        $question->options()->firstOrCreate(
                            ['label' => $optData['label'], 'question_id' => $question->id],
                            [
                                'text' => $optData['text'],
                                'is_correct' => $optData['is_correct'],
                            ]
                        );
                    }
                }

                $assessments[] = $assessment;
                $this->command->comment("   ✓ [{$subjectName}] {$assessment->title} ({$assessment->questions()->count()} soal)");
            }
        }

        return $assessments;
    }

    private function seedPblCases(array $mataPelajaranMap, Kelas $kelas): array
    {
        $pblCases = [];
        $pblData = $this->getPblCasesData();

        foreach ($pblData as $subjectName => $cases) {
            $mpId = $mataPelajaranMap[$subjectName] ?? null;
            if (!$mpId) continue;

            foreach ($cases as $caseData) {
                $slug = Str::slug($caseData['title']);
                $pblCase = PblCase::firstOrCreate(
                    ['slug' => $slug],
                    [
                        'case_number' => $caseData['case_number'],
                        'title' => $caseData['title'],
                        'mata_pelajaran_id' => $mpId,
                        'kelas_id' => $kelas->id,
                        'description' => $caseData['description'],
                        'time_limit' => $caseData['time_limit'],
                        'start_date' => now()->subDays(14),
                        'deadline' => now()->addDays(14),
                    ]
                );

                // Seed sections and items
                foreach ($caseData['sections'] as $sIndex => $sectionData) {
                    $section = CaseSection::firstOrCreate(
                        ['case_id' => $pblCase->id, 'title' => $sectionData['title']],
                        ['order' => $sIndex + 1]
                    );

                    foreach ($sectionData['items'] as $iIndex => $itemData) {
                        CaseSectionItem::firstOrCreate(
                            ['section_id' => $section->id, 'content' => $itemData['content']],
                            [
                                'type' => $itemData['type'],
                                'order' => $iIndex + 1,
                            ]
                        );
                    }
                }

                $pblCases[] = $pblCase;
                $this->command->comment("   ✓ [{$subjectName}] {$pblCase->title}");
            }
        }

        return $pblCases;
    }

    // ──────────────────────────────────────────────────────────────
    //  SEED STUDENT ACTIVITY DATA (CONTROLLED FOR WASPAS)
    // ──────────────────────────────────────────────────────────────

    private function seedStudentData(array $students, array $mataPelajaranMap, array $assessments, array $lessons, array $pblCases): void
    {
        foreach ($students as $index => $student) {
            $profile = $this->studentProfiles[$index];
            $this->command->comment("   📝 [{$student->id}] {$student->name}");

            $this->seedAttendance($student, $profile['attendance_days']);
            $this->seedEngagement($student, $profile['engagement_total_minutes']);
            $this->seedPerformance($student, $profile['performance_scores'], $mataPelajaranMap, $assessments);
            $this->seedSubjectMastery($student, $profile['mastery'], $mataPelajaranMap);
            $this->seedPblSubmissions($student, $pblCases, $profile);
        }
    }

    private function seedAttendance(User $student, int $activeDays): void
    {
        StudentActivityLog::where('user_id', $student->id)->delete();

        if ($activeDays <= 0) {
            $this->command->comment("      ⤷ Attendance: 0 hari (tidak aktif)");
            return;
        }

        $availableDays = range(0, 6);
        shuffle($availableDays);
        $selectedDays = array_slice($availableDays, 0, min($activeDays, 7));

        foreach ($selectedDays as $daysAgo) {
            $date = Carbon::now()->subDays($daysAgo);

            StudentActivityLog::create([
                'user_id' => $student->id,
                'activity_type' => 'session_started',
                'logged_at' => $date->copy()->setTime(rand(7, 10), rand(0, 59)),
            ]);

            StudentActivityLog::create([
                'user_id' => $student->id,
                'activity_type' => 'lesson_viewed',
                'logged_at' => $date->copy()->setTime(rand(10, 14), rand(0, 59)),
            ]);

            if (rand(0, 1)) {
                StudentActivityLog::create([
                    'user_id' => $student->id,
                    'activity_type' => 'attempt_completed',
                    'logged_at' => $date->copy()->setTime(rand(14, 16), rand(0, 59)),
                ]);
            }
        }

        $this->command->comment("      ⤷ Attendance: {$activeDays} hari aktif");
    }

    private function seedEngagement(User $student, int $totalMinutes): void
    {
        SessionDuration::where('user_id', $student->id)->delete();

        if ($totalMinutes <= 0) {
            $this->command->comment("      ⤷ Engagement: 0 menit");
            return;
        }

        $remainingMinutes = $totalMinutes;
        $sessionsCount = max(1, intval($totalMinutes / 45));
        $sessionsCount = min($sessionsCount, 40);

        for ($i = 0; $i < $sessionsCount && $remainingMinutes > 0; $i++) {
            $avgPerSession = $remainingMinutes / ($sessionsCount - $i);
            $durationMinutes = max(5, intval($avgPerSession + rand(-10, 10)));
            $durationMinutes = min($durationMinutes, $remainingMinutes);

            $daysAgo = rand(0, 29);
            $startHour = rand(8, 18);
            $startMinute = rand(0, 59);
            $startedAt = Carbon::now()->subDays($daysAgo)->setTime($startHour, $startMinute);
            $endedAt = $startedAt->copy()->addMinutes($durationMinutes);

            SessionDuration::create([
                'user_id' => $student->id,
                'session_type' => 'course_learning',
                'duration_seconds' => $durationMinutes * 60,
                'started_at' => $startedAt,
                'ended_at' => $endedAt,
            ]);

            $remainingMinutes -= $durationMinutes;
        }

        $this->command->comment("      ⤷ Engagement: {$totalMinutes} menit ({$sessionsCount} sesi)");
    }

    private function seedPerformance(User $student, array $scores, array $mataPelajaranMap, array $assessments): void
    {
        AssessmentAttempt::where('user_id', $student->id)->delete();

        $mataPelajaranIds = array_values($mataPelajaranMap);

        // Use real assessments if enough, otherwise create dummy ones
        $availableAssessments = count($assessments) >= count($scores)
            ? array_slice($assessments, 0, count($scores))
            : $this->ensureDummyAssessments(count($scores), $mataPelajaranIds);

        foreach ($scores as $i => $score) {
            $assessment = $availableAssessments[$i % count($availableAssessments)];
            $daysAgo = rand(1, 30);
            $startedAt = Carbon::now()->subDays($daysAgo)->setTime(rand(8, 15), rand(0, 59));

            AssessmentAttempt::create([
                'user_id' => $student->id,
                'assessment_id' => $assessment->id,
                'status' => 'COMPLETED',
                'score' => $score,
                'level' => AssessmentAttempt::determineLevel($score),
                'started_at' => $startedAt,
                'completed_at' => $startedAt->copy()->addMinutes(rand(10, 25)),
            ]);
        }

        $avgScore = round(array_sum($scores) / count($scores), 1);
        $this->command->comment("      ⤷ Performance: {$avgScore} avg (" . count($scores) . " attempts)");
    }

    private function ensureDummyAssessments(int $count, array $mataPelajaranIds): array
    {
        $assessments = [];
        for ($i = 0; $i < $count; $i++) {
            $mpId = $mataPelajaranIds[$i % count($mataPelajaranIds)];
            $assessments[] = Assessment::firstOrCreate(
                ['slug' => 'spk-dummy-assessment-' . ($i + 1)],
                [
                    'title' => 'Ujian SPK Dummy ' . ($i + 1),
                    'description' => 'Assessment dummy untuk seeding SPK',
                    'time_limit' => 30,
                    'mata_pelajaran_id' => $mpId,
                ]
            );
        }
        return $assessments;
    }

    private function seedSubjectMastery(User $student, array $masteryData, array $mataPelajaranMap): void
    {
        SubjectMastery::where('user_id', $student->id)->delete();

        foreach ($masteryData as $subjectName => $percentage) {
            $mpId = $mataPelajaranMap[$subjectName] ?? null;
            if (!$mpId) continue;

            SubjectMastery::create([
                'user_id' => $student->id,
                'mata_pelajaran_id' => $mpId,
                'mastery_percentage' => $percentage,
                'average_score' => $percentage,
                'assessments_completed' => rand(3, 10),
                'status' => $this->determineMasteryStatus($percentage),
                'last_assessed_at' => now()->subDays(rand(0, 7)),
                'calculated_at' => now(),
            ]);
        }

        $avgMastery = round(array_sum($masteryData) / count($masteryData), 1);
        $this->command->comment("      ⤷ Subject Mastery: {$avgMastery}% avg (" . count($masteryData) . " mapel)");
    }

    private function seedPblSubmissions(User $student, array $pblCases, array $profile): void
    {
        CaseSubmission::where('user_id', $student->id)->delete();

        $avgPerf = array_sum($profile['performance_scores']) / count($profile['performance_scores']);

        foreach ($pblCases as $pblCase) {
            // Not all students submit — probability based on engagement level
            if ($profile['engagement_total_minutes'] < 100 && rand(0, 1) === 0) {
                continue;
            }

            $score = max(0, min(100, $avgPerf + rand(-15, 15)));
            $feedbacks = [
                'Bagus, terus tingkatkan kemampuanmu!',
                'Perlu perbaikan pada bagian analisis.',
                'Kerja yang sangat baik, pemahaman konsep sudah tepat.',
                'Cukup baik, tapi masih perlu latihan lebih.',
                'Perlu usaha lebih dalam menyelesaikan tugas.',
                'Sangat memuaskan! Kamu menunjukkan pemahaman yang mendalam.',
            ];

            CaseSubmission::create([
                'user_id' => $student->id,
                'case_id' => $pblCase->id,
                'answer' => 'Jawaban siswa untuk tugas: ' . $pblCase->title,
                'submitted_at' => now()->subDays(rand(1, 14)),
                'score' => round($score, 2),
                'feedback' => $feedbacks[array_rand($feedbacks)],
            ]);
        }
    }

    private function seedLessonProgress(array $students, array $lessons): void
    {
        foreach ($students as $index => $student) {
            $profile = $this->studentProfiles[$index];
            $completionRate = $profile['engagement_total_minutes'] / 1500; // Normalize to 0-1

            foreach ($lessons as $lesson) {
                // Check if already exists
                $exists = \DB::table('user_lessons')
                    ->where('user_id', $student->id)
                    ->where('lesson_id', $lesson->id)
                    ->exists();

                if ($exists) continue;

                $completed = (rand(0, 100) / 100) < $completionRate;

                \DB::table('user_lessons')->insert([
                    'user_id' => $student->id,
                    'lesson_id' => $lesson->id,
                    'completed' => $completed,
                    'completed_at' => $completed ? now()->subDays(rand(1, 30)) : null,
                    'created_at' => now()->subDays(rand(30, 60)),
                    'updated_at' => now(),
                ]);
            }

            $completedCount = \DB::table('user_lessons')
                ->where('user_id', $student->id)
                ->where('completed', true)
                ->count();
            $totalCount = count($lessons);

            $this->command->comment("   ✓ {$student->name}: {$completedCount}/{$totalCount} materi selesai");
        }
    }

    // ──────────────────────────────────────────────────────────────
    //  WASPAS RISK PROFILING
    // ──────────────────────────────────────────────────────────────

    private function calculateRiskProfiles(): void
    {
        $dummyEmails = array_column($this->studentProfiles, 'email');
        $dummyUserIds = User::whereIn('email', $dummyEmails)->pluck('id');

        StudentRiskProfile::whereIn('user_id', $dummyUserIds)->delete();
        TeacherInsight::whereIn('student_id', $dummyUserIds)->delete();

        try {
            $service = app(\App\Services\StudentRiskProfileService::class);
            $profiles = $service->calculateAllRiskProfiles();

            $this->command->newLine();
            $this->command->info('╔══════════════════════════════════════════════════════╗');
            $this->command->info('║  📊 Hasil Perhitungan WASPAS                        ║');
            $this->command->info('╚══════════════════════════════════════════════════════╝');
            $this->command->newLine();

            // Tampilkan konfigurasi WASPAS
            $config = config('spk.waspas');
            $this->command->info('⚙️  Konfigurasi WASPAS:');
            $this->command->info("   λ (lambda) = {$config['lambda']}");
            $this->command->info('   Kriteria dan Bobot:');

            $criteriaRows = [];
            foreach ($config['criteria'] as $key => $criterion) {
                $criteriaRows[] = [
                    $criterion['label'],
                    $key,
                    $criterion['weight'],
                    strtoupper($criterion['type']),
                    "[{$criterion['benchmark']['min']} - {$criterion['benchmark']['max']}]",
                ];
            }
            $this->command->table(
                ['Kriteria', 'Kode', 'Bobot', 'Tipe', 'Benchmark'],
                $criteriaRows
            );

            // Tampilkan data mentah kriteria per siswa
            $this->command->newLine();
            $this->command->info('📋 Data Mentah Kriteria per Siswa:');

            $rawRows = [];
            foreach ($this->studentProfiles as $sp) {
                $user = User::where('email', $sp['email'])->first();
                if (!$user || !isset($profiles[$user->id])) continue;

                $waspasMeta = $profiles[$user->id]->recommendations['_waspas'] ?? null;
                $rawCriteria = $waspasMeta['raw_criteria'] ?? [];

                $rawRows[] = [
                    $sp['name'],
                    $rawCriteria['attendance'] ?? '-',
                    round($rawCriteria['performance'] ?? 0, 1),
                    $rawCriteria['engagement'] ?? '-',
                    round($rawCriteria['subject_mastery'] ?? 0, 1),
                ];
            }
            $this->command->table(
                ['Nama', 'Attendance (hari)', 'Performance (avg)', 'Engagement (mnt)', 'Mastery (%)'],
                $rawRows
            );

            // Tampilkan data normalisasi
            $this->command->newLine();
            $this->command->info('📐 Matriks Ternormalisasi:');

            $normRows = [];
            foreach ($this->studentProfiles as $sp) {
                $user = User::where('email', $sp['email'])->first();
                if (!$user || !isset($profiles[$user->id])) continue;

                $waspasMeta = $profiles[$user->id]->recommendations['_waspas'] ?? null;
                $normalized = $waspasMeta['normalized_criteria'] ?? [];

                $normRows[] = [
                    $sp['name'],
                    round($normalized['attendance'] ?? 0, 4),
                    round($normalized['performance'] ?? 0, 4),
                    round($normalized['engagement'] ?? 0, 4),
                    round($normalized['subject_mastery'] ?? 0, 4),
                ];
            }
            $this->command->table(
                ['Nama', 'Attendance (N)', 'Performance (N)', 'Engagement (N)', 'Mastery (N)'],
                $normRows
            );

            // Tampilkan hasil akhir WASPAS
            $this->command->newLine();
            $this->command->info('🏆 Hasil Akhir WASPAS (Perankingan):');

            $resultRows = collect($profiles)
                ->filter(fn ($p) => in_array($p->user_id, $dummyUserIds->toArray()))
                ->sortBy('waspas_rank')
                ->values()
                ->map(function ($profile) {
                    $actionRec = $profile->recommendations['_action_recommendation'] ?? null;
                    $recommended = $actionRec['recommended'] ?? null;

                    $levelEmoji = match ($profile->risk_level) {
                        'critical' => '🔴',
                        'high' => '🟠',
                        'medium' => '🟡',
                        'low' => '🟢',
                        default => '⚪',
                    };

                    return [
                        $profile->waspas_rank ?? '-',
                        $profile->user->name,
                        round($profile->wsm_score ?? 0, 4),
                        round($profile->wpm_score ?? 0, 4),
                        round(1 - ($profile->overall_risk_score / 100), 4),
                        round($profile->overall_risk_score, 2) . '%',
                        "{$levelEmoji} " . ucfirst($profile->risk_level),
                        $recommended['label'] ?? '-',
                    ];
                })
                ->toArray();

            $this->command->table(
                ['Rank', 'Nama', 'WSM', 'WPM', 'Q Score', 'Skor Risiko', 'Level', 'Rekomendasi Aksi'],
                $resultRows
            );

            // Statistik distribusi
            $this->command->newLine();
            $this->command->info('📈 Distribusi Risk Level:');
            $distribution = collect($profiles)
                ->filter(fn ($p) => in_array($p->user_id, $dummyUserIds->toArray()))
                ->groupBy('risk_level')
                ->map(fn ($group) => $group->count());

            foreach (['low' => '🟢', 'medium' => '🟡', 'high' => '🟠', 'critical' => '🔴'] as $level => $emoji) {
                $count = $distribution[$level] ?? 0;
                $bar = str_repeat('█', $count * 3);
                $this->command->info("   {$emoji} " . str_pad(ucfirst($level), 10) . ": {$count} siswa {$bar}");
            }

        } catch (\Throwable $e) {
            $this->command->error("⚠️  Error saat menghitung WASPAS: {$e->getMessage()}");
            $this->command->comment('   Anda bisa menghitung manual:');
            $this->command->comment('   php artisan tinker → app(StudentRiskProfileService::class)->calculateAllRiskProfiles()');
        }
    }

    // ──────────────────────────────────────────────────────────────
    //  UTILITY
    // ──────────────────────────────────────────────────────────────

    private function determineMasteryStatus(float $percentage): string
    {
        return match (true) {
            $percentage >= 80 => 'excellent',
            $percentage >= 60 => 'good',
            $percentage >= 40 => 'needs_help',
            default => 'poor',
        };
    }
}
