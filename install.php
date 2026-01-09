<?php
/**
 * SIGEF Builder - Wizard de Instalação
 * Versão 1.0
 * 
 * Design Profissional baseado no tema adminbs5
 */

session_start();

// Evitar cache
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

// Definir caminho base
define('APP_PATH', __DIR__);

// Carregar classes de instalação
require_once APP_PATH . '/vendor/adianti/plugins/install/DatabaseInstall.php';
require_once APP_PATH . '/vendor/adianti/plugins/install/ExtensionsInstall.php';
require_once APP_PATH . '/vendor/adianti/plugins/install/ConfigInstall.php';

use Adianti\Plugins\Install\DatabaseInstall;
use Adianti\Plugins\Install\ExtensionsInstall;
use Adianti\Plugins\Install\ConfigInstall;

// Inicializar classes
$extensions = new ExtensionsInstall();
$database = new DatabaseInstall();
$config = new ConfigInstall(APP_PATH . '/app/config');

// Verificar se já está instalado
if ($config->hasInstallLock() && !isset($_GET['force'])) {
    header('Location: index.php');
    exit;
}

// Processar step actual
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$error = '';
$success = '';

// Processar formulários
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'check_requirements':
            $step = 2;
            break;
            
        case 'configure_database':
            $dbConfig = [
                'driver' => $_POST['db_driver'] ?? 'mysql',
                'host' => $_POST['db_host'] ?? 'localhost',
                'port' => $_POST['db_port'] ?? '3306',
                'name' => $_POST['db_name'] ?? '',
                'user' => $_POST['db_user'] ?? '',
                'pass' => $_POST['db_pass'] ?? '',
            ];
            
            $database->configure($dbConfig);
            
            if ($database->testConnection()) {
                $_SESSION['db_config'] = $dbConfig;
                $step = 3;
            } else {
                $error = implode('<br>', $database->getErrors());
                $step = 2;
            }
            break;
            
        case 'install_database':
            $dbConfig = $_SESSION['db_config'] ?? [];
            
            if (empty($dbConfig)) {
                $error = 'Configuração de base de dados perdida. Volte ao passo anterior.';
                $step = 2;
                break;
            }
            
            $database->configure($dbConfig);
            
            if (!$database->createDatabase()) {
                $error = implode('<br>', $database->getErrors());
                break;
            }
            
            if (!$database->connect()) {
                $error = implode('<br>', $database->getErrors());
                break;
            }
            
            $schemaFile = APP_PATH . '/app/database/sigef_schema.sql';
            if (file_exists($schemaFile)) {
                if (!$database->executeSqlFile($schemaFile)) {
                    $error = implode('<br>', $database->getErrors());
                    break;
                }
            }
            
            $step = 4;
            $success = implode('<br>', $database->getMessages());
            break;
            
        case 'configure_admin':
            $dbConfig = $_SESSION['db_config'] ?? [];
            
            $adminName = $_POST['admin_name'] ?? 'Administrador';
            $adminLogin = $_POST['admin_login'] ?? 'admin';
            $adminPass = $_POST['admin_pass'] ?? '';
            $adminEmail = $_POST['admin_email'] ?? '';
            
            if (empty($adminPass)) {
                $error = 'A palavra-passe é obrigatória.';
                $step = 4;
                break;
            }
            
            $database->configure($dbConfig);
            $database->connect();
            
            $sql = $config->getDefaultAdminSQL($adminName, $adminLogin, $adminPass, $adminEmail);
            $database->executeSQL($sql);
            
            $config->addBuilderSection([
                'database_type' => $dbConfig['driver'],
            ]);
            
            $config->generateDatabaseConfig([
                'host' => $dbConfig['host'],
                'port' => $dbConfig['port'],
                'name' => $dbConfig['name'],
                'user' => $dbConfig['user'],
                'pass' => $dbConfig['pass'],
                'type' => $dbConfig['driver'],
            ], 'sigef');
            
            $config->createInstallLock();
            
            $step = 5;
            break;
    }
}

// Executar verificações no step 1
$checksResults = [];
if ($step === 1) {
    $checksResults = $extensions->runAllChecks(APP_PATH);
}

$summary = $extensions->getSummary();
?>
<!DOCTYPE html>
<html class="notranslate" translate="no" data-bs-theme="light" data-menu-theme="dark" lang="pt-AO">
<head>
    <title>SIGEF V1.2 PNA - Assistente de Instalação</title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <link rel="shortcut icon" type="image/png" href="favicon.png" />
    
    <!-- Google Fonts - Inter -->
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link crossorigin href="https://fonts.gstatic.com" rel="preconnect">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        :root {
            --sigef-accent: #5B68FF;
            --sigef-accent-hover: #4954e0;
            --sigef-success: #00a65a;
            --sigef-warning: #f39c12;
            --sigef-danger: #dd4b39;
            --bs-dark: rgb(48 51 55);
            --bs-light: white;
            --bs-border-radius: 6px;
            --ad-font-size: 0.95rem;
        }
        
        * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            box-sizing: border-box;
        }
        
        body, html {
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }
        
        .wrapper {
            min-height: 100vh;
            position: relative;
            overflow: hidden;
        }
        
        .main {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: url('app/images/sigef-background.png') no-repeat center center;
            background-size: cover;
            position: relative;
            padding: 2rem;
        }
        
        .main::before {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.3);
        }
        
        .installer-container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 700px;
        }
        
        /* Header */
        .installer-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        
        .sigef-logo {
            width: 60px;
            height: 60px;
            margin: 0 auto 0.75rem;
            border-radius: 50%;
            overflow: hidden;
            background: white;
            padding: 5px;
        }
        
        .sigef-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        .installer-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: 0.05em;
            margin: 0 0 0.25rem 0;
        }
        
        .installer-subtitle {
            font-size: 0.95rem;
            font-weight: 400;
            color: rgba(255,255,255,0.8);
            margin: 0;
        }
        
        /* Card */
        .installer-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        /* Steps */
        .steps-indicator {
            display: flex;
            justify-content: center;
            padding: 20px 15px;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            gap: 8px;
        }
        
        .step-item {
            display: flex;
            align-items: center;
            color: #adb5bd;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .step-item.active { color: var(--sigef-accent); }
        .step-item.completed { color: var(--sigef-success); }
        
        .step-circle {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #dee2e6;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 6px;
            font-weight: 600;
            font-size: 0.75rem;
        }
        
        .step-item.active .step-circle {
            background: var(--sigef-accent);
            color: #fff;
        }
        
        .step-item.completed .step-circle {
            background: var(--sigef-success);
            color: #fff;
        }
        
        .step-divider {
            width: 25px;
            height: 2px;
            background: #dee2e6;
            margin: 0 8px;
            align-self: center;
        }
        
        /* Body */
        .installer-body {
            padding: 30px;
        }
        
        .section-title {
            font-size: 1.25rem;
            color: #1a1a2e;
            margin-bottom: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-title i {
            color: var(--sigef-accent);
        }
        
        /* Check Items */
        .check-group {
            margin-bottom: 20px;
        }
        
        .check-group-title {
            font-weight: 600;
            color: #6c757d;
            margin-bottom: 10px;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .check-item {
            display: flex;
            align-items: center;
            padding: 10px 12px;
            background: #f8f9fa;
            border-radius: 6px;
            margin-bottom: 6px;
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        
        .check-item.success { border-left: 3px solid var(--sigef-success); }
        .check-item.warning { border-left: 3px solid var(--sigef-warning); }
        .check-item.error { border-left: 3px solid var(--sigef-danger); }
        
        .check-icon {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            font-size: 0.7rem;
        }
        
        .check-item.success .check-icon { background: var(--sigef-success); color: #fff; }
        .check-item.warning .check-icon { background: var(--sigef-warning); color: #fff; }
        .check-item.error .check-icon { background: var(--sigef-danger); color: #fff; }
        
        /* Forms */
        .form-label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 6px;
            font-size: 0.9rem;
        }
        
        .form-control, .form-select {
            background: #ffffff;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            color: #333;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: all 0.2s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--sigef-accent);
            box-shadow: 0 0 0 3px rgba(91, 104, 255, 0.15);
            outline: none;
        }
        
        .form-control::placeholder {
            color: #adb5bd;
        }
        
        /* Buttons */
        .btn-installer {
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            border-radius: 6px;
            font-size: 0.95rem;
            transition: all 0.2s;
        }
        
        .btn-installer-primary {
            background: var(--sigef-accent);
            border-color: var(--sigef-accent);
            color: #fff;
        }
        
        .btn-installer-primary:hover {
            background: var(--sigef-accent-hover);
            border-color: var(--sigef-accent-hover);
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(91, 104, 255, 0.4);
        }
        
        .btn-installer-primary:active {
            transform: scale(0.98);
        }
        
        /* Alerts */
        .alert-custom {
            border-radius: 8px;
            padding: 12px 16px;
            border: none;
            font-size: 0.9rem;
        }
        
        /* Success */
        .success-container {
            text-align: center;
            padding: 30px;
        }
        
        .success-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--sigef-success) 0%, #2ecc71 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2.5rem;
            color: #fff;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        /* Badges */
        .badge {
            font-weight: 500;
            padding: 0.4em 0.8em;
            font-size: 0.8rem;
        }
        
        /* Summary bar */
        .summary-bar {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        /* Footer */
        .installer-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }
        
        /* Info box */
        .info-box-mini {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .info-box-mini strong {
            color: #495057;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .main { padding: 1rem; }
            .installer-body { padding: 20px; }
            .step-item span { display: none; }
            .step-divider { width: 15px; margin: 0 4px; }
            .installer-footer { flex-direction: column; gap: 10px; }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="main">
            <div class="installer-container">
                <!-- Header -->
                <div class="installer-header">
                    <div class="sigef-logo">
                        <img src="app/images/policia-nacional-logo.png" alt="SIGEF" onerror="this.src='favicon.png'">
                    </div>
                    <h1 class="installer-title">SIGEF V1.2 PNA</h1>
                    <p class="installer-subtitle">Assistente de Instalação</p>
                </div>
                
                <!-- Card -->
                <div class="installer-card">
                    <!-- Steps -->
                    <div class="steps-indicator">
                        <div class="step-item <?= $step >= 1 ? ($step > 1 ? 'completed' : 'active') : '' ?>">
                            <div class="step-circle"><?= $step > 1 ? '<i class="bi bi-check"></i>' : '1' ?></div>
                            <span>Requisitos</span>
                        </div>
                        <div class="step-divider"></div>
                        <div class="step-item <?= $step >= 2 ? ($step > 2 ? 'completed' : 'active') : '' ?>">
                            <div class="step-circle"><?= $step > 2 ? '<i class="bi bi-check"></i>' : '2' ?></div>
                            <span>Base de Dados</span>
                        </div>
                        <div class="step-divider"></div>
                        <div class="step-item <?= $step >= 3 ? ($step > 3 ? 'completed' : 'active') : '' ?>">
                            <div class="step-circle"><?= $step > 3 ? '<i class="bi bi-check"></i>' : '3' ?></div>
                            <span>Instalação</span>
                        </div>
                        <div class="step-divider"></div>
                        <div class="step-item <?= $step >= 4 ? ($step > 4 ? 'completed' : 'active') : '' ?>">
                            <div class="step-circle"><?= $step > 4 ? '<i class="bi bi-check"></i>' : '4' ?></div>
                            <span>Administrador</span>
                        </div>
                        <div class="step-divider"></div>
                        <div class="step-item <?= $step >= 5 ? 'active' : '' ?>">
                            <div class="step-circle">5</div>
                            <span>Concluído</span>
                        </div>
                    </div>
                    
                    <!-- Body -->
                    <div class="installer-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-custom mb-4">
                                <i class="bi bi-exclamation-triangle me-2"></i><?= $error ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success alert-custom mb-4">
                                <i class="bi bi-check-circle me-2"></i><?= $success ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($step === 1): ?>
                            <!-- Step 1: Requirements -->
                            <h2 class="section-title"><i class="bi bi-clipboard-check"></i> Verificação de Requisitos</h2>
                            
                            <?php $phpCheck = $extensions->checkPHPVersion(); ?>
                            
                            <div class="check-group">
                                <div class="check-group-title">Versão PHP</div>
                                <div class="check-item <?= $phpCheck['status'] ? 'success' : 'error' ?>">
                                    <div class="check-icon"><i class="bi bi-<?= $phpCheck['status'] ? 'check' : 'x' ?>"></i></div>
                                    <div class="flex-grow-1">
                                        <strong><?= $phpCheck['name'] ?></strong>
                                        <span class="text-muted ms-2" style="font-size: 0.85rem;">
                                            Requerido: <?= $phpCheck['required'] ?> | Actual: <?= $phpCheck['current'] ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="check-group">
                                <div class="check-group-title">Extensões Obrigatórias</div>
                                <?php foreach ($checksResults['required_extensions'] as $ext => $check): ?>
                                    <div class="check-item <?= $check['status'] ? 'success' : 'error' ?>">
                                        <div class="check-icon"><i class="bi bi-<?= $check['status'] ? 'check' : 'x' ?>"></i></div>
                                        <div class="flex-grow-1">
                                            <strong><?= $check['name'] ?></strong>
                                            <span class="text-muted ms-1" style="font-size: 0.8rem;">(<?= $check['extension'] ?>)</span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="check-group">
                                <div class="check-group-title">Diretórios com Permissão de Escrita</div>
                                <?php foreach ($checksResults['directories'] as $dir => $check): ?>
                                    <div class="check-item <?= $check['status'] ? 'success' : 'error' ?>">
                                        <div class="check-icon"><i class="bi bi-<?= $check['status'] ? 'check' : 'x' ?>"></i></div>
                                        <div class="flex-grow-1">
                                            <strong><?= $check['path'] ?></strong>
                                            <span class="text-muted ms-2" style="font-size: 0.8rem;">
                                                <?= $check['exists'] ? ($check['writable'] ? 'OK' : 'Sem permissão') : 'Não existe' ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="installer-footer">
                                <div class="summary-bar">
                                    <span class="badge bg-success"><?= $summary['passed'] ?> OK</span>
                                    <span class="badge bg-danger"><?= $summary['failed'] ?> Falhou</span>
                                    <span class="badge bg-warning text-dark"><?= $summary['warnings'] ?> Avisos</span>
                                </div>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="check_requirements">
                                    <button type="submit" class="btn btn-installer btn-installer-primary" <?= !$summary['can_proceed'] ? 'disabled' : '' ?>>
                                        Continuar <i class="bi bi-arrow-right ms-1"></i>
                                    </button>
                                </form>
                            </div>
                            
                        <?php elseif ($step === 2): ?>
                            <!-- Step 2: Database -->
                            <h2 class="section-title"><i class="bi bi-database"></i> Configuração da Base de Dados</h2>
                            
                            <form method="POST">
                                <input type="hidden" name="action" value="configure_database">
                                
                                <div class="mb-3">
                                    <label class="form-label">Tipo de Base de Dados</label>
                                    <select name="db_driver" class="form-select" id="db_driver">
                                        <option value="mysql">MySQL / MariaDB</option>
                                        <option value="pgsql">PostgreSQL</option>
                                        <option value="sqlite">SQLite</option>
                                    </select>
                                </div>
                                
                                <div class="row" id="db_server_fields">
                                    <div class="col-md-8 mb-3">
                                        <label class="form-label">Servidor</label>
                                        <input type="text" name="db_host" class="form-control" value="localhost" placeholder="localhost" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Porta</label>
                                        <input type="text" name="db_port" class="form-control" value="3306" placeholder="3306" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Nome da Base de Dados</label>
                                    <input type="text" name="db_name" class="form-control" value="sigef" placeholder="sigef" required>
                                </div>
                                
                                <div class="row" id="db_auth_fields">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Utilizador</label>
                                        <input type="text" name="db_user" class="form-control" value="root" placeholder="root">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Palavra-passe</label>
                                        <input type="password" name="db_pass" class="form-control" placeholder="••••••••">
                                    </div>
                                </div>
                                
                                <div class="installer-footer">
                                    <a href="?step=1" class="btn btn-outline-secondary btn-installer">
                                        <i class="bi bi-arrow-left me-1"></i> Voltar
                                    </a>
                                    <button type="submit" class="btn btn-installer btn-installer-primary">
                                        Testar Conexão <i class="bi bi-arrow-right ms-1"></i>
                                    </button>
                                </div>
                            </form>
                            
                            <script>
                                document.getElementById('db_driver').addEventListener('change', function() {
                                    const serverFields = document.getElementById('db_server_fields');
                                    const authFields = document.getElementById('db_auth_fields');
                                    if (this.value === 'sqlite') {
                                        serverFields.style.display = 'none';
                                        authFields.style.display = 'none';
                                    } else {
                                        serverFields.style.display = 'flex';
                                        authFields.style.display = 'flex';
                                    }
                                });
                            </script>
                            
                        <?php elseif ($step === 3): ?>
                            <!-- Step 3: Installation -->
                            <h2 class="section-title"><i class="bi bi-download"></i> Instalação das Tabelas</h2>
                            
                            <div class="info-box-mini">
                                <i class="bi bi-info-circle text-primary me-2"></i>
                                A instalação irá criar todas as tabelas necessárias na base de dados.
                            </div>
                            
                            <div class="check-item success mb-3">
                                <div class="check-icon"><i class="bi bi-check"></i></div>
                                <div>Conexão à base de dados verificada</div>
                            </div>
                            
                            <div class="mb-4">
                                <strong>Ficheiros SQL a executar:</strong>
                                <ul class="mt-2 mb-0">
                                    <li><code>sigef_schema.sql</code> - Estrutura principal do SIGEF</li>
                                </ul>
                            </div>
                            
                            <form method="POST">
                                <input type="hidden" name="action" value="install_database">
                                <div class="installer-footer">
                                    <a href="?step=2" class="btn btn-outline-secondary btn-installer">
                                        <i class="bi bi-arrow-left me-1"></i> Voltar
                                    </a>
                                    <button type="submit" class="btn btn-installer btn-installer-primary">
                                        <i class="bi bi-play-fill me-1"></i> Instalar Agora
                                    </button>
                                </div>
                            </form>
                            
                        <?php elseif ($step === 4): ?>
                            <!-- Step 4: Admin -->
                            <h2 class="section-title"><i class="bi bi-person-badge"></i> Configurar Administrador</h2>
                            
                            <form method="POST">
                                <input type="hidden" name="action" value="configure_admin">
                                
                                <div class="mb-3">
                                    <label class="form-label">Nome Completo</label>
                                    <input type="text" name="admin_name" class="form-control" value="Administrador" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Login</label>
                                    <input type="text" name="admin_login" class="form-control" value="admin" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="admin_email" class="form-control" value="admin@sigef.ao" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Palavra-passe</label>
                                    <input type="password" name="admin_pass" class="form-control" placeholder="Mínimo 8 caracteres" required>
                                </div>
                                
                                <div class="installer-footer">
                                    <a href="?step=3" class="btn btn-outline-secondary btn-installer">
                                        <i class="bi bi-arrow-left me-1"></i> Voltar
                                    </a>
                                    <button type="submit" class="btn btn-installer btn-installer-primary">
                                        Finalizar <i class="bi bi-check-lg ms-1"></i>
                                    </button>
                                </div>
                            </form>
                            
                        <?php elseif ($step === 5): ?>
                            <!-- Step 5: Complete -->
                            <div class="success-container">
                                <div class="success-icon">
                                    <i class="bi bi-check-lg"></i>
                                </div>
                                <h2 class="section-title justify-content-center">Instalação Concluída!</h2>
                                <p class="text-muted mb-4">
                                    O SIGEF V1.2 PNA foi instalado com sucesso.<br>
                                    Pode agora aceder à aplicação.
                                </p>
                                
                                <div class="alert alert-warning alert-custom text-start mb-4">
                                    <i class="bi bi-shield-exclamation me-2"></i>
                                    <strong>Segurança:</strong> Elimine ou renomeie o ficheiro <code>install.php</code> após a instalação.
                                </div>
                                
                                <a href="index.php" class="btn btn-installer btn-installer-primary btn-lg">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>Aceder ao SIGEF
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Footer -->
                <div class="text-center text-white mt-3" style="opacity: 0.7; font-size: 0.85rem;">
                    SIGEF V1.2 PNA &bull; Powered by Adianti Framework
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
