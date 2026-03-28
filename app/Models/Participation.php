<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Participation extends Model
{
    protected $table = 'participation';

    protected $fillable = [
        'user_id', 'training_id', 'progress',
        'completed', 'completion_date'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function training()
    {
        return $this->belongsTo(Training::class);
    }

    protected function casts(): array
    {
        return [
            'completed' => 'boolean',
            'completion_date' => 'datetime',
        ];
    }
}