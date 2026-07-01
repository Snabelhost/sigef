<?php
/**
 * Servidor de ficheiros de storage via PHP puro.
 * Serve ficheiros de storage/app/public sem depender do Laravel.
 * Chamado pelo .htaccess quando se acede a /storage/*
 */

// Extrair o caminho do ficheiro do URL
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$path = preg_replace('#^/storage/#', '', parse_url($requestUri, PHP_URL_PATH));

// Segurança: prevenir directory traversal
$path = str_replace(['..', "\0", '\\'], ['', '', '/'], $path);
$path = ltrim($path, '/');

if (empty($path)) {
    http_response_code(404);
    exit('Not found');
}

// Caminho real do ficheiro
$basePath = __DIR__ . '/../storage/app/public/';
$filePath = realpath($basePath . $path);

// Verificar que o ficheiro existe e está dentro do directório permitido
$realBase = realpath($basePath);
if (!$filePath || !$realBase || strpos($filePath, $realBase) !== 0 || !is_file($filePath)) {
    http_response_code(404);
    exit('Not found');
}

// MIME type
$mimeTypes = [
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'gif'  => 'image/gif',
    'webp' => 'image/webp',
    'svg'  => 'image/svg+xml',
    'pdf'  => 'application/pdf',
    'doc'  => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
];

$ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
$mimeType = $mimeTypes[$ext] ?? (function_exists('mime_content_type') ? mime_content_type($filePath) : 'application/octet-stream');

// Cache headers
$lastModified = filemtime($filePath);
$etag = '"' . md5($filePath . $lastModified) . '"';

// Verificar cache do browser
if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
    http_response_code(304);
    exit;
}

// Servir ficheiro
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: public, max-age=604800, immutable');
header('ETag: ' . $etag);
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastModified) . ' GMT');

readfile($filePath);
exit;
