<?php
/**
 * DatabaseInstall - Classe de Instalação de Base de Dados
 * SIGEF Builder v1.0
 * 
 * Baseado no ecossistema adianti/plugins
 */

namespace Adianti\Plugins\Install;

class DatabaseInstall
{
    private $connection;
    private $driver;
    private $host;
    private $port;
    private $name;
    private $user;
    private $pass;
    private $errors = [];
    private $messages = [];
    
    /**
     * Tipos de base de dados suportados
     */
    const DRIVER_MYSQL = 'mysql';
    const DRIVER_SQLITE = 'sqlite';
    const DRIVER_PGSQL = 'pgsql';
    
    /**
     * Configurar parâmetros de conexão
     */
    public function configure(array $config): self
    {
        $this->driver = $config['driver'] ?? self::DRIVER_MYSQL;
        $this->host = $config['host'] ?? 'localhost';
        $this->port = $config['port'] ?? ($this->driver === self::DRIVER_MYSQL ? 3306 : 5432);
        $this->name = $config['name'] ?? '';
        $this->user = $config['user'] ?? '';
        $this->pass = $config['pass'] ?? '';
        
        return $this;
    }
    
    /**
     * Testar conexão com a base de dados
     */
    public function testConnection(): bool
    {
        try {
            $dsn = $this->buildDSN(false);
            $pdo = new \PDO($dsn, $this->user, $this->pass, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
            ]);
            $this->messages[] = "Conexão estabelecida com sucesso!";
            return true;
        } catch (\PDOException $e) {
            $this->errors[] = "Erro de conexão: " . $e->getMessage();
            return false;
        }
    }
    
    /**
     * Criar base de dados se não existir
     */
    public function createDatabase(): bool
    {
        try {
            if ($this->driver === self::DRIVER_SQLITE) {
                // SQLite cria automaticamente
                return true;
            }
            
            $dsn = $this->buildDSN(false);
            $pdo = new \PDO($dsn, $this->user, $this->pass, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
            ]);
            
            $charset = $this->driver === self::DRIVER_MYSQL ? 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci' : '';
            $sql = "CREATE DATABASE IF NOT EXISTS `{$this->name}` {$charset}";
            $pdo->exec($sql);
            
            $this->messages[] = "Base de dados '{$this->name}' criada/verificada com sucesso!";
            return true;
        } catch (\PDOException $e) {
            $this->errors[] = "Erro ao criar base de dados: " . $e->getMessage();
            return false;
        }
    }
    
    /**
     * Conectar à base de dados
     */
    public function connect(): bool
    {
        try {
            $dsn = $this->buildDSN(true);
            $this->connection = new \PDO($dsn, $this->user, $this->pass, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
            ]);
            
            if ($this->driver === self::DRIVER_MYSQL) {
                $this->connection->exec("SET NAMES utf8mb4");
            }
            
            $this->messages[] = "Conectado à base de dados '{$this->name}'";
            return true;
        } catch (\PDOException $e) {
            $this->errors[] = "Erro ao conectar: " . $e->getMessage();
            return false;
        }
    }
    
    /**
     * Executar arquivo SQL
     */
    public function executeSqlFile(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            $this->errors[] = "Arquivo SQL não encontrado: {$filePath}";
            return false;
        }
        
        try {
            $sql = file_get_contents($filePath);
            
            // Remover comentários
            $sql = preg_replace('/--.*$/m', '', $sql);
            $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
            
            // Dividir em statements
            $statements = array_filter(
                array_map('trim', explode(';', $sql)),
                fn($s) => !empty($s)
            );
            
            $executed = 0;
            foreach ($statements as $statement) {
                if (!empty(trim($statement))) {
                    $this->connection->exec($statement);
                    $executed++;
                }
            }
            
            $this->messages[] = "Executados {$executed} statements de: " . basename($filePath);
            return true;
        } catch (\PDOException $e) {
            $this->errors[] = "Erro ao executar SQL: " . $e->getMessage();
            return false;
        }
    }
    
    /**
     * Executar SQL direto
     */
    public function executeSQL(string $sql): bool
    {
        try {
            $this->connection->exec($sql);
            return true;
        } catch (\PDOException $e) {
            $this->errors[] = "Erro SQL: " . $e->getMessage();
            return false;
        }
    }
    
    /**
     * Verificar se tabela existe
     */
    public function tableExists(string $tableName): bool
    {
        try {
            $result = $this->connection->query("SELECT 1 FROM {$tableName} LIMIT 1");
            return $result !== false;
        } catch (\PDOException $e) {
            return false;
        }
    }
    
    /**
     * Obter lista de tabelas
     */
    public function getTables(): array
    {
        try {
            if ($this->driver === self::DRIVER_MYSQL) {
                $stmt = $this->connection->query("SHOW TABLES");
            } elseif ($this->driver === self::DRIVER_SQLITE) {
                $stmt = $this->connection->query("SELECT name FROM sqlite_master WHERE type='table'");
            } else {
                $stmt = $this->connection->query("SELECT tablename FROM pg_tables WHERE schemaname='public'");
            }
            
            return $stmt->fetchAll(\PDO::FETCH_COLUMN);
        } catch (\PDOException $e) {
            $this->errors[] = "Erro ao listar tabelas: " . $e->getMessage();
            return [];
        }
    }
    
    /**
     * Fazer backup da base de dados
     */
    public function backup(string $backupPath): bool
    {
        try {
            $tables = $this->getTables();
            $backup = "-- SIGEF Builder Backup\n";
            $backup .= "-- Data: " . date('Y-m-d H:i:s') . "\n\n";
            
            foreach ($tables as $table) {
                // Estrutura
                if ($this->driver === self::DRIVER_MYSQL) {
                    $result = $this->connection->query("SHOW CREATE TABLE `{$table}`")->fetch();
                    $backup .= $result['Create Table'] . ";\n\n";
                }
                
                // Dados
                $rows = $this->connection->query("SELECT * FROM `{$table}`")->fetchAll();
                foreach ($rows as $row) {
                    $values = array_map(fn($v) => $this->connection->quote($v ?? ''), $row);
                    $backup .= "INSERT INTO `{$table}` VALUES (" . implode(', ', $values) . ");\n";
                }
                $backup .= "\n";
            }
            
            file_put_contents($backupPath, $backup);
            $this->messages[] = "Backup criado: {$backupPath}";
            return true;
        } catch (\Exception $e) {
            $this->errors[] = "Erro no backup: " . $e->getMessage();
            return false;
        }
    }
    
    /**
     * Gerar arquivo de configuração de conexão
     */
    public function generateConfigFile(string $configName): string
    {
        $config = "<?php\n";
        $config .= "return [\n";
        $config .= "    'host' => '{$this->host}',\n";
        $config .= "    'port' => '{$this->port}',\n";
        $config .= "    'name' => '{$this->name}',\n";
        $config .= "    'user' => '{$this->user}',\n";
        $config .= "    'pass' => '{$this->pass}',\n";
        $config .= "    'type' => '{$this->driver}',\n";
        $config .= "];\n";
        
        return $config;
    }
    
    /**
     * Construir DSN
     */
    private function buildDSN(bool $includeDatabase): string
    {
        switch ($this->driver) {
            case self::DRIVER_SQLITE:
                return "sqlite:{$this->name}";
                
            case self::DRIVER_PGSQL:
                $dsn = "pgsql:host={$this->host};port={$this->port}";
                if ($includeDatabase) {
                    $dsn .= ";dbname={$this->name}";
                }
                return $dsn;
                
            case self::DRIVER_MYSQL:
            default:
                $dsn = "mysql:host={$this->host};port={$this->port}";
                if ($includeDatabase) {
                    $dsn .= ";dbname={$this->name}";
                }
                $dsn .= ";charset=utf8mb4";
                return $dsn;
        }
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
     * Limpar erros e mensagens
     */
    public function clearMessages(): void
    {
        $this->errors = [];
        $this->messages = [];
    }
    
    /**
     * Obter conexão PDO
     */
    public function getConnection(): ?\PDO
    {
        return $this->connection;
    }
}
