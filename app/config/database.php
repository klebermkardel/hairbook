<?php
// app/config/database.php

// Carrega as variáveis de ambiente do arquivo .env
// Esta é uma implementação SIMPLIFICADA e manual.
// Para um projeto real, uma biblioteca como vlucas/phpdotenv é recomendada.

$dotenv_path = __DIR__ . '/../../.env'; // Caminho para o arquivo .env na raiz do projeto

if (file_exists($dotenv_path)) {
    $lines = file($dotenv_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue; // Ignora comentários
        list($name, $value) = explode('=', $line, 2);
        $_ENV[$name] = $value; // Carrega para a superglobal $_ENV
        $_SERVER[$name] = $value; // Carrega para a superglobal $_SERVER
    }
}

// Define as constantes de conexão usando as variáveis de ambiente carregadas
define('DB_SERVER', $_ENV['DB_SERVER'] ?? 'localhost'); // Fallback se não encontrar
define('DB_USERNAME', $_ENV['DB_USERNAME'] ?? 'root');
define('DB_PASSWORD', $_ENV['DB_PASSWORD'] ?? '');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'hairbook');
define('DB_CHARSET', $_ENV['DB_CHARSET'] ?? 'utf8mb4');