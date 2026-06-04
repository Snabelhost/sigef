<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class DocumentRecipient extends Model implements Auditable
{
    use HasFactory, AuditableTrait;

    protected $fillable = [
        'document_id',
        'institution_id',
        'user_id',
        'status',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    // ========================================
    // Constantes
    // ========================================

    const STATUS_PENDING = 'pending';
    const STATUS_READ = 'read';
    const STATUS_RESPONDED = 'responded';

    // ========================================
    // Relações
    // ========================================

    /**
     * Documento recebido
     */
    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Instituição destinatária
     */
    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * Utilizador destinatário
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Respostas deste destinatário
     */
    public function responses()
    {
        return $this->hasMany(DocumentResponse::class);
    }

    // ========================================
    // Scopes
    // ========================================

    /**
     * Apenas não lidos
     */
    public function scopeUnread($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Para um utilizador específico
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // ========================================
    // Helpers
    // ========================================

    /**
     * Marca como lido
     */
    public function markAsRead(): void
    {
        if ($this->status === self::STATUS_PENDING) {
            $this->update([
                'status' => self::STATUS_READ,
                'read_at' => now(),
            ]);
        }
    }

    /**
     * Marca como respondido
     */
    public function markAsResponded(): void
    {
        $this->update([
            'status' => self::STATUS_RESPONDED,
        ]);
    }

    /**
     * Verifica se foi lido
     */
    public function isRead(): bool
    {
        return $this->status !== self::STATUS_PENDING;
    }

    /**
     * Labels de status
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_PENDING => 'Pendente',
            self::STATUS_READ => 'Lido',
            self::STATUS_RESPONDED => 'Respondido',
        ];
    }
}
