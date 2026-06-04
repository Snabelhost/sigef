<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class Document extends Model implements Auditable
{
    use HasFactory, AuditableTrait, SoftDeletes;

    protected $fillable = [
        'sender_institution_id',
        'sender_user_id',
        'title',
        'reference_number',
        'content',
        'priority',
        'status',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    // ========================================
    // Constantes
    // ========================================

    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_URGENT = 'urgent';
    const PRIORITY_CONFIDENTIAL = 'confidential';

    const STATUS_DRAFT = 'draft';
    const STATUS_SENT = 'sent';
    const STATUS_ARCHIVED = 'archived';

    // ========================================
    // Relações
    // ========================================

    /**
     * Instituição que enviou o documento
     */
    public function senderInstitution()
    {
        return $this->belongsTo(Institution::class, 'sender_institution_id');
    }

    /**
     * Relationship for Filament multi-tenancy support
     * Uses sender_institution_id as the tenant relationship
     */
    public function institution()
    {
        return $this->belongsTo(Institution::class, 'sender_institution_id');
    }

    /**
     * Utilizador que enviou o documento
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }

    /**
     * Destinatários do documento
     */
    public function recipients()
    {
        return $this->hasMany(DocumentRecipient::class);
    }

    /**
     * Anexos do documento
     */
    public function attachments()
    {
        return $this->hasMany(DocumentAttachment::class);
    }

    /**
     * Respostas ao documento (mais recentes primeiro)
     */
    public function responses()
    {
        return $this->hasMany(DocumentResponse::class)->latest();
    }

    // ========================================
    // Scopes
    // ========================================

    /**
     * Apenas documentos enviados
     */
    public function scopeSent($query)
    {
        return $query->where('status', self::STATUS_SENT);
    }

    /**
     * Apenas rascunhos
     */
    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    /**
     * Documentos de uma instituição específica (enviados)
     */
    public function scopeFromInstitution($query, $institutionId)
    {
        return $query->where('sender_institution_id', $institutionId);
    }

    /**
     * Documentos recebidos por uma instituição
     */
    public function scopeReceivedByInstitution($query, $institutionId)
    {
        return $query->whereHas('recipients', function ($q) use ($institutionId) {
            $q->where('institution_id', $institutionId);
        });
    }

    /**
     * Documentos recebidos por um utilizador
     */
    public function scopeReceivedByUser($query, $userId)
    {
        return $query->whereHas('recipients', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        });
    }

    // ========================================
    // Helpers
    // ========================================

    /**
     * Verifica se o documento foi enviado
     */
    public function isSent(): bool
    {
        return $this->status === self::STATUS_SENT;
    }

    /**
     * Verifica se é rascunho
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Envia o documento
     */
    public function send(): void
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
        ]);
    }

    /**
     * Labels de prioridade para o UI
     */
    public static function getPriorityOptions(): array
    {
        return [
            self::PRIORITY_NORMAL => 'Normal',
            self::PRIORITY_URGENT => 'Urgente',
            self::PRIORITY_CONFIDENTIAL => 'Confidencial',
        ];
    }

    /**
     * Labels de status para o UI
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_DRAFT => 'Rascunho',
            self::STATUS_SENT => 'Enviado',
            self::STATUS_ARCHIVED => 'Arquivado',
        ];
    }
}
