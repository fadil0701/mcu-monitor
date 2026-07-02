<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class McuWorkCalendarClosure extends Model
{
    protected $fillable = [
        'closure_date',
        'type',
        'label',
    ];

    protected function casts(): array
    {
        return [
            'closure_date' => 'date',
        ];
    }
}
