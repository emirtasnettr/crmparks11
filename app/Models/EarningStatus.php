<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EarningStatus extends Model
{
    protected $fillable = [
        'code',
        'label',
        'color',
        'sort_order',
    ];
}
