<?php

return [

    /*
    |--------------------------------------------------------------------------
    | WASPAS Configuration
    |--------------------------------------------------------------------------
    |
    | Weighted Aggregated Sum Product Assessment (WASPAS) parameters for
    | student risk profiling. Higher WASPAS Q score = better performance.
    |
    */

    'waspas' => [
        // λ parameter: Q = λ·WSM + (1-λ)·WPM
        'lambda' => (float) env('SPK_WASPAS_LAMBDA', 0.5),

        'criteria' => [
            'attendance' => [
                'label' => 'Kehadiran/Aktivitas',
                'weight' => 0.20,
                'type' => 'benefit',
                'benchmark' => ['min' => 0, 'max' => 7],
            ],
            'performance' => [
                'label' => 'Performa Ujian',
                'weight' => 0.30,
                'type' => 'benefit',
                'benchmark' => ['min' => 0, 'max' => 100],
            ],
            'engagement' => [
                'label' => 'Keterlibatan Belajar',
                'weight' => 0.20,
                'type' => 'benefit',
                'benchmark' => ['min' => 0, 'max' => 1800], // minutes in 30 days
            ],
            'subject_mastery' => [
                'label' => 'Penguasaan Mata Pelajaran',
                'weight' => 0.30,
                'type' => 'benefit',
                'benchmark' => ['min' => 0, 'max' => 100],
            ],
        ],
    ],

    'risk_levels' => [
        'critical' => 75,
        'high' => 60,
        'medium' => 40,
        'low' => 0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Pedagogical Actions (Tindakan Pedagogis)
    |--------------------------------------------------------------------------
    |
    | Defines the alternatives for the WASPAS-based SPK. Each action has its
    | own weight profile and criteria types, reflecting the student profile
    | for which the action is most appropriate.
    |
    | For each action, WASPAS is run with the student's raw criteria as the
    | single alternative, using the action's specific weights and types.
    | The action with the highest Q score is the recommended action.
    |
    */

    'pedagogical_actions' => [

        'penguatan_materi' => [
            'label' => 'Penguatan Materi',
            'description' => 'Berikan materi tambahan dan penjelasan ulang untuk memperkuat pemahaman siswa.',
            'icon' => 'book-open',
            'color' => 'blue',
            // Cocok untuk: engagement TINGGI tapi mastery RENDAH
            // → siswa aktif tapi belum paham, butuh materi yang lebih jelas
            'weights' => [
                'attendance'      => 0.10,
                'performance'     => 0.15,
                'engagement'      => 0.40, // bobot tinggi: engagement harus tinggi
                'subject_mastery' => 0.35, // bobot tinggi: mastery harus rendah (cost)
            ],
            'criteria_types' => [
                'attendance'      => 'benefit',
                'performance'     => 'cost',    // rendah → lebih cocok
                'engagement'      => 'benefit',  // tinggi → lebih cocok
                'subject_mastery' => 'cost',     // rendah → lebih cocok
            ],
        ],

        'remedial_latihan' => [
            'label' => 'Remedial & Latihan Ulang',
            'description' => 'Berikan ujian/latihan remedial agar siswa dapat memperbaiki nilai dan penguasaan.',
            'icon' => 'refresh-cw',
            'color' => 'orange',
            // Cocok untuk: performance RENDAH, mastery RENDAH, tapi attendance cukup
            // → siswa hadir tapi hasil ujiannya jelek, butuh latihan ulang
            'weights' => [
                'attendance'      => 0.15,
                'performance'     => 0.35, // bobot tinggi: performance harus rendah (cost)
                'engagement'      => 0.15,
                'subject_mastery' => 0.35, // bobot tinggi: mastery harus rendah (cost)
            ],
            'criteria_types' => [
                'attendance'      => 'benefit',  // hadir → butuh remedial (bukan absent)
                'performance'     => 'cost',     // nilai rendah → butuh remedial
                'engagement'      => 'benefit',  // aktif → bisa ikut remedial
                'subject_mastery' => 'cost',     // mastery rendah → butuh remedial
            ],
        ],

        'bimbingan_individual' => [
            'label' => 'Bimbingan Individual',
            'description' => 'Lakukan pendekatan personal dan bimbingan satu-satu untuk membantu siswa.',
            'icon' => 'user-check',
            'color' => 'red',
            // Cocok untuk: SEMUA aspek RENDAH
            // → siswa butuh perhatian khusus secara personal
            'weights' => [
                'attendance'      => 0.25,
                'performance'     => 0.25,
                'engagement'      => 0.25,
                'subject_mastery' => 0.25,
            ],
            'criteria_types' => [
                'attendance'      => 'cost', // rendah → butuh bimbingan
                'performance'     => 'cost', // rendah → butuh bimbingan
                'engagement'      => 'cost', // rendah → butuh bimbingan
                'subject_mastery' => 'cost', // rendah → butuh bimbingan
            ],
        ],

        'variasi_metode' => [
            'label' => 'Variasi Metode Penyampaian',
            'description' => 'Ubah atau variasikan metode penyampaian materi karena siswa mungkin tidak cocok dengan metode saat ini.',
            'icon' => 'layers',
            'color' => 'purple',
            // Cocok untuk: attendance CUKUP, performance SEDANG, tapi engagement RENDAH
            // → siswa hadir tapi tidak tertarik, mungkin bosan dengan metode saat ini
            'weights' => [
                'attendance'      => 0.20,
                'performance'     => 0.15,
                'engagement'      => 0.40, // bobot tinggi: engagement harus rendah (cost)
                'subject_mastery' => 0.25,
            ],
            'criteria_types' => [
                'attendance'      => 'benefit',  // hadir tapi tidak engage
                'performance'     => 'benefit',  // nilai lumayan
                'engagement'      => 'cost',     // engagement rendah → metode tidak cocok
                'subject_mastery' => 'benefit',  // mastery lumayan
            ],
        ],

        'pengayaan_tantangan' => [
            'label' => 'Pengayaan & Tantangan Baru',
            'description' => 'Berikan materi pengayaan dan tantangan tingkat lanjut agar siswa tidak stagnan.',
            'icon' => 'award',
            'color' => 'emerald',
            // Cocok untuk: SEMUA aspek TINGGI
            // → siswa sudah unggul, butuh tantangan lebih
            'weights' => [
                'attendance'      => 0.20,
                'performance'     => 0.30,
                'engagement'      => 0.20,
                'subject_mastery' => 0.30,
            ],
            'criteria_types' => [
                'attendance'      => 'benefit', // tinggi → sudah mandiri
                'performance'     => 'benefit', // tinggi → unggul
                'engagement'      => 'benefit', // tinggi → aktif
                'subject_mastery' => 'benefit', // tinggi → menguasai
            ],
        ],

        'monitoring_lanjutan' => [
            'label' => 'Monitoring Lanjutan',
            'description' => 'Siswa dalam kondisi stabil. Pantau perkembangan tanpa intervensi khusus.',
            'icon' => 'eye',
            'color' => 'slate',
            // Cocok untuk: aspek SEDANG/STABIL, tidak ada yang ekstrem
            // → menggunakan bobot merata dengan benefit, tapi tidak terlalu tinggi atau rendah
            'weights' => [
                'attendance'      => 0.25,
                'performance'     => 0.25,
                'engagement'      => 0.25,
                'subject_mastery' => 0.25,
            ],
            'criteria_types' => [
                'attendance'      => 'benefit',
                'performance'     => 'benefit',
                'engagement'      => 'benefit',
                'subject_mastery' => 'benefit',
            ],
        ],

    ],

];
