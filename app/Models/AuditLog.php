<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id', 'actor', 'action', 'subject_type', 'subject_id', 'details',
    ];
}
