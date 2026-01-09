<?php
/**
 * ConfigInstall - Gestão de Configurações
 * SIGEF Builder v1.0
 * 
 * Baseado no ecossistema adianti/plugins
 */

namespace Adianti\Plugins\Install;

class ConfigInstall
{
    private $configPath;
    private $config = [];
    private $errors = [];
    private $messages = [];
    
    /**
     * Construtor
     */
    public function __construct(string $configPath = '')
    {
        $this->configPath = $configPath ?: dirname(__DIR__, 4) . '/app/config';
    }
    
    /**
     * Carregar configuração existente
     */
    public function loadConfig(string $filename = 'application.php'): array
    {
        $filePath = $this->configPath . '/' . $filename;
        
        if (file_exists($filePath)) {
            $this->config = require $filePath;
            return $this->config;
        }
        
        $this->errors[] = "Ficheiro de configuração não encontrado: {$filename}";
        return [];
    }
    
    /**
     * Guardar configuração
     */
    public function saveConfig(array $config, string $filename = 'application.php'): bool
    {
        $filePath = $this->configPath . '/' . $filename;
        
        try {
            $content = "<?php\nreturn " . $this->arrayToPhp($config, 0) . ";\n";
            file_put_contents($filePath, $content);
            $this->messages[] = "Configuração guardada: {$filename}";
            return true;
        } catch (\Exception $e) {
            $this->errors[] = "Erro ao guardar configuração: " . $e->getMessage();
            return false;
        }
    }
    
    /**
     * Adicionar secção builder à configuração
     */
    public function addBuilderSection(array $builderConfig = []): bool
    {
        $this->loadConfig();
        
        $defaultBuilder = [
            'version' => '1.0',
            'name' => 'SIGEF Builder',
            'installed' => true,
            'install_date' => date('Y-m-d H:i:s'),
            'database_type' => 'mysql',
            'last_update' => null,
        ];
        
        $this->config['builder'] = array_merge($defaultBuilder, $builderConfig);
        
        return $this->saveConfig($this->config);
    }
    
    /**
     * Gerar ficheiro de configuração de base de dados
     */
    public function generateDatabaseConfig(array $dbConfig, string $configName): bool
    {
        $template = <<<PHP
<?php
return [
    'host' => '{$dbConfig['host']}',
    'port' => '{$dbConfig['port']}',
    'name' => '{$dbConfig['name']}',
    'user' => '{$dbConfig['user']}',
    'pass' => '{$dbConfig['pass']}',
    'type' => '{$dbConfig['type']}',
];
PHP;
        
        $filePath = $this->configPath . '/' . $configName . '.php';
        
        try {
            file_put_contents($filePath, $template);
            $this->messages[] = "Configuração de BD criada: {$configName}.php";
            return true;
        } catch (\Exception $e) {
            $this->errors[] = "Erro ao criar config BD: " . $e->getMessage();
            return false;
        }
    }
    
    /**
     * Atualizar configuração geral
     */
    public function updateGeneralConfig(array $settings): bool
    {
        $this->loadConfig();
        
        foreach ($settings as $section => $values) {
            if (!isset($this->config[$section])) {
                $this->config[$section] = [];
            }
            
            if (is_array($values)) {
                $this->config[$section] = array_merge($this->config[$section], $values);
            } else {
                $this->config[$section] = $values;
            }
        }
        
        return $this->saveConfig($this->config);
    }
    
    /**
     * Verificar se já está instalado
     */
    public function isInstalled(): bool
    {
        $this->loadConfig();
        return isset($this->config['builder']['installed']) && $this->config['builder']['installed'] === true;
    }
    
    /**
     * Obter versão do builder
     */
    public function getBuilderVersion(): ?string
    {
        $this->loadConfig();
        return $this->config['builder']['version'] ?? null;
    }
    
    /**
     * Criar ficheiro de lock de instalação
     */
    public function createInstallLock(): bool
    {
        $lockFile = dirname($this->configPath) . '/.install_lock';
        
        $content = json_encode([
            'installed_at' => date('Y-m-d H:i:s'),
            'version' => '1.0',
            'php_version' => PHP_VERSION,
        ], JSON_PRETTY_PRINT);
        
        return file_put_contents($lockFile, $content) !== false;
    }
    
    /**
     * Verificar se existe lock de instalação
     */
    public function hasInstallLock(): bool
    {
        $lockFile = dirname($this->configPath) . '/.install_lock';
        return file_exists($lockFile);
    }
    
    /**
     * Remover lock de instalação (para reinstalação)
     */
    public function removeInstallLock(): bool
    {
        $lockFile = dirname($this->configPath) . '/.install_lock';
        if (file_exists($lockFile)) {
            return unlink($lockFile);
        }
        return true;
    }
    
    /**
     * Gerar seed aleatório para aplicação
     */
    public function generateSeed(int $length = 32): string
    {
        return bin2hex(random_bytes($length / 2));
    }
    
    /**
     * Atualizar seed da aplicação
     */
    public function updateSeed(): bool
    {
        return $this->updateGeneralConfig([
            'general' => [
                'seed' => $this->generateSeed()
            ]
        ]);
    }
    
    /**
     * Criar utilizador administrador padrão
     */
    public function getDefaultAdminSQL(string $name, string $login, string $password, string $email): string
    {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        return "INSERT INTO system_users (id, name, login, password, email, active, accepted_term_policy) 
                VALUES (1, '{$name}', '{$login}', '{$hashedPassword}', '{$email}', 'Y', 'Y')
                ON DUPLICATE KEY UPDATE 
                name = VALUES(name), 
                password = VALUES(password), 
                email = VALUES(email);";
    }
    
    /**
     * Converter array para código PHP
     */
    private function arrayToPhp(array $array, int $indent): string
    {
        $spaces = str_repeat('    ', $indent);
        $innerSpaces = str_repeat('    ', $indent + 1);
        
        $items = [];
        $isAssoc = array_keys($array) !== range(0, count($array) - 1);
        
        foreach ($array as $key => $value) {
            $keyStr = $isAssoc ? "'{$key}' => " : '';
            
            if (is_array($value)) {
                $items[] = $innerSpaces . $keyStr . $this->arrayToPhp($value, $indent + 1);
            } elseif (is_bool($value)) {
                $items[] = $innerSpaces . $keyStr . ($value ? 'true' : 'false');
            } elseif (is_null($value)) {
                $items[] = $innerSpaces . $keyStr . 'null';
            } elseif (is_numeric($value) && !is_string($value)) {
                $items[] = $innerSpaces . $keyStr . $value;
            } else {
                $escaped = addslashes($value);
                $items[] = $innerSpaces . $keyStr . "'{$escaped}'";
            }
        }
        
        return "[\n" . implode(",\n", $items) . "\n{$spaces}]";
    }
    
    /**
     * Obter erros
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
    
    /**
     * Obter mensagens
     */
    public function getMessages(): array
    {
        return $this->messages;
    }
    
    /**
     * Obter caminho de configuração
     */
    public function getConfigPath(): string
    {
        return $this->configPath;
    }
}
