<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    protected $fillable = [
        'training_id', 'uploaded_by', 'file_name',
        'file_path', 'file_type', 'file_size'
    ];

    public function training()
    {
        return $this->belongsTo(Training::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}