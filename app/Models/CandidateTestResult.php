<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CandidateTestResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'candidate_id',
        'selection_test_id',
        'result',
        'score',
        'observations',
        'evaluated_by',
        'evaluated_at',
    ];

    protected $casts = [
        'evaluated_at' => 'datetime',
    ];

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function test()
    {
        return $this->belongsTo(SelectionTest::class, 'selection_test_id');
    }

    public function evaluator()
    {
        return $this->belongsTo(User::class, 'evaluated_by');
    }
}
