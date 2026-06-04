<?php
/**
 * Script de Otimização - Execute no terminal do Laragon
 * 
 * Comandos para executar:
 * 1. Abra o terminal do Laragon (clique direito no ícone > Terminal)
 * 2. Navegue até a pasta: cd c:\laragon\www\sigefv2
 * 3. Execute: php artisan optimize:clear
 * 4. Execute: php artisan optimize
 * 5. Execute: php artisan migrate (para adicionar os índices)
 * 
 * Ou abra o browser em: http://sigefv2.test/optimize.php
 */

// Detectar ambiente
$isWeb = php_sapi_name() !== 'cli';

if ($isWeb) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<html><head><title>Otimização SIGEF</title>';
    echo '<style>body{font-family:Arial;padding:20px;background:#1a1a2e;color:#eee;}';
    echo '.success{color:#4ade80;}.error{color:#f87171;}.info{color:#60a5fa;}';
    echo 'pre{background:#16213e;padding:15px;border-radius:8px;overflow-x:auto;}';
    echo 'h1{color:#818cf8;}</style></head><body>';
    echo '<h1> Otimização SIGEF v2</h1>';
}

function output($msg, $class = 'info') {
    global $isWeb;
    if ($isWeb) {
        echo "<p class='{$class}'>{$msg}</p>";
        ob_flush();
        flush();
    } else {
        echo $msg . "\n";
    }
}

// Mudar para diretório do Laravel
chdir(__DIR__);

output('Diretório: ' . getcwd());

// Limpar caches
output(' Limpando caches...', 'info');

$commands = [
    'php artisan config:clear' => 'Limpar config cache',
    'php artisan cache:clear' => 'Limpar application cache',
    'php artisan view:clear' => 'Limpar view cache',
    'php artisan route:clear' => 'Limpar route cache',
    'php artisan event:clear' => 'Limpar event cache',
];

foreach ($commands as $cmd => $desc) {
    exec($cmd . ' 2>&1', $cmdOutput, $returnCode);
    if ($returnCode === 0) {
        output(" {$desc}", 'success');
    } else {
        output("{$desc}: " . implode(' ', $cmdOutput), 'error');
    }
    $cmdOutput = [];
}

// Otimizar
output('⚡ Otimizando aplicação...', 'info');

$optimizeCommands = [
    'php artisan config:cache' => 'Cache de configuração',
    'php artisan route:cache' => 'Cache de rotas',
    'php artisan view:cache' => 'Cache de views',
    'php artisan icons:cache' => 'Cache de ícones',
];

foreach ($optimizeCommands as $cmd => $desc) {
    exec($cmd . ' 2>&1', $cmdOutput, $returnCode);
    if ($returnCode === 0) {
        output("{$desc}", 'success');
    } else {
        output("{$desc} (pode ser ignorado)", 'info');
    }
    $cmdOutput = [];
}

// Verificar opcache
output('🔧 Verificando OPcache...', 'info');
if (function_exists('opcache_get_status')) {
    $status = opcache_get_status();
    if ($status && $status['opcache_enabled']) {
        output(' OPcache está ativo!', 'success');
        $stats = $status['memory_usage'];
        $hitRate = round($status['opcache_statistics']['opcache_hit_rate'] ?? 0, 2);
        output("Hit Rate: {$hitRate}%", 'info');
    } else {
        output('OPcache não está ativo. Considere ativar em php.ini', 'error');
    }
} else {
    output(' OPcache não disponível', 'error');
}

output('', 'info');
output(' Otimização concluída!', 'success');
output('', 'info');
output('Dicas adicionais:', 'info');
output('1. Execute "php artisan migrate" para adicionar índices de performance', 'info');
output('2. No Laragon, reinicie Apache/Nginx após estas mudanças', 'info');
output('3. Considere usar MySQL 8.0+ para melhor performance', 'info');

if ($isWeb) {
    echo '<br><a href="/admin" style="color:#818cf8;text-decoration:none;padding:10px 20px;background:#16213e;border-radius:8px;">← Voltar ao Admin</a>';
    echo '</body></html>';
}
