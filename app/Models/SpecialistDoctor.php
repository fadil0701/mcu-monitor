<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpecialistDoctor extends Model
{
    protected $fillable = [
        'name',
        'specialty',
        'description',
        'is_active',
    ];
}
