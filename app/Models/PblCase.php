<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PblCase extends Model
{
    use HasFactory;

    protected $table = 'pbl_cases';

    protected $fillable = [
        'slug',
        'case_number',
        'title',
        'mata_pelajaran_id',
        'kelas_id',
        'description',
        'image_url',
        'time_limit',
        'start_date',
        'deadline',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'deadline' => 'datetime',
        'time_limit' => 'integer',
    ];

    /**
     * Get the MataPelajaran that owns the case
     */
    public function mataPelajaran(): BelongsTo
    {
        return $this->belongsTo(MataPelajaran::class, 'mata_pelajaran_id');
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    /**
     * Get all sections for this case
     */
    public function sections(): HasMany
    {
        return $this->hasMany(CaseSection::class, 'case_id')->orderBy('order');
    }

    /**
     * Get all user progress for this case
     */
    public function userProgress(): HasMany
    {
        return $this->hasMany(UserCaseProgress::class, 'case_id');
    }

    /**
     * Get all submissions for this case
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(CaseSubmission::class, 'case_id');
    }
}
