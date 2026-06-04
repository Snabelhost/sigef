<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'module',
        'description',
        'model_type',
        'model_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'device_type',
        'browser',
        'platform',
        'url',
        'method',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    /**
     * Relacionamento com o utilizador
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Obter o modelo relacionado (polimórfico)
     */
    public function subject()
    {
        if ($this->model_type && $this->model_id) {
            return $this->model_type::find($this->model_id);
        }
        return null;
    }

    /**
     * Registrar uma atividade
     */
    public static function log(
        string $action,
        string $module,
        ?string $description = null,
        ?Model $model = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): self {
        $request = request();
        $userAgent = $request->userAgent() ?? '';
        
        // Detectar tipo de dispositivo
        $deviceType = self::detectDeviceType($userAgent);
        $browser = self::detectBrowser($userAgent);
        $platform = self::detectPlatform($userAgent);
        
        return self::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'module' => $module,
            'description' => $description,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model?->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr($userAgent, 0, 500),
            'device_type' => $deviceType,
            'browser' => $browser,
            'platform' => $platform,
            'url' => mb_substr($request->fullUrl(), 0, 500),
            'method' => $request->method(),
        ]);
    }

    /**
     * Detectar tipo de dispositivo
     */
    public static function detectDeviceType(string $userAgent): string
    {
        $userAgent = strtolower($userAgent);
        
        if (preg_match('/(tablet|ipad|playbook|silk)/', $userAgent)) {
            return 'tablet';
        }
        
        if (preg_match('/(mobile|android|iphone|ipod|blackberry|opera mini|iemobile)/', $userAgent)) {
            return 'mobile';
        }
        
        return 'desktop';
    }

    /**
     * Detectar browser
     */
    public static function detectBrowser(string $userAgent): string
    {
        if (preg_match('/edge/i', $userAgent)) return 'Edge';
        if (preg_match('/edg/i', $userAgent)) return 'Edge';
        if (preg_match('/opr|opera/i', $userAgent)) return 'Opera';
        if (preg_match('/chrome|chromium|crios/i', $userAgent)) return 'Chrome';
        if (preg_match('/firefox|fxios/i', $userAgent)) return 'Firefox';
        if (preg_match('/safari/i', $userAgent)) return 'Safari';
        if (preg_match('/msie|trident/i', $userAgent)) return 'Internet Explorer';
        
        return 'Desconhecido';
    }

    /**
     * Detectar plataforma/SO
     */
    public static function detectPlatform(string $userAgent): string
    {
        if (preg_match('/windows nt 10/i', $userAgent)) return 'Windows 10/11';
        if (preg_match('/windows nt 6\.[23]/i', $userAgent)) return 'Windows 8';
        if (preg_match('/windows nt 6\.1/i', $userAgent)) return 'Windows 7';
        if (preg_match('/windows/i', $userAgent)) return 'Windows';
        if (preg_match('/android/i', $userAgent)) return 'Android';
        if (preg_match('/iphone|ipad|ipod/i', $userAgent)) return 'iOS';
        if (preg_match('/mac os x/i', $userAgent)) return 'macOS';
        if (preg_match('/linux/i', $userAgent)) return 'Linux';
        
        return 'Desconhecido';
    }

    /**
     * Cor do badge baseada na ação
     */
    public function getActionColor(): string
    {
        return match($this->action) {
            'login' => 'success',
            'logout' => 'gray',
            'create' => 'primary',
            'update' => 'warning',
            'delete' => 'danger',
            'view' => 'info',
            default => 'gray',
        };
    }

    /**
     * Ícone da ação
     */
    public function getActionIcon(): string
    {
        return match($this->action) {
            'login' => 'heroicon-o-arrow-right-on-rectangle',
            'logout' => 'heroicon-o-arrow-left-on-rectangle',
            'create' => 'heroicon-o-plus-circle',
            'update' => 'heroicon-o-pencil-square',
            'delete' => 'heroicon-o-trash',
            'view' => 'heroicon-o-eye',
            default => 'heroicon-o-document',
        };
    }

    /**
     * Ícone do dispositivo
     */
    public function getDeviceIcon(): string
    {
        return match($this->device_type) {
            'mobile' => 'heroicon-o-device-phone-mobile',
            'tablet' => 'heroicon-o-device-tablet',
            default => 'heroicon-o-computer-desktop',
        };
    }
}
