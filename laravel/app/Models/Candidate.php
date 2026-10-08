<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Candidate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'email', 'phone', 'nationality',
        'applied_position', 'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function interviews()
    {
        return $this->hasMany(Interview::class);
    }
}
