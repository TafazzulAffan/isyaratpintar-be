<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MataPelajaran extends Model
{
    use HasFactory;

    protected $table = 'mata_pelajarans';

    protected $fillable = [
        'name',
    ];

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class, 'mata_pelajaran_id')->orderBy('id');
    }

    public function pblCases(): HasMany
    {
        return $this->hasMany(PblCase::class, 'mata_pelajaran_id');
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class, 'mata_pelajaran_id');
    }
}
