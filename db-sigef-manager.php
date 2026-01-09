<?php
/**
 * SIGEF V1.2 PNA - Database Manager
 * Gestão de Base de Dados
 * Versão 1.0
 * 
 * Design Profissional baseado no tema adminbs5
 */

session_start();

define('APP_PATH', __DIR__);

// Handler para download de backups
if (isset($_GET['download']) && !empty($_GET['download'])) {
    $filename = basename($_GET['download']); // Segurança: apenas o nome do ficheiro
    $filepath = APP_PATH . '/tmp/backups/' . $filename;
    
    if (file_exists($filepath) && preg_match('/^sigef_backup_.*\.sql$/', $filename)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        header('Cache-Control: no-cache, no-store, must-revalidate');
        readfile($filepath);
        exit;
    }
}

// Verificar autenticação
$authenticated = false;
$authError = '';

if (isset($_POST['auth_password'])) {
    $managerPassword = 'sigef2024manager';
    if ($_POST['auth_password'] === $managerPassword) {
        $_SESSION['db_manager_auth'] = true;
        $authenticated = true;
    } else {
        $authError = 'Palavra-passe incorreta';
    }
}

if (isset($_SESSION['db_manager_auth']) && $_SESSION['db_manager_auth'] === true) {
    $authenticated = true;
}

if (isset($_GET['logout'])) {
    unset($_SESSION['db_manager_auth']);
    header('Location: db-sigef-manager.php');
    exit;
}

require_once APP_PATH . '/vendor/adianti/plugins/install/DatabaseInstall.php';
require_once APP_PATH . '/vendor/adianti/plugins/install/ConfigInstall.php';

use Adianti\Plugins\Install\DatabaseInstall;
use Adianti\Plugins\Install\ConfigInstall;

$config = new ConfigInstall(APP_PATH . '/app/config');
$database = new DatabaseInstall();

$dbConfigFile = APP_PATH . '/app/config/sigef.php';
$dbConfig = [];
if (file_exists($dbConfigFile)) {
    $dbConfig = require $dbConfigFile;
}

$message = '';
$messageType = '';

if ($authenticated && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if (!empty($dbConfig)) {
        $database->configure([
            'driver' => $dbConfig['type'] ?? 'mysql',
            'host' => $dbConfig['host'] ?? 'localhost',
            'port' => $dbConfig['port'] ?? '3306',
            'name' => $dbConfig['name'] ?? 'sigef',
            'user' => $dbConfig['user'] ?? '',
            'pass' => $dbConfig['pass'] ?? '',
        ]);
        $database->connect();
    }
    
    switch ($action) {
        case 'backup':
            $backupDir = APP_PATH . '/tmp/backups';
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }
            $backupFile = $backupDir . '/sigef_backup_' . date('Y-m-d_His') . '.sql';
            
            if ($database->backup($backupFile)) {
                $message = 'Backup criado: ' . basename($backupFile);
                $messageType = 'success';
            } else {
                $message = implode('<br>', $database->getErrors());
                $messageType = 'danger';
            }
            break;
            
        case 'execute_sql':
            $sql = $_POST['sql'] ?? '';
            if (!empty($sql)) {
                if ($database->executeSQL($sql)) {
                    $message = 'SQL executado com sucesso!';
                    $messageType = 'success';
                } else {
                    $message = implode('<br>', $database->getErrors());
                    $messageType = 'danger';
                }
            }
            break;
            
        case 'run_migration':
            $migrationFile = $_POST['migration_file'] ?? '';
            $filePath = APP_PATH . '/app/database/' . $migrationFile;
            
            if (file_exists($filePath)) {
                if ($database->executeSqlFile($filePath)) {
                    $message = 'Migration executada: ' . $migrationFile;
                    $messageType = 'success';
                } else {
                    $message = implode('<br>', $database->getErrors());
                    $messageType = 'danger';
                }
            } else {
                $message = 'Ficheiro não encontrado';
                $messageType = 'danger';
            }
            break;
            
        case 'reset_admin':
            $newPass = $_POST['new_password'] ?? '';
            if (!empty($newPass)) {
                $hashedPass = password_hash($newPass, PASSWORD_DEFAULT);
                $sql = "UPDATE system_users SET password = '{$hashedPass}' WHERE login = 'admin'";
                if ($database->executeSQL($sql)) {
                    $message = 'Palavra-passe redefinida!';
                    $messageType = 'success';
                } else {
                    $message = implode('<br>', $database->getErrors());
                    $messageType = 'danger';
                }
            }
            break;
            
        case 'clear_sessions':
            $sessionDir = APP_PATH . '/tmp/session';
            $count = 0;
            if (is_dir($sessionDir)) {
                $files = glob($sessionDir . '/*');
                foreach ($files as $file) {
                    if (is_file($file)) { unlink($file); $count++; }
                }
            }
            $message = "Sessões limpas ({$count} ficheiros)";
            $messageType = 'success';
            break;
            
        case 'clear_cache':
            $cacheDir = APP_PATH . '/tmp';
            $count = 0;
            $files = glob($cacheDir . '/*.html');
            foreach ($files as $file) {
                if (is_file($file)) { unlink($file); $count++; }
            }
            $message = "Cache limpa ({$count} ficheiros)";
            $messageType = 'success';
            break;
    }
}

$tables = [];
if ($authenticated && !empty($dbConfig)) {
    $database->configure([
        'driver' => $dbConfig['type'] ?? 'mysql',
        'host' => $dbConfig['host'] ?? 'localhost',
        'port' => $dbConfig['port'] ?? '3306',
        'name' => $dbConfig['name'] ?? 'sigef',
        'user' => $dbConfig['user'] ?? '',
        'pass' => $dbConfig['pass'] ?? '',
    ]);
    if ($database->connect()) {
        $tables = $database->getTables();
    }
}

$sqlFiles = [];
if (is_dir(APP_PATH . '/app/database')) {
    $sqlFiles = glob(APP_PATH . '/app/database/*.sql');
    $sqlFiles = array_map('basename', $sqlFiles);
}

$backups = [];
$backupDir = APP_PATH . '/tmp/backups';
if (is_dir($backupDir)) {
    $backups = glob($backupDir . '/*.sql');
    $backups = array_map('basename', $backups);
    rsort($backups);
}
?>
<!DOCTYPE html>
<html class="notranslate" translate="no" data-bs-theme="light" lang="pt-AO">
<head>
    <title>SIGEF V1.2 PNA - Database Manager</title>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1" name="viewport">
    <link rel="shortcut icon" type="image/png" href="favicon.png" />
    
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link crossorigin href="https://fonts.gstatic.com" rel="preconnect">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        :root {
            --sigef-accent: #5B68FF;
            --sigef-accent-hover: #4954e0;
            --sigef-success: #00a65a;
            --sigef-warning: #f39c12;
            --sigef-danger: #dd4b39;
        }
        
        * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        
        body {
            background: #f4f6f9;
            min-height: 100vh;
        }
        
        /* Login Container */
        .login-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: url('app/images/sigef-background.png') no-repeat center center;
            background-size: cover;
            position: relative;
        }
        
        .login-wrapper::before {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.4);
        }
        
        .login-container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 380px;
            padding: 1rem;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        
        .login-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #ffffff;
            margin: 0 0 0.25rem;
        }
        
        .login-subtitle {
            color: rgba(255,255,255,0.7);
            font-size: 0.9rem;
        }
        
        .login-card {
            background: #fff;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        /* Navbar */
        .navbar-sigef {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            padding: 0.75rem 0;
        }
        
        .navbar-sigef .navbar-brand {
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        /* Cards */
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            margin-bottom: 1.25rem;
        }
        
        .card-header {
            background: #fff;
            border-bottom: 1px solid #f0f0f0;
            font-weight: 600;
            font-size: 0.95rem;
            padding: 0.875rem 1.25rem;
            border-radius: 10px 10px 0 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .card-header i {
            color: var(--sigef-accent);
        }
        
        .card-body {
            padding: 1.25rem;
        }
        
        /* Stats */
        .stat-card {
            text-align: center;
            padding: 1.5rem 1rem;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--sigef-accent);
            line-height: 1;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 0.8rem;
            margin-top: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Buttons */
        .btn-sigef {
            padding: 0.625rem 1.25rem;
            border-radius: 6px;
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        
        .btn-sigef-primary {
            background: var(--sigef-accent);
            border-color: var(--sigef-accent);
            color: #fff;
        }
        
        .btn-sigef-primary:hover {
            background: var(--sigef-accent-hover);
            border-color: var(--sigef-accent-hover);
            color: #fff;
            transform: translateY(-1px);
        }
        
        /* Quick Actions */
        .quick-action {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 1rem;
            border-radius: 8px;
            background: #f8f9fa;
            transition: all 0.2s;
            border: none;
            width: 100%;
            text-align: center;
        }
        
        .quick-action:hover {
            background: #e9ecef;
            transform: translateY(-2px);
        }
        
        .quick-action i {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .quick-action span {
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .quick-action.success { color: var(--sigef-success); }
        .quick-action.warning { color: var(--sigef-warning); }
        .quick-action.info { color: var(--sigef-accent); }
        .quick-action.danger { color: var(--sigef-danger); }
        
        /* Tables List */
        .table-list {
            max-height: 280px;
            overflow-y: auto;
        }
        
        .table-item {
            padding: 0.5rem 0.75rem;
            background: #f8f9fa;
            border-radius: 4px;
            margin-bottom: 4px;
            font-family: 'SF Mono', 'Monaco', monospace;
            font-size: 0.8rem;
            border-left: 3px solid var(--sigef-accent);
        }
        
        /* SQL Editor */
        .sql-editor {
            font-family: 'SF Mono', 'Monaco', 'Consolas', monospace;
            min-height: 120px;
            background: #1e1e1e;
            color: #d4d4d4;
            border: none;
            border-radius: 8px;
            font-size: 0.85rem;
        }
        
        .sql-editor:focus {
            background: #1e1e1e;
            color: #d4d4d4;
            box-shadow: 0 0 0 3px rgba(91, 104, 255, 0.2);
        }
        
        /* Backup List */
        .backup-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.625rem 0.875rem;
            background: #f8f9fa;
            border-radius: 6px;
            margin-bottom: 6px;
            font-size: 0.85rem;
        }
        
        /* Form */
        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 6px;
            padding: 0.625rem 0.875rem;
            font-size: 0.9rem;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--sigef-accent);
            box-shadow: 0 0 0 3px rgba(91, 104, 255, 0.15);
        }
        
        .form-label {
            font-weight: 500;
            font-size: 0.9rem;
            margin-bottom: 0.375rem;
        }
        
        /* Alert */
        .alert {
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <?php if (!$authenticated): ?>
        <!-- Login -->
        <div class="login-wrapper">
            <div class="login-container">
                <div class="login-header">
                    <div class="mb-3">
                        <i class="bi bi-database-gear text-white" style="font-size: 3rem;"></i>
                    </div>
                    <h1 class="login-title">SIGEF V1.2 PNA</h1>
                    <p class="login-subtitle">Database Manager</p>
                </div>
                
                <div class="login-card">
                    <?php if ($authError): ?>
                        <div class="alert alert-danger mb-3"><?= $authError ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Palavra-passe de Gestão</label>
                            <input type="password" name="auth_password" class="form-control" required autofocus 
                                   placeholder="••••••••">
                        </div>
                        <button type="submit" class="btn btn-sigef btn-sigef-primary w-100">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Entrar
                        </button>
                    </form>
                    
                    <div class="text-center mt-3">
                        <a href="index.php" class="text-muted text-decoration-none" style="font-size: 0.85rem;">
                            <i class="bi bi-arrow-left me-1"></i>Voltar ao SIGEF
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Dashboard -->
        <nav class="navbar navbar-dark navbar-sigef">
            <div class="container">
                <span class="navbar-brand">
                    <i class="bi bi-database-gear me-2"></i>SIGEF V1.2 PNA - Database Manager
                </span>
                <a href="?logout=1" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-box-arrow-right me-1"></i>Sair
                </a>
            </div>
        </nav>
        
        <div class="container py-4">
            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
                    <?= $message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Stats -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="card stat-card">
                        <div class="stat-number"><?= count($tables) ?></div>
                        <div class="stat-label">Tabelas</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card stat-card">
                        <div class="stat-number"><?= count($sqlFiles) ?></div>
                        <div class="stat-label">Ficheiros SQL</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card stat-card">
                        <div class="stat-number"><?= count($backups) ?></div>
                        <div class="stat-label">Backups</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card stat-card">
                        <div class="stat-number" style="font-size: 1.25rem;"><?= strtoupper($dbConfig['type'] ?? 'N/A') ?></div>
                        <div class="stat-label">Driver</div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <!-- Left Column -->
                <div class="col-lg-6">
                    <!-- Quick Actions -->
                    <div class="card">
                        <div class="card-header">
                            <i class="bi bi-lightning"></i> Acções Rápidas
                        </div>
                        <div class="card-body">
                            <div class="row g-2">
                                <div class="col-6 col-md-3">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="backup">
                                        <button type="submit" class="quick-action success">
                                            <i class="bi bi-download"></i>
                                            <span>Backup</span>
                                        </button>
                                    </form>
                                </div>
                                <div class="col-6 col-md-3">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="clear_cache">
                                        <button type="submit" class="quick-action warning">
                                            <i class="bi bi-trash"></i>
                                            <span>Limpar Cache</span>
                                        </button>
                                    </form>
                                </div>
                                <div class="col-6 col-md-3">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="clear_sessions">
                                        <button type="submit" class="quick-action info">
                                            <i class="bi bi-person-x"></i>
                                            <span>Sessões</span>
                                        </button>
                                    </form>
                                </div>
                                <div class="col-6 col-md-3">
                                    <a href="install.php?force=1" class="quick-action danger text-decoration-none">
                                        <i class="bi bi-arrow-clockwise"></i>
                                        <span>Reinstalar</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tables -->
                    <div class="card">
                        <div class="card-header">
                            <i class="bi bi-table"></i> Tabelas (<?= count($tables) ?>)
                        </div>
                        <div class="card-body table-list">
                            <?php if (empty($tables)): ?>
                                <p class="text-muted mb-0">Nenhuma tabela encontrada.</p>
                            <?php else: ?>
                                <?php foreach ($tables as $table): ?>
                                    <div class="table-item"><?= htmlspecialchars($table) ?></div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Backups -->
                    <div class="card">
                        <div class="card-header">
                            <i class="bi bi-archive"></i> Backups
                        </div>
                        <div class="card-body">
                            <?php if (empty($backups)): ?>
                                <p class="text-muted mb-0">Nenhum backup encontrado.</p>
                            <?php else: ?>
                                <?php foreach (array_slice($backups, 0, 5) as $backup): ?>
                                    <div class="backup-item">
                                        <span><i class="bi bi-file-code me-2"></i><?= $backup ?></span>
                                        <a href="?download=<?= urlencode($backup) ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-download"></i>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Right Column -->
                <div class="col-lg-6">
                    <!-- Run Migration -->
                    <div class="card">
                        <div class="card-header">
                            <i class="bi bi-file-earmark-code"></i> Executar Migration
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="run_migration">
                                <div class="mb-3">
                                    <select name="migration_file" class="form-select" required>
                                        <option value="">Selecionar ficheiro SQL...</option>
                                        <?php foreach ($sqlFiles as $file): ?>
                                            <option value="<?= $file ?>"><?= $file ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-sigef btn-sigef-primary">
                                    <i class="bi bi-play me-1"></i>Executar
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- SQL Console -->
                    <div class="card">
                        <div class="card-header">
                            <i class="bi bi-terminal"></i> Consola SQL
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="execute_sql">
                                <div class="mb-3">
                                    <textarea name="sql" class="form-control sql-editor" 
                                        placeholder="SELECT * FROM system_users LIMIT 10;"></textarea>
                                </div>
                                <button type="submit" class="btn btn-sigef btn-sigef-primary">
                                    <i class="bi bi-play me-1"></i>Executar SQL
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Reset Admin -->
                    <div class="card">
                        <div class="card-header">
                            <i class="bi bi-key"></i> Redefinir Admin
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="reset_admin">
                                <div class="mb-3">
                                    <label class="form-label">Nova Palavra-passe</label>
                                    <input type="password" name="new_password" class="form-control" 
                                           placeholder="Mínimo 8 caracteres" required>
                                </div>
                                <button type="submit" class="btn btn-danger btn-sigef">
                                    <i class="bi bi-arrow-clockwise me-1"></i>Redefinir
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="text-center text-muted py-3" style="font-size: 0.85rem;">
                SIGEF V1.2 PNA &bull; Powered by Adianti Framework
            </div>
        </div>
    <?php endif; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
