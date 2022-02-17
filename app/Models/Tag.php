<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = ['tag'];

    /* =====================
      RELACIONES
    ========================= */

    /**
     * Un tag esta en muchos post
     *
     * @return BelongsToMany
     */
    public function posts(): BelongsToMany{
        return $this->belongsToMany(Post::class);
    }


}
