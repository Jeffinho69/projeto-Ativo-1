<?php
// painel.php
require_once 'config.php';
if (empty($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}
$role = $_SESSION['user']['role'] ?? 'recep';
if ($role === 'recep' || $role === 'admin') {
    header('Location: recepcao.php');
    exit;
} elseif ($role === 'vereador') {
    header('Location: vereador.php');
    exit;
} else {
    echo "Função desconhecida.";
    exit;
}