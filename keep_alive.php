<?php
// keep_alive.php
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $mysqli = db_connect();

    // Faz uma consulta simples pra "acordar" o banco
    $ping = $mysqli->query("SELECT NOW() as hora");

    if ($ping && $row = $ping->fetch_assoc()) {
        echo json_encode(['ok' => true, 'hora' => $row['hora']]);
    } else {
        echo json_encode(['ok' => false, 'msg' => 'Ping falhou']);
    }

    $mysqli->close();
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}