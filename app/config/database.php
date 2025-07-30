<?php
// app/config/database.php

// Define as constantes de conexão com o banco de dados
// Mude 'localhost' para o IP do seu servidor de banco de dados, se for diferente
define('DB_SERVER', 'localhost');
// Mude 'root' para o nome de usuário do seu banco de dados MySQL
define('DB_USERNAME', 'root');
// Mude '' (string vazia) para a senha do seu usuário do banco de dados MySQL
define('DB_PASSWORD', '');
// Mude 'hairbook' para o nome do seu banco de dados
define('DB_NAME', 'hairbook');

// Opcional: Configura o charset para a conexão com o banco de dados
// É importante para evitar problemas com caracteres especiais (acentuação, cedilha)
define('DB_CHARSET', 'utf8mb4');

// Outras configurações ou constantes podem ser adicionadas aqui no futuro