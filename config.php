<?php
// config.php
define('DB_HOST', 'localhost');
define('DB_USER', 'fhiktajx_adminuser');
define('DB_PASS', 'AnalistaTI.2025');
define('DB_NAME', 'fhiktajx_painel_recepcao'); // << banco correto
define('DB_CHAR', 'utf8mb4');

function db_connect(){
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($mysqli->connect_errno) {
        http_response_code(500);
        die("Erro ao conectar ao banco: " . $mysqli->connect_error);
    }
    $mysqli->set_charset(DB_CHAR);
    return $mysqli;
}
session_start();


// =========================================================
// ================ ADICIONADO: AUTO-LIMPEZA ===============
// =========================================================

// Para não rodar a limpeza em TODA requisição (o que seria lento),
// vamos rodar com 1% de chance (1 a cada 100 visitas na página).
// Isso é o suficiente para manter o banco limpo.

if (rand(1, 100) === 1) { 
    try {
        $conn_clean = db_connect(); // Abre uma nova conexão só para isso
        
        // Apaga DE VERDADE (hard delete) mensagens com mais de 30 dias
        // Tabela 'messages'
        $sql_clean = "DELETE FROM messages WHERE sent_at < NOW() - INTERVAL 30 DAY";
        
        $conn_clean->query($sql_clean);
        $conn_clean->close();
    } catch (Exception $e) {
        // Ignora o erro para não quebrar a página
    }
}
// =========================================================
// ================ FIM DA AUTO-LIMPEZA ====================
// =========================================================
?>