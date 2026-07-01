<?php
/**
 * Script para criar o symlink de storage no servidor de produção.
 * 
 * INSTRUÇÕES DE USO:
 * 1. Faça upload deste ficheiro para a pasta public/ (ou public_html/) do servidor
 * 2. Acesse https://sigef.urbtech.shop/create-storage-link.php no navegador
 * 3. Após criar o symlink com sucesso, DELETE este ficheiro do servidor
 * 
 * Este script cria o symlink: public/storage -> storage/app/public
 * Isto é necessário para que as fotos e ficheiros sejam acessíveis via URL.
 */

// Detectar caminhos automaticamente
$publicPath = __DIR__;
$storagePath = realpath(__DIR__ . '/../storage/app/public');
$linkPath = $publicPath . DIRECTORY_SEPARATOR . 'storage';

echo "<h2>🔗 Criador de Symlink de Storage - SIGEF</h2>";
echo "<hr>";

// Verificar se o diretório de storage existe
if (!$storagePath) {
    echo "<p style='color:red;'>❌ ERRO: O diretório storage/app/public não foi encontrado.</p>";
    echo "<p>Caminho esperado: " . realpath(__DIR__ . '/..') . "/storage/app/public</p>";
    exit;
}

echo "<p>📁 Pasta public: <code>{$publicPath}</code></p>";
echo "<p>📁 Pasta storage: <code>{$storagePath}</code></p>";
echo "<p>🔗 Symlink: <code>{$linkPath}</code></p>";
echo "<hr>";

// Verificar se o symlink já existe
if (file_exists($linkPath)) {
    if (is_link($linkPath)) {
        $target = readlink($linkPath);
        echo "<p style='color:green;'>✅ O symlink já existe e aponta para: <code>{$target}</code></p>";
        
        // Verificar se aponta para o local correto
        if (realpath($linkPath) === $storagePath) {
            echo "<p style='color:green;'>✅ O symlink está correctamente configurado!</p>";
        } else {
            echo "<p style='color:orange;'>⚠️ O symlink aponta para um local diferente do esperado.</p>";
            echo "<p>Esperado: <code>{$storagePath}</code></p>";
            echo "<p>Actual: <code>" . realpath($linkPath) . "</code></p>";
        }
    } else {
        echo "<p style='color:orange;'>⚠️ Existe um ficheiro/pasta 'storage' em public/ mas NÃO é um symlink.</p>";
        echo "<p>Pode ser necessário remover manualmente antes de criar o symlink.</p>";
    }
} else {
    echo "<p>ℹ️ O symlink não existe. A tentar criar...</p>";
    
    // Tentar criar o symlink
    if (symlink($storagePath, $linkPath)) {
        echo "<p style='color:green;'>✅ Symlink criado com SUCESSO!</p>";
        echo "<p style='color:green;'>public/storage → {$storagePath}</p>";
    } else {
        echo "<p style='color:red;'>❌ Não foi possível criar o symlink automaticamente.</p>";
        echo "<p>Isto pode acontecer em hosting compartilhado que não permite symlinks.</p>";
        echo "<p><strong>Alternativa:</strong> A rota de fallback em routes/web.php já cuida de servir os ficheiros.</p>";
        
        // Tentar abordagem alternativa - copiar .htaccess redirect
        echo "<hr>";
        echo "<h3>Solução Alternativa: .htaccess Redirect</h3>";
        echo "<p>Pode adicionar ao .htaccess da pasta public/:</p>";
        echo "<pre>RewriteRule ^storage/(.*)$ ../storage/app/public/$1 [L]</pre>";
    }
}

echo "<hr>";
echo "<p style='color:red;'><strong>⚠️ IMPORTANTE: Apague este ficheiro do servidor após usar!</strong></p>";
echo "<p style='font-size:12px;'>Gerado em: " . date('Y-m-d H:i:s') . "</p>";
