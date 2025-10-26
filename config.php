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
?>
