<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SelectionTest extends Model
{
    use HasFactory;

    protected $fillable = [
        'institution_id',
        'name',
        'type',
        'order',
    ];

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }
}
