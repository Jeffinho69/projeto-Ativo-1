<?php
// config.php
define('DB_HOST', '');
define('DB_USER', '');
define('DB_PASS', '');
define('DB_NAME', ''); 
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


if (rand(1, 100) === 1) { 
    try {
        $conn_clean = db_connect(); // Abre uma nova conexão só para isso
        
        
        $sql_clean = "DELETE FROM messages WHERE sent_at < NOW() - INTERVAL 30 DAY";
        
        $conn_clean->query($sql_clean);
        $conn_clean->close();
    } catch (Exception $e) {
        // Ignora o erro para não quebrar a página
    }
}

?>
