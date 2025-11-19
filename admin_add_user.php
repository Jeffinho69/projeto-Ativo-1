<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['fullName'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    if ($fullName === '' || $username === '' || $password === '' || $role === '') {
        die("Preencha todos os campos obrigatórios.");
    }

    $mysqli = db_connect();

    // Verifica se já existe usuário com esse nome
    $check = $mysqli->prepare("SELECT id FROM users WHERE username = ?");
    $check->bind_param("s", $username);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        die("Usuário já existe.");
    }
    $check->close();

    // Cria hash da senha
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Insere novo usuário
    $stmt = $mysqli->prepare("INSERT INTO users (username, password_hash, fullName, role, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssss", $username, $password_hash, $fullName, $role);
    if ($stmt->execute()) {
        echo "<script>alert('Usuário cadastrado com sucesso!'); window.location='usuarios.php';</script>";
    } else {
        echo "Erro ao cadastrar usuário: " . $mysqli->error;
    }
    $stmt->close();
    $mysqli->close();
}
?>
