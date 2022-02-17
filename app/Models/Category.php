<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable =[
        'name',
    ];

    /* =====================
      RELACIONES
    ========================= */

    /**
     * Una categoria tiene muchos posts
     *
     * @return HasMany
     */
    public function posts():HasMany{
        return $this->hasMany(Category::class);
    }

}
