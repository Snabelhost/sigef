<?php
/**
 * ExtensionsInstall - Verificação de Extensões e Requisitos
 * SIGEF Builder v1.0
 * 
 * Baseado no ecossistema adianti/plugins
 */

namespace Adianti\Plugins\Install;

class ExtensionsInstall
{
    private $requirements = [];
    private $checks = [];
    
    /**
     * Extensões PHP obrigatórias
     */
    const REQUIRED_EXTENSIONS = [
        'pdo' => 'PDO (Base de Dados)',
        'pdo_mysql' => 'PDO MySQL',
        'mbstring' => 'Multibyte String',
        'json' => 'JSON',
        'openssl' => 'OpenSSL',
        'curl' => 'cURL',
        'gd' => 'GD (Imagens)',
        'zip' => 'ZIP',
        'fileinfo' => 'FileInfo',
    ];
    
    /**
     * Extensões opcionais
     */
    const OPTIONAL_EXTENSIONS = [
        'pdo_sqlite' => 'PDO SQLite',
        'pdo_pgsql' => 'PDO PostgreSQL',
        'intl' => 'Internacionalização',
        'soap' => 'SOAP',
        'ldap' => 'LDAP',
        'imap' => 'IMAP',
    ];
    
    /**
     * Diretórios que precisam de permissão de escrita
     */
    const WRITABLE_DIRS = [
        'tmp',
        'tmp/session',
        'app/output',
        'app/images',
        'files',
    ];
    
    /**
     * Verificar versão do PHP
     */
    public function checkPHPVersion(string $minVersion = '8.2.0'): array
    {
        $current = PHP_VERSION;
        $valid = version_compare($current, $minVersion, '>=');
        
        $this->checks['php_version'] = [
            'name' => 'Versão PHP',
            'required' => ">= {$minVersion}",
            'current' => $current,
            'status' => $valid,
            'type' => 'required'
        ];
        
        return $this->checks['php_version'];
    }
    
    /**
     * Verificar extensões obrigatórias
     */
    public function checkRequiredExtensions(): array
    {
        $results = [];
        
        foreach (self::REQUIRED_EXTENSIONS as $ext => $name) {
            $loaded = extension_loaded($ext);
            $results[$ext] = [
                'name' => $name,
                'extension' => $ext,
                'status' => $loaded,
                'type' => 'required'
            ];
            $this->checks['ext_' . $ext] = $results[$ext];
        }
        
        return $results;
    }
    
    /**
     * Verificar extensões opcionais
     */
    public function checkOptionalExtensions(): array
    {
        $results = [];
        
        foreach (self::OPTIONAL_EXTENSIONS as $ext => $name) {
            $loaded = extension_loaded($ext);
            $results[$ext] = [
                'name' => $name,
                'extension' => $ext,
                'status' => $loaded,
                'type' => 'optional'
            ];
            $this->checks['ext_' . $ext] = $results[$ext];
        }
        
        return $results;
    }
    
    /**
     * Verificar permissões de diretórios
     */
    public function checkDirectoryPermissions(string $basePath): array
    {
        $results = [];
        
        foreach (self::WRITABLE_DIRS as $dir) {
            $fullPath = rtrim($basePath, '/\\') . DIRECTORY_SEPARATOR . $dir;
            $exists = is_dir($fullPath);
            $writable = is_writable($fullPath);
            
            $results[$dir] = [
                'path' => $dir,
                'full_path' => $fullPath,
                'exists' => $exists,
                'writable' => $writable,
                'status' => $exists && $writable,
                'type' => 'required'
            ];
            $this->checks['dir_' . str_replace('/', '_', $dir)] = $results[$dir];
        }
        
        return $results;
    }
    
    /**
     * Criar diretórios em falta
     */
    public function createMissingDirectories(string $basePath): array
    {
        $results = [];
        
        foreach (self::WRITABLE_DIRS as $dir) {
            $fullPath = rtrim($basePath, '/\\') . DIRECTORY_SEPARATOR . $dir;
            
            if (!is_dir($fullPath)) {
                $created = @mkdir($fullPath, 0755, true);
                $results[$dir] = [
                    'path' => $dir,
                    'action' => 'created',
                    'success' => $created
                ];
            } else {
                $results[$dir] = [
                    'path' => $dir,
                    'action' => 'exists',
                    'success' => true
                ];
            }
        }
        
        return $results;
    }
    
    /**
     * Verificar configurações do PHP
     */
    public function checkPHPSettings(): array
    {
        $settings = [
            'memory_limit' => [
                'name' => 'Memory Limit',
                'recommended' => '256M',
                'current' => ini_get('memory_limit'),
                'check' => fn($v) => $this->parseSize($v) >= $this->parseSize('256M')
            ],
            'max_execution_time' => [
                'name' => 'Max Execution Time',
                'recommended' => '120',
                'current' => ini_get('max_execution_time'),
                'check' => fn($v) => (int)$v >= 120 || $v == 0
            ],
            'upload_max_filesize' => [
                'name' => 'Upload Max Filesize',
                'recommended' => '20M',
                'current' => ini_get('upload_max_filesize'),
                'check' => fn($v) => $this->parseSize($v) >= $this->parseSize('20M')
            ],
            'post_max_size' => [
                'name' => 'Post Max Size',
                'recommended' => '25M',
                'current' => ini_get('post_max_size'),
                'check' => fn($v) => $this->parseSize($v) >= $this->parseSize('25M')
            ],
            'display_errors' => [
                'name' => 'Display Errors',
                'recommended' => 'Off (produção)',
                'current' => ini_get('display_errors') ? 'On' : 'Off',
                'check' => fn($v) => true // Apenas informativo
            ],
        ];
        
        $results = [];
        foreach ($settings as $key => $setting) {
            $status = $setting['check']($setting['current']);
            $results[$key] = [
                'name' => $setting['name'],
                'recommended' => $setting['recommended'],
                'current' => $setting['current'],
                'status' => $status,
                'type' => 'recommended'
            ];
            $this->checks['php_' . $key] = $results[$key];
        }
        
        return $results;
    }
    
    /**
     * Verificar funções desabilitadas
     */
    public function checkDisabledFunctions(): array
    {
        $required = ['exec', 'shell_exec', 'file_get_contents', 'file_put_contents'];
        $disabled = explode(',', ini_get('disable_functions'));
        $disabled = array_map('trim', $disabled);
        
        $results = [];
        foreach ($required as $func) {
            $isDisabled = in_array($func, $disabled);
            $results[$func] = [
                'function' => $func,
                'status' => !$isDisabled,
                'message' => $isDisabled ? 'Desabilitada' : 'Disponível'
            ];
        }
        
        return $results;
    }
    
    /**
     * Executar todas as verificações
     */
    public function runAllChecks(string $basePath): array
    {
        $this->checks = [];
        
        return [
            'php_version' => $this->checkPHPVersion(),
            'required_extensions' => $this->checkRequiredExtensions(),
            'optional_extensions' => $this->checkOptionalExtensions(),
            'directories' => $this->checkDirectoryPermissions($basePath),
            'php_settings' => $this->checkPHPSettings(),
            'disabled_functions' => $this->checkDisabledFunctions(),
        ];
    }
    
    /**
     * Verificar se todos os requisitos obrigatórios estão OK
     */
    public function allRequiredPassed(): bool
    {
        foreach ($this->checks as $check) {
            if (isset($check['type']) && $check['type'] === 'required' && !$check['status']) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Obter resumo das verificações
     */
    public function getSummary(): array
    {
        $passed = 0;
        $failed = 0;
        $warnings = 0;
        
        foreach ($this->checks as $check) {
            if (!isset($check['status'])) continue;
            
            if ($check['status']) {
                $passed++;
            } elseif (isset($check['type']) && $check['type'] === 'required') {
                $failed++;
            } else {
                $warnings++;
            }
        }
        
        return [
            'passed' => $passed,
            'failed' => $failed,
            'warnings' => $warnings,
            'total' => $passed + $failed + $warnings,
            'can_proceed' => $failed === 0
        ];
    }
    
    /**
     * Converter tamanho para bytes
     */
    private function parseSize(string $size): int
    {
        $unit = strtoupper(substr($size, -1));
        $value = (int)$size;
        
        switch ($unit) {
            case 'G': return $value * 1024 * 1024 * 1024;
            case 'M': return $value * 1024 * 1024;
            case 'K': return $value * 1024;
            default: return $value;
        }
    }
    
    /**
     * Obter todas as verificações
     */
    public function getChecks(): array
    {
        return $this->checks;
    }
}
