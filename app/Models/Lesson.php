<?php

namespace App\Models;

use App\Models\MataPelajaran;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Lesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'mata_pelajaran_id',
        'slug',
        'title',
        'description',
        'duration',
        'file_url',
        'resume',
    ];

    protected $appends = ['completed'];

    /**
     * Store virtual attributes that should not be saved to database
     */
    protected $virtualAttributes = [];

    protected static function booted(): void
    {
        static::creating(function (Lesson $lesson): void {
            $lesson->slug = self::generateSlug();
        });
    }

    private static function generateSlug(): string
    {
        do {
            $slug = Str::lower(Str::random(12));
        } while (self::query()->where('slug', $slug)->exists());

        return $slug;
    }

    public function mataPelajaran(): BelongsTo
    {
        return $this->belongsTo(MataPelajaran::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_lessons')
            ->withPivot(['completed', 'completed_at'])
            ->withTimestamps();
    }

    /**
     * Get the lesson's completed status.
     * This is a virtual attribute that doesn't persist to the database.
     */
    public function getCompletedAttribute(): bool
    {
        return $this->virtualAttributes['completed'] ?? false;
    }

    /**
     * Override setAttribute to prevent completed from being saved to database
     */
    public function setAttribute($key, $value)
    {
        // Store completed in virtual attributes instead of model attributes
        if ($key === 'completed') {
            $this->virtualAttributes['completed'] = $value;
            return $this;
        }

        return parent::setAttribute($key, $value);
    }
}
