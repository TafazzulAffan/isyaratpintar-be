<?php

namespace Database\Seeders;

use App\Models\Lesson;
use App\Models\MataPelajaran;
use Illuminate\Database\Seeder;

class LessonSeeder extends Seeder
{
    /**
     * Seed the application's lesson data.
     */
    public function run(): void
    {
        // Create or get the Pengenalan PBO mata pelajaran
        $mataPelajaran = MataPelajaran::query()->firstOrCreate([
            'name' => 'Pengenalan PBO',
        ]);

        Lesson::query()->updateOrCreate(
            ['title' => 'Pengenalan PBO'],
            [
                'mata_pelajaran_id' => $mataPelajaran->id,
                'title' => 'Pengenalan PBO',
                'description' => 'Pada bab ini, peserta didik akan mempelajari konsep dasar dalam Pemrograman Berorientasi Objek (PBO), yaitu class dan object. Materi ini menjadi fondasi utama dalam memahami bagaimana suatu program dirangkai menggunakan object-oriented paradigm. Peserta didik akan mempelajari pengetahuan class sebagai cetak biru (blueprint) dari object sebagai instansiasi dari class. Selain itu, dibahas juga bagaimana mendefinisikan atribut (properties) dan method (behavior), serta cara membuat dan menggunakan object di dalam program.',
                'duration' => null,
                'pdf_url' => 'materi/Pengenalan_PBO.pdf',
            ]
        );
    }
}
