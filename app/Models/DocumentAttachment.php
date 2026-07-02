<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class DocumentAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'file_path',
        'original_name',
        'mime_type',
        'size',
    ];

    // ========================================
    // Relações
    // ========================================

    /**
     * Documento ao qual pertence o anexo
     */
    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    // ========================================
    // Accessors
    // ========================================

    /**
     * URL pública do arquivo
     */
    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }

    /**
     * Tamanho formatado
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->size;
        
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    /**
     * Extensão do arquivo
     */
    public function getExtensionAttribute(): string
    {
        return pathinfo($this->original_name, PATHINFO_EXTENSION);
    }

    /**
     * Ícone baseado no tipo de arquivo
     */
    public function getIconAttribute(): string
    {
        $extension = strtolower($this->extension);
        
        $icons = [
            'pdf' => 'heroicon-o-document-text',
            'doc' => 'heroicon-o-document',
            'docx' => 'heroicon-o-document',
            'xls' => 'heroicon-o-table-cells',
            'xlsx' => 'heroicon-o-table-cells',
            'jpg' => 'heroicon-o-photo',
            'jpeg' => 'heroicon-o-photo',
            'png' => 'heroicon-o-photo',
            'gif' => 'heroicon-o-photo',
            'zip' => 'heroicon-o-archive-box',
            'rar' => 'heroicon-o-archive-box',
        ];
        
        return $icons[$extension] ?? 'heroicon-o-paper-clip';
    }
}
