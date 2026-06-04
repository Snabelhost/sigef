<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class DocumentResponse extends Model implements Auditable
{
    use HasFactory, AuditableTrait;

    protected $fillable = [
        'document_id',
        'document_recipient_id',
        'user_id',
        'content',
        'attachments',
    ];

    protected $casts = [
        'attachments' => 'array',
    ];

    // ========================================
    // Relações
    // ========================================

    /**
     * Documento original
     */
    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Registro do destinatário
     */
    public function recipient()
    {
        return $this->belongsTo(DocumentRecipient::class, 'document_recipient_id');
    }

    /**
     * Utilizador que respondeu
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ========================================
    // Eventos
    // ========================================

    protected static function booted()
    {
        // Quando uma resposta é criada, atualiza o status do recipient
        static::created(function ($response) {
            $response->recipient->markAsResponded();
        });
    }
}
