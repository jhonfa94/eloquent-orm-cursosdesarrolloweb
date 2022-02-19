<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Post extends Model
{

    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'slug',
        'content',
        'likes',
        'dislikes',
    ];

    protected $appends = [
        "title_with_author"
    ];

    protected $casts = [
        "created_at" => "datetime:Y-m-d"
    ];

    // protected $with = ["user:id,name,email",]; # SE CARGA DE FORMA AUTOMATICA




    public function setTitleAttribute(string $title)
    {
        $this->attributes['title'] = $title;
        $this->attributes['slug'] = Str::slug($title);
    }

    /**
     * Atributto personalizado
     *
     * @return string
     */
    public function getTitleWithAuthorAttribute(): string
    {
        return sprintf("%s - %s", $this->title, $this->user->name);
    }



    /* =====================
      REALACIONES
    ========================= */

    /**
     * Un post pertenece a un usuario
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Un Post pertenece a una categoria
     *
     * @return BelongsTo
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Un Uost tiene muchas etiquetas
     *
     * @return BelongsToMany
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /**
     * Tags ordenados
     *
     * @return BelongsToMany
     */
    public function sortedTags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->orderBy('tag', 'asc');
    }

    /**
     * SCOPE para los tags
     *
     * @param Builder $builder
     * @return Builder
     */
    public function scopeWhereHasTagsWithTags(Builder $builder): Builder
    {
        //scopeWhereHasTagsWithTags
        return $builder
            ->select(['id', 'title'])
            ->with("tags:id,tag")
            ->whereHas("tags");
        // ->whereDoesntHave("tags")
        // ->get();
    }
}
