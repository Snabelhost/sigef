<?php

namespace App\Models;

use App\Support\PublicStorage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CardTemplate extends Model
{
    public const TYPE_STUDENT = 'student';
    public const TYPE_PROFESSOR = 'professor';
    public const TYPE_STAFF = 'staff';

    public const STYLE_STUDENT = 'student';
    public const STYLE_PROFESSOR_VERTICAL = 'professor_vertical';
    public const STYLE_STAFF_EFFECTIVE = 'staff_effective';
    public const STYLE_MODERN = 'modern';
    public const STYLE_CLASSIC = 'classic';
    public const STYLE_MINIMALIST = 'minimalist';

    public const ORIENTATION_HORIZONTAL = 'horizontal';
    public const ORIENTATION_VERTICAL = 'vertical';

    protected $fillable = [
        'institution_id',
        'source_template_id',
        'name',
        'card_type',
        'card_variant',
        'is_default',
        'is_active',
        'primary_color',
        'secondary_color',
        'text_color',
        'front_text_color',
        'header_text_color',
        'back_text_color',
        'front_background_color',
        'back_background_color',
        'logo_path',
        'front_background_path',
        'back_background_path',
        'signature_image_path',
        'sample_photo_path',
        'fallback_photo_path',
        'sample_payload',
        'brand_name',
        'front_title',
        'number_label',
        'subtitle',
        'website',
        'contact_email',
        'contact_phone',
        'contact_whatsapp',
        'address_line',
        'back_title',
        'signature_label',
        'signatory_name',
        'signatory_title',
        'footer_text',
        'show_qr_code',
        'show_barcode',
        'style',
        'orientation',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'show_qr_code' => 'boolean',
        'show_barcode' => 'boolean',
        'sample_payload' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $template): void {
            if ($template->card_type !== self::TYPE_STAFF) {
                $template->card_variant = null;
            }

            if ($template->is_default) {
                $template->is_active = true;
            }

            $template->show_barcode = false;
        });

        static::saved(function (self $template): void {
            if (! $template->is_default) {
                return;
            }

            static::query()
                ->where('card_type', $template->card_type)
                ->where('institution_id', $template->institution_id)
                ->when($template->card_type === self::TYPE_STAFF && filled($template->card_variant), fn (Builder $query): Builder => $query->where('card_variant', $template->card_variant))
                ->whereKeyNot($template->id)
                ->update(['is_default' => false]);
        });
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function sourceTemplate(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_template_id');
    }

    public function schoolCopies(): HasMany
    {
        return $this->hasMany(self::class, 'source_template_id');
    }

    public static function cardTypeOptions(): array
    {
        return [
            self::TYPE_STUDENT => 'Formando',
            self::TYPE_PROFESSOR => 'Formador',
            self::TYPE_STAFF => 'Efectivo',
        ];
    }

    public static function styleOptions(): array
    {
        return [
            self::STYLE_STUDENT => 'Formando',
            self::STYLE_PROFESSOR_VERTICAL => 'Passe vertical',
            self::STYLE_STAFF_EFFECTIVE => 'Passe horizontal',
            self::STYLE_MODERN => 'Moderno',
            self::STYLE_CLASSIC => 'Classico',
            self::STYLE_MINIMALIST => 'Minimalista',
        ];
    }

    public static function orientationOptions(): array
    {
        return [
            self::ORIENTATION_HORIZONTAL => 'Horizontal',
            self::ORIENTATION_VERTICAL => 'Vertical',
        ];
    }

    public static function staffVariantOptions(): array
    {
        return [
            'without_department' => 'Sem departamento',
            'with_department' => 'Com departamento',
            'civil' => 'Civil',
        ];
    }

    public static function resolveForType(?string $cardType, ?string $cardVariant = null, ?int $institutionId = null): ?self
    {
        $cardType = trim((string) $cardType);

        if ($cardType === '') {
            return null;
        }

        $query = static::query()
            ->where('card_type', $cardType)
            ->where('is_active', true)
            ->when(
                filled($institutionId),
                fn (Builder $query): Builder => $query->where(function (Builder $query) use ($institutionId): void {
                    $query->where('institution_id', $institutionId)
                        ->orWhereNull('institution_id');
                }),
                fn (Builder $query): Builder => $query->whereNull('institution_id'),
            );

        $orderByInstitution = function (Builder $query) use ($institutionId): Builder {
            if (filled($institutionId)) {
                return $query->orderByRaw('CASE WHEN institution_id = ? THEN 0 ELSE 1 END', [$institutionId]);
            }

            return $query->orderByDesc('is_default');
        };

        $cardVariant = trim((string) $cardVariant);

        if ($cardVariant !== '') {
            $variantTemplate = (clone $query)
                ->where('card_variant', $cardVariant)
                ->tap($orderByInstitution)
                ->orderByDesc('is_default')
                ->orderByDesc('updated_at')
                ->first();

            if ($variantTemplate instanceof self) {
                return $variantTemplate;
            }
        }

        return $query
            ->tap($orderByInstitution)
            ->orderByDesc('is_default')
            ->orderByDesc('updated_at')
            ->first();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getCardTypeLabelAttribute(): string
    {
        return static::cardTypeOptions()[$this->card_type] ?? (string) $this->card_type;
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->publicUrl($this->logo_path);
    }

    public function getFrontBackgroundUrlAttribute(): ?string
    {
        return $this->publicUrl($this->front_background_path);
    }

    public function getBackBackgroundUrlAttribute(): ?string
    {
        return $this->publicUrl($this->back_background_path);
    }

    public function getSignatureImageUrlAttribute(): ?string
    {
        return $this->publicUrl($this->signature_image_path);
    }

    public function getSamplePhotoUrlAttribute(): ?string
    {
        return $this->publicUrl($this->sample_photo_path);
    }

    public function getFallbackPhotoUrlAttribute(): ?string
    {
        return $this->publicUrl($this->fallback_photo_path);
    }

    protected function publicUrl(?string $path): ?string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (str_starts_with($path, 'images/')) {
            return asset($path);
        }

        return PublicStorage::url($path, requireExisting: true);
    }
}
