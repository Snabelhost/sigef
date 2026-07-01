<?php
/**
 * DIAGNÓSTICO DE STORAGE - SIGEF
 * 
 * Faça upload para public/ no servidor e aceda via browser:
 * https://sigef.urbtech.shop/diagnose-storage.php
 * 
 * APAGUE APÓS USAR!
 */

echo "<h2>🔍 Diagnóstico de Storage - SIGEF</h2>";
echo "<style>body{font-family:Arial;max-width:900px;margin:20px auto;} 
.ok{color:green;font-weight:bold;} .err{color:red;font-weight:bold;} .warn{color:orange;font-weight:bold;}
pre{background:#f5f5f5;padding:10px;overflow-x:auto;} table{border-collapse:collapse;width:100%;} 
td,th{border:1px solid #ddd;padding:8px;text-align:left;}</style>";

// 1. Caminhos
$publicDir = __DIR__;
$baseDir = realpath(__DIR__ . '/..');
$storagePath = $baseDir . '/storage/app/public';
$linkPath = $publicDir . '/storage';
$photosPath = $storagePath . '/candidates/photos';

echo "<h3>📁 1. Caminhos do Sistema</h3>";
echo "<table>";
echo "<tr><td>Pasta public/</td><td><code>{$publicDir}</code></td></tr>";
echo "<tr><td>Pasta base do Laravel</td><td><code>{$baseDir}</code></td></tr>";
echo "<tr><td>Storage real</td><td><code>{$storagePath}</code></td></tr>";
echo "<tr><td>Symlink esperado</td><td><code>{$linkPath}</code></td></tr>";
echo "<tr><td>Pasta de fotos</td><td><code>{$photosPath}</code></td></tr>";
echo "</table>";

// 2. Verificar storage real
echo "<h3>📂 2. Storage Real (storage/app/public)</h3>";
if (is_dir($storagePath)) {
    echo "<p class='ok'>✅ Existe</p>";
    $dirs = glob($storagePath . '/*', GLOB_ONLYDIR);
    echo "<p>Subpastas: " . implode(', ', array_map('basename', $dirs)) . "</p>";
} else {
    echo "<p class='err'>❌ NÃO EXISTE em: {$storagePath}</p>";
}

// 3. Verificar fotos
echo "<h3>📸 3. Fotos dos Candidatos</h3>";
if (is_dir($photosPath)) {
    $photos = glob($photosPath . '/*');
    $count = count($photos);
    echo "<p class='ok'>✅ Pasta existe com {$count} ficheiro(s)</p>";
    if ($count > 0) {
        echo "<p>Primeiros 5 ficheiros:</p><ul>";
        foreach (array_slice($photos, 0, 5) as $photo) {
            $size = filesize($photo);
            $readable = is_readable($photo) ? '✅ legível' : '❌ sem permissão';
            $perms = substr(sprintf('%o', fileperms($photo)), -4);
            echo "<li><code>" . basename($photo) . "</code> ({$size} bytes, perms: {$perms}, {$readable})</li>";
        }
        echo "</ul>";
    }
} else {
    echo "<p class='err'>❌ Pasta de fotos NÃO EXISTE em: {$photosPath}</p>";
}

// 4. Verificar symlink
echo "<h3>🔗 4. Symlink (public/storage)</h3>";
if (file_exists($linkPath) || is_link($linkPath)) {
    if (is_link($linkPath)) {
        $target = readlink($linkPath);
        $realTarget = realpath($linkPath);
        echo "<p class='ok'>✅ É um SYMLINK</p>";
        echo "<p>Aponta para: <code>{$target}</code></p>";
        if ($realTarget) {
            echo "<p>Caminho real: <code>{$realTarget}</code></p>";
            if ($realTarget === realpath($storagePath)) {
                echo "<p class='ok'>✅ Aponta para o local CORRECTO</p>";
            } else {
                echo "<p class='err'>❌ Aponta para local ERRADO!</p>";
                echo "<p>Esperado: <code>" . realpath($storagePath) . "</code></p>";
            }
        } else {
            echo "<p class='err'>❌ Symlink QUEBRADO - o destino não existe!</p>";
        }
    } else if (is_dir($linkPath)) {
        echo "<p class='warn'>⚠️ É um DIRECTÓRIO REAL, não um symlink!</p>";
        echo "<p>Isto é provavelmente a causa do problema. Foi criado pelo deploy em vez de ser um symlink.</p>";
        $linkContents = glob($linkPath . '/*');
        echo "<p>Conteúdo: " . (empty($linkContents) ? 'VAZIO' : implode(', ', array_map('basename', $linkContents))) . "</p>";
        
        // Verificar se tem fotos dentro
        $linkPhotos = $linkPath . '/candidates/photos';
        if (is_dir($linkPhotos)) {
            $lp = glob($linkPhotos . '/*');
            echo "<p>Fotos em public/storage/candidates/photos/: " . count($lp) . " ficheiro(s)</p>";
        } else {
            echo "<p class='err'>❌ public/storage/candidates/photos/ NÃO EXISTE</p>";
        }
    } else {
        echo "<p class='warn'>⚠️ Existe mas não é symlink nem directório</p>";
    }
} else {
    echo "<p class='err'>❌ NÃO EXISTE</p>";
}

// 5. Verificar .htaccess
echo "<h3>📄 5. Ficheiro .htaccess</h3>";
$htaccess = file_get_contents($publicDir . '/.htaccess');
if (strpos($htaccess, 'RewriteRule ^storage/') !== false) {
    echo "<p class='ok'>✅ Regra de rewrite para storage está presente</p>";
} else {
    echo "<p class='err'>❌ Regra de rewrite para storage NÃO encontrada no .htaccess</p>";
}

// 6. Verificar config do filesystem
echo "<h3>⚙️ 6. Configuração do Filesystem</h3>";
$configFile = $baseDir . '/config/filesystems.php';
if (file_exists($configFile)) {
    $configContent = file_get_contents($configFile);
    if (strpos($configContent, "'serve' => true") !== false || strpos($configContent, '"serve" => true') !== false) {
        echo "<p class='ok'>✅ 'serve' => true está configurado</p>";
    } else {
        echo "<p class='err'>❌ 'serve' => true NÃO encontrado em filesystems.php</p>";
    }
} else {
    echo "<p class='err'>❌ Ficheiro config/filesystems.php não encontrado</p>";
}

// 7. Testar URL de uma foto
echo "<h3>🌐 7. Teste de URL</h3>";
if (is_dir($photosPath)) {
    $testPhotos = glob($photosPath . '/*');
    if (!empty($testPhotos)) {
        $testPhoto = 'candidates/photos/' . basename($testPhotos[0]);
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $testUrl = "{$protocol}://{$host}/storage/{$testPhoto}";
        echo "<p>URL de teste: <a href='{$testUrl}' target='_blank'>{$testUrl}</a></p>";
        echo "<p>Clique no link acima para testar se a foto abre.</p>";
        
        // Tentar servir directamente
        echo "<h4>Imagem de teste directo (via PHP):</h4>";
        $fullTestPath = $photosPath . '/' . basename($testPhotos[0]);
        if (is_readable($fullTestPath)) {
            $mime = mime_content_type($fullTestPath);
            $base64 = base64_encode(file_get_contents($fullTestPath));
            echo "<img src='data:{$mime};base64,{$base64}' style='max-width:150px;max-height:150px;border:2px solid green;'>";
            echo "<p class='ok'>✅ PHP consegue ler a foto - o ficheiro existe e é acessível</p>";
        } else {
            echo "<p class='err'>❌ PHP NÃO consegue ler a foto</p>";
        }
    }
}

// 8. Solução recomendada
echo "<h3>🛠️ 8. Solução Automática</h3>";
if (isset($_GET['fix']) && $_GET['fix'] === 'symlink') {
    // Remover o que existe e recriar como symlink
    if (is_dir($linkPath) && !is_link($linkPath)) {
        // É um directório real - tentar remover
        $removed = false;
        $contents = glob($linkPath . '/{,.}*', GLOB_BRACE);
        $contents = array_filter($contents, fn($f) => !in_array(basename($f), ['.', '..']));
        if (empty($contents) || count($contents) <= 1) { // Vazio ou só .gitignore
            // Remover recursivamente
            function removeDir($dir) {
                $items = glob($dir . '/{,.}*', GLOB_BRACE);
                foreach ($items as $item) {
                    if (in_array(basename($item), ['.', '..'])) continue;
                    is_dir($item) ? removeDir($item) : unlink($item);
                }
                return rmdir($dir);
            }
            $removed = removeDir($linkPath);
        }
        
        if ($removed) {
            echo "<p class='ok'>✅ Directório antigo removido</p>";
        } else {
            echo "<p class='err'>❌ Não foi possível remover o directório. Remova manualmente via File Manager do cPanel.</p>";
        }
    }
    
    if (is_link($linkPath)) {
        unlink($linkPath);
        echo "<p>Symlink antigo removido</p>";
    }
    
    if (!file_exists($linkPath) && !is_link($linkPath)) {
        if (@symlink($storagePath, $linkPath)) {
            echo "<p class='ok'>✅ Symlink CRIADO com sucesso! As fotos devem funcionar agora.</p>";
        } else {
            echo "<p class='err'>❌ Symlinks não são permitidos neste servidor.</p>";
            echo "<p>A solução via rota Laravel (serve => true + .htaccess) deve funcionar. Verifique se o deploy incluiu os ficheiros actualizados.</p>";
        }
    }
} else {
    echo "<p><a href='?fix=symlink' style='background:#10b981;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;font-weight:bold;'>🔧 Corrigir Symlink Automaticamente</a></p>";
    echo "<p style='font-size:12px;'>Clique para remover o directório/symlink existente e recriar correctamente.</p>";
}

echo "<hr><p style='color:red;font-weight:bold;'>⚠️ APAGUE este ficheiro do servidor após usar!</p>";
echo "<p style='font-size:12px;'>Gerado em: " . date('Y-m-d H:i:s') . "</p>";
