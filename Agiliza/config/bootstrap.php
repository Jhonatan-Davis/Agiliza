<?php

define('APP_ROOT', dirname(__DIR__));
// Calcula a URL a partir da pasta publicada pelo Apache, evitando caminhos fixos.
$document_root = realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '';
$app_root_real = realpath(APP_ROOT) ?: APP_ROOT;
$base_url = '';

if ($document_root !== '' && str_starts_with($app_root_real, $document_root)) {
	$base_url = str_replace('\\', '/', substr($app_root_real, strlen($document_root)));
}

define('BASE_URL', rtrim('/' . trim($base_url, '/'), '/'));
define('PUBLIC_URL', BASE_URL . '/public');

function public_asset_url(string $path): string
{
	if ($path === '') {
		return PUBLIC_URL . '/uploads/default_perfil.png';
	}

	return str_starts_with($path, '/') ? $path : PUBLIC_URL . '/' . ltrim($path, '/');
}

require_once APP_ROOT . '/config/conexao.php';