<?php
// vereador.php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
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
  <!-- Pequeno CSS local para ajustar a área de notificações (pode também ir ao style.css) -->
  <style>

    /* Painel centralizado e card leve para notificações */
    .vereador-container {
      max-width: 900px;
      margin: 18px auto;
      padding: 18px;
    }
    .card-notif {
      background: var(--card);
      border-radius: 12px;
      padding: 16px;
      box-shadow: var(--shadow);
      margin-bottom: 16px;
    }
    .notif-row {
      display: grid;
      grid-template-columns: 1fr 220px 160px 180px; /* nome / motivo / entrada / ações */
      gap: 12px;
      align-items: center;
      padding: 12px;
      border-radius: 10px;
      border: 1px solid #eef3f9;
      background: linear-gradient(90deg, #fff, #fbfdff);
      margin-bottom: 10px;
    }
    .notif-row h4 { margin: 0; font-size: 16px; }
    .notif-meta { color: var(--muted); font-size: 13px; }
    .actions { display:flex; gap:8px; justify-content:flex-end; }
    .btn-accept { background: linear-gradient(90deg,var(--accent),var(--accent-700)); color:#fff; border:0; padding:8px 12px; border-radius:8px; cursor:pointer; font-weight:700; }
    .btn-deny { background: linear-gradient(45deg,#ff6b6b,#c92b2b); color:#fff; border:0; padding:8px 12px; border-radius:8px; cursor:pointer; font-weight:700; }
    .small-muted { font-size:13px; color:var(--muted); }
    /* responsivo */
    @media (max-width:900px){
      .notif-row { grid-template-columns: 1fr; gap:8px; }
      .actions { justify-content:flex-start; }
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
      <button class="btn ghost" onclick="location.href='chat.php'">💬 Chat</button>
<span id="chatNotif" class="chat-notif hidden">●</span>

      <button id="logoutBtn" class="btn ghost" onclick="location.href='logout.php'">Sair</button>
    </div>
  </header>

  <main class="main-grid">
    <section class="content light vereador-container">
      <div class="card-notif">
        <h3>Notificações</h3>
        <div class="note muted">Aqui aparecem visitantes atribuídos a você. Você pode aceitar (autorizar entrada) ou negar.</div>
      </div>

      <div class="card-notif" id="pendingArea">
        <h4>Visitantes pendentes para você</h4>
        <div id="vereadorPendingList" style="margin-top:12px;"></div>
      </div>

      <div class="card-notif" id="presentArea">
        <h4>Seus presentes</h4>
        <div id="vereadorPresentList" style="margin-top:12px;"></div>
      </div>

    </section>
  </main>

  <div id="toast-container" class="toast-container"></div>
</body>

<script>
function updateTopBadge(){
  fetch('chat_api.php?action=poll_unread').then(r=>r.json()).then(j=>{
    if (!j.ok) return;
    const b = document.getElementById('chatBadge');
    if (!b) return;
    if (j.unread && j.unread>0) { b.style.display='inline-block'; b.textContent = j.unread; }
    else b.style.display='none';
  });
}
updateTopBadge();
setInterval(updateTopBadge, 5000);
</script>

</html>
