<?php

require_once 'config.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['username'] ?? '');
    $p = $_POST['password'] ?? '';
    $full = trim($_POST['fullName'] ?? $u);
    $role = $_POST['role'] ?? 'recep';
    if (!$u || !$p) die('username/password required');
    $hash = password_hash($p, PASSWORD_DEFAULT);
    $db = db_connect();
    $stmt = $db->prepare("INSERT INTO users (username, password_hash, fullName, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('ssss', $u, $hash, $full, $role);
    $ok = $stmt->execute();
    echo $ok ? "Usuário criado" : "Erro: " . $stmt->error;
    exit;
}
?>
<form method="post">
  <input name="username" placeholder="username"><br>
  <input name="password" placeholder="password"><br>
  <input name="fullName" placeholder="Full name"><br>
  <select name="role"><option value="recep">recepção</option><option value="vereador">vereador</option><option value="admin">admin</option></select><br>
  <button type="submit">Criar</button>

</form>
