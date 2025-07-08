<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'phone', 'address'];

    public function supplies(): HasMany
    {
        return $this->hasMany(Supply::class);
    }

    public function products()
    {
        return $this->hasManyThrough(
            Product::class,
            Supply::class,
            'supplier_id',
            'id',
            'id',
            'product_id'
        )->distinct();
    }
}