<?php
// app/api/api.php

// Define o cabeçalho para indicar que a resposta será em formato JSON
header('Content-Type: application/json');

// Inclui o arquivo de configuração do banco de dados
// O caminho deve ser relativo à localização deste arquivo (api.php)
require_once __DIR__ . '/../config/database.php';

// Conexão com o Banco de Dados MySQL usando a extensão MySQLi
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Verifica se houve algum erro na conexão com o banco de dados
if ($conn->connect_error) {
    // Em caso de erro, retorna uma resposta JSON com falha e a mensagem de erro
    echo json_encode(['success' => false, 'message' => 'Falha na conexão com o banco de dados: ' . $conn->connect_error]);
    // Encerra a execução do script
    exit();
}

// Opcional: Define o charset da conexão para evitar problemas de codificação de caracteres
$conn->set_charset(DB_CHARSET);

// Função para sanitizar e validar entradas de usuário
// Essencial para segurança, prevenindo ataques como Cross-Site Scripting (XSS)
function sanitize_input($data) {
    // Remove espaços em branco do início e fim da string
    $data = trim($data);
    // Remove barras invertidas adicionadas por magic quotes (se ativado, embora raro hoje)
    $data = stripslashes($data);
    // Converte caracteres especiais em entidades HTML para evitar XSS
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Obtém a ação solicitada pela requisição GET (ex: ?action=get_services)
// Se 'action' não estiver definido, assume uma string vazia
$action = isset($_GET['action']) ? sanitize_input($_GET['action']) : '';

// A estrutura 'switch' será usada para rotear as requisições para as funções corretas
switch ($action) {
    // O próximo passo vai adicionar os 'case' para 'get_services', 'get_available_times' e 'make_appointment'
    default:
        // Se a ação solicitada não for reconhecida, retorna um erro
        echo json_encode(['success' => false, 'message' => 'Ação inválida ou não especificada.']);
        break;
}

// Fecha a conexão com o banco de dados ao final da execução do script
$conn->close();