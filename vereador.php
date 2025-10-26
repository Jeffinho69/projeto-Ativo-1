<?php
// vereador.php
require_once 'config.php';
if (empty($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}
$user = $_SESSION['user'];
if ($user['role'] !== 'vereador' && $user['role'] !== 'admin') {
    echo "Acesso negado.";
    exit;
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Painel - Vereador</title>
  <link rel="stylesheet" href="style.css">

  <style>
    /* ======== ESTILO DO BOTÃO NEGAR ======== */
    .btn-deny {
      background: linear-gradient(45deg, #ff4b4b, #b31217);
      color: #fff;
      border: none;
      border-radius: 8px;
      padding: 6px 12px;
      cursor: pointer;
      font-size: 14px;
      transition: 0.3s;
    }

    .btn-deny:hover {
      background: linear-gradient(45deg, #b31217, #ff4b4b);
      transform: scale(1.05);
    }

    /* Espaçamento entre botões */
    .table .btn {
      margin-right: 6px;
    }
  </style>

  <script defer src="script.js"></script>
</head>

<body class="panel-page">
  <header class="topbar light">
    <div class="brand">
      <img src="https://tse3.mm.bing.net/th/id/OIP.NeJbj2QckKlAAfZ0YlkgUgHaJw?cb=12&pid=Api" alt="Brasão" class="brasao">
      <div>
        <div class="title">Painel do Vereador</div>
        <div class="subtitle">Câmara Municipal</div>
      </div>
    </div>
    <div class="top-actions">
      <div id="clock" class="clock">--:--:--</div>
      <div id="userDisplay" class="user-display"><?php echo htmlspecialchars($user['fullName']); ?></div>
      <button id="logoutBtn" class="btn ghost" onclick="location.href='logout.php'">Sair</button>
    </div>
  </header>

  <main class="main-grid">
    <section class="content light">
      <h3>Notificações</h3>
      <div class="note muted">Aqui aparecem visitantes atribuídos a você (aceitar ou negar e registrar saída).</div>

      <h4>Visitantes pendentes para você</h4>
      <table class="table" id="vereadorPending">
        <thead>
          <tr><th>Nome</th><th>Documento</th><th>Entrada</th><th>Ações</th></tr>
        </thead>
        <tbody>
          <?php
          // Exemplo de visitante (substituir depois pelo loop real)
          echo '<tr>
            <td>João Silva</td>
            <td>123.456.789-00</td>
            <td>10:20</td>
            <td>
              <button class="btn primary" onclick="aceitarSolicitacao(1)">Aceitar</button>
              <button class="btn-deny" onclick="negarSolicitacao(1)">Negar</button>
            </td>
          </tr>';
          ?>
        </tbody>
      </table>

      <h4 style="margin-top:14px">Seus presentes</h4>
      <table class="table" id="vereadorPresent">
        <thead>
          <tr><th>Nome</th><th>Documento</th><th>Entrada</th><th>Saída</th><th>Ações</th></tr>
        </thead>
        <tbody></tbody>
      </table>
    </section>
  </main>

  <div id="toast-container" class="toast-container"></div>

  <script>
  function aceitarSolicitacao(id) {
    if (confirm("Deseja aceitar este visitante?")) {
      fetch('api.php?action=approve', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `id=${encodeURIComponent(id)}`
      })
      .then(r => r.json())
      .then(j => {
        if (j.ok) {
          mostrarToast("Visitante aceito com sucesso!", "success");
          atualizarListasVereador();
        } else {
          mostrarToast(j.msg || "Erro ao aceitar visitante", "error");
        }
      })
      .catch(() => mostrarToast("Erro de conexão com o servidor.", "error"));
    }
  }

  function negarSolicitacao(id) {
    if (confirm("Tem certeza que deseja negar essa solicitação?")) {
      fetch('api.php?action=deny', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `id=${encodeURIComponent(id)}`
      })
      .then(r => r.json())
      .then(j => {
        if (j.ok) {
          mostrarToast("Visita negada com sucesso!", "success");
          atualizarListasVereador();
        } else {
          mostrarToast(j.msg || "Erro ao negar visita", "error");
        }
      })
      .catch(() => mostrarToast("Erro de conexão com o servidor.", "error"));
    }
  }

  function mostrarToast(msg, type) {
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    toast.className = 'toast ' + type;
    toast.textContent = msg;
    container.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
  }

  function atualizarListasVereador() {
    fetch('api.php?action=my_pending_for_vereador')
      .then(r => r.json())
      .then(j => {
        if (j.ok) {
          const tbody = document.querySelector('#vereadorPending tbody');
          tbody.innerHTML = '';
          j.data.forEach(v => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
              <td>${v.name}</td>
              <td>${v.doc || ''}</td>
              <td>${v.added_at}</td>
              <td>
                <button class="btn primary" onclick="aceitarSolicitacao(${v.id})">Aceitar</button>
                <button class="btn-deny" onclick="negarSolicitacao(${v.id})">Negar</button>
              </td>`;
            tbody.appendChild(tr);
          });
        }
      });

    fetch('api.php?action=my_present_for_vereador')
      .then(r => r.json())
      .then(j => {
        if (j.ok) {
          const tbody = document.querySelector('#vereadorPresent tbody');
          tbody.innerHTML = '';
          j.data.forEach(v => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
              <td>${v.name}</td>
              <td>${v.doc || ''}</td>
              <td>${v.entered_at}</td>
              <td>${v.left_at || '-'}</td>
              <td><button class="btn ghost" onclick="registerExit(${v.id})">Registrar Saída</button></td>`;
            tbody.appendChild(tr);
          });
        }
      });
  }

  // Atualiza listas a cada 5 segundos
  setInterval(atualizarListasVereador, 50000);
</script>

</body>
</html>
