<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $fillable = [
        'subject',
        'original_url',
        'file_path',
        'filename',
        'from_email',
        'received_at',
    ];

    protected $casts = [
        'received_at' => 'datetime',
    ];
}
