<?php

namespace Moox\Tag\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TagTranslation extends Model
{
    public $timestamps = false;

    protected $fillable = ['title', 'slug', 'content'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'title' => 'string',
        'slug' => 'string',
        'content' => 'string',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function (self $translation) {
            // Generate slug from title if not provided
            if (empty($translation->slug) && ! empty($translation->title)) {
                $translation->slug = Str::slug($translation->title);
            }

            // Ensure slug uniqueness within the same locale
            if ($translation->isDirty('slug')) {
                $exists = static::where('locale', $translation->locale)
                    ->where('slug', $translation->slug)
                    ->where('id', '!=', $translation->id ?? 0)
                    ->exists();

                if ($exists) {
                    throw ValidationException::withMessages([
                        'slug' => ["The slug '{$translation->slug}' already exists for locale '{$translation->locale}'."],
                    ]);
                }
            }
        });
    }

    /**
     * Generate a unique slug for the translation
     */
    public static function generateUniqueSlug(string $title, string $locale, ?int $excludeId = null): string
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $counter = 1;

        while (static::where('locale', $locale)
            ->where('slug', $slug)
            ->when($excludeId, fn ($query) => $query->where('id', '!=', $excludeId))
            ->exists()) {
            $slug = $originalSlug.'-'.$counter++;
        }

        return $slug;
    }
}
