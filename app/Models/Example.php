<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Example extends Model
{
    use HasFactory;
    protected $fillable = [
        'image',
        'title',
        'content',
    ];

    /**
     * image
     *
     * @return Attribute
     */
    protected function image(): Attribute
    {
        return Attribute::make(
            get: fn ($image) => url('/storage/examples/' . $image),
        );
    }
}
