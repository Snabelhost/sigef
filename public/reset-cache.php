<?php
/**
 * Reset OPcache - Aceda via http://sigefv2.test/reset-cache.php
 */

// Reset OPcache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "OPcache resetado com sucesso!<br>";
} else {
    echo "OPcache não está disponível<br>";
}

// Limpar ficheiros de cache do Laravel manualmente
$viewsPath = __DIR__ . '/../storage/framework/views';
$cachePath = __DIR__ . '/../storage/framework/cache/data';

// Limpar views cache
if (is_dir($viewsPath)) {
    $files = glob($viewsPath . '/*');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    echo "Views cache limpo!<br>";
}

// Limpar bootstrap cache
$bootstrapFiles = glob(__DIR__ . '/../bootstrap/cache/*.php');
foreach ($bootstrapFiles as $file) {
    if (is_file($file) && basename($file) !== '.gitignore') {
        unlink($file);
    }
}
echo "Bootstrap cache limpo!<br>";

echo "<br><strong>Cache limpo! Recarregue a página do painel.</strong>";
echo "<br><br><a href='/admin'>← Voltar ao Admin</a>";
