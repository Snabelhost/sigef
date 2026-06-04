<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateTransferHistory extends Model
{
    protected $fillable = [
        'candidate_id',
        'from_institution_id',
        'to_institution_id',
        'transferred_by',
        'candidate_name',
        'bi_number',
        'student_type',
        'phone',
        'province',
        'status',
        'notes',
        'transferred_at',
    ];

    protected $casts = [
        'transferred_at' => 'datetime',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function fromInstitution(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'from_institution_id');
    }

    public function toInstitution(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'to_institution_id');
    }

    public function transferredByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'transferred_by');
    }
}
