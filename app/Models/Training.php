<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Training extends Model
{
    protected $fillable = [
        'title',
        'description',
        'category',
        'duration',
        'status',
        'created_by',
        'training_date',
        'end_date',
    ];

    protected function casts(): array
    {
        return [
            'training_date' => 'datetime',
            'end_date' => 'date',
        ];
    }

    public function participants(): HasMany
    {
        return $this->hasMany(Participation::class);
    }

    public function materials(): HasMany
    {
        return $this->hasMany(Material::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
