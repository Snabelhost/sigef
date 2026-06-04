<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentTransferHistory extends Model
{
    protected $fillable = [
        'student_id',
        'from_institution_id',
        'to_institution_id',
        'transferred_by',
        'agent_name',
        'student_number',
        'rank',
        'provenance',
        'phone',
        'status',
        'notes',
        'transferred_at',
    ];

    protected $casts = [
        'transferred_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
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
