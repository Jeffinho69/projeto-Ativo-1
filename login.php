<?php
// login.php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    $_SESSION['login_error'] = 'Preencha usuário e senha';
    header('Location: index.php');
    exit;
}

$mysqli = db_connect();
$stmt = $mysqli->prepare("SELECT id, username, password_hash, fullName, role FROM users WHERE username = ?");
$stmt->bind_param('s', $username);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();
$stmt->close();

if (!$user) {
    $_SESSION['login_error'] = 'Usuário não encontrado';
    header('Location: index.php');
    exit;
}

if (!password_verify($password, $user['password_hash'])) {
    $_SESSION['login_error'] = 'Senha incorreta';
    header('Location: index.php');
    exit;
}

// sucesso: grava sessão
$_SESSION['user'] = [
    'id' => $user['id'],
    'username' => $user['username'],
    'fullName' => $user['fullName'],
    'role' => $user['role'],
];

header('Location: painel.php');
exit;