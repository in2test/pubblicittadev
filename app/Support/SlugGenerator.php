<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SlugGenerator
{
    /**
     * Generate a unique slug for a model class.
     *
     * @param  class-string<Model>  $modelClass
     */
    public static function unique(string $modelClass, ?string $title, ?Model $ignoreRecord = null): string
    {
        $baseSlug = Str::slug($title ?? '');
        $slug = $baseSlug;
        $count = 2;

        while (
            $modelClass::query()
                ->where('slug', $slug)
                ->when($ignoreRecord, fn ($query) => $query->whereKeyNot($ignoreRecord->getKey()))
                ->exists()
        ) {
            $slug = $baseSlug.'-'.$count++;
        }

        return $slug;
    }
}
