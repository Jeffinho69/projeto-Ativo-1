<?php
// index.php
// Se já logado, redireciona pro painel
require_once 'config.php';
if (!empty($_SESSION['user'])) {
    header('Location: painel.php');
    exit;
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Login - Painel da Recepção</title>
  <link rel="stylesheet" href="style.css">
  <script defer src="script.js"></script>
</head>
<body class="login-page">
  <header class="top-header">
    <img src="https://tse3.mm.bing.net/th/id/OIP.NeJbj2QckKlAAfZ0YlkgUgHaJw?cb=12&pid=Api" alt="Brasão" class="brasao">
    <div class="header-title">Painel da Recepção – Câmara Municipal de Vitória de Santo Antão</div>
  </header>

  <main class="login-main">
    <div class="login-card">
      <div class="illustration">
        <div class="chars" id="chars">
          <div class="char char1"></div>
          <div class="char char2"></div>
          <div class="char char3"></div>
          <div class="char char4"></div>
        </div>
      </div>

      <div class="login-box">
        <h2>Bem-vindo</h2>
        <p class="muted">Faça login para acessar o painel de recepção</p>

        <form method="post" action="login.php" id="loginForm">
          <label>Usuário</label>
          <input id="username" name="username" type="text" placeholder="Usuário" autocomplete="off" required>

          <label>Senha</label>
          <input id="password" name="password" type="password" placeholder="Senha" required>

          <div class="hint muted"><strong></strong> <strong></strong></div>

          <button id="loginBtn" class="btn primary" type="submit">Entrar</button>
        </form>

        <p id="loginError" class="error"></p>
      </div>
    </div>
  </main>

  <div id="toast-container" class="toast-container"></div>
</body>
</html>