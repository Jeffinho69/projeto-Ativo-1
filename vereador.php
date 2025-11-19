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
      <button id="logoutBtn" class="btn ghost" onclick="location.href='logout.php'">Sair</button>

      <div class="notification-wrap">
          <button id="notificationBellBtn" class="btn ghost" style="padding: 8px 12px; position: relative;">
              🔔
              <span id="notificationBadge" class="badge" style="display:none; position:absolute; top:-8px; right:-8px; background: #e53935; color: #fff; border-radius: 999px; padding: 2px 6px; font-size: 11px; border: 2px solid var(--card, #fff);">0</span>
          </button>
          
          <div id="notificationDropdown" class="notification-dropdown" style="display:none;">
              <div class="notification-header">
                  <strong>Notificações</strong>
              </div>
              <div id="notificationList" class="notification-list">
                  <div class="notification-empty">Nenhuma notificação.</div>
              </div>
          </div>
      </div>
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

<!-- Botão flutuante de ajuda -->
<button id="helpBtn" class="btn ghost" 
        style="position:fixed;bottom:20px;right:20px;z-index:1000;background:#0b73e0;color:#fff;border:none;padding:12px 20px;border-radius:30px;box-shadow:0 3px 6px rgba(0,0,0,0.2);cursor:pointer;font-weight:bold;">
  💬 Ajuda / Suporte
</button>

<!-- Janela de ajuda -->
<div id="helpBox" class="light" 
     style="display:none;position:fixed;bottom:80px;right:20px;width:350px;background:#fff;border-radius:14px;box-shadow:0 4px 12px rgba(0,0,0,0.2);padding:18px;z-index:1001;">
  <h3 style="margin-top:0;color:#0b73e0;">Chamado de Suporte</h3>
  <p style="font-size:14px;color:#444;margin-bottom:6px;">
    Envie um e-mail para o suporte:<br>
    <b style="color:#0b73e0;">help@camaravitoria.on.spiceworks.com</b>
  </p>

  <p style="font-size:13px;line-height:1.5;color:#555;margin-bottom:8px;">
    ➤ Acesse o Webmail clicando abaixo:<br>
    <a href="https://webmail.vitoriadesantoantao.pe.leg.br/cpsess6726689678/3rdparty/roundcube/?_task=mail&_action=compose&_id=1035450755690c9163904a1"
       target="_blank" style="color:#0b73e0;text-decoration:none;font-weight:bold;">
       👉 Abrir Webmail
    </a>
  </p>

  <ul style="font-size:13px;color:#555;line-height:1.5;margin-left:18px;">
    <li>Faça login com o <b>mesmo e-mail</b> usado nesse sistema e <b>senha</b> fornecida pelo setor de TI <b> Se não foi alterada.</b></li>
    <li> Após logar, clicar em <b>Criar email</b></li>
    <li>No campo <b>Para</b>, digite o e-mail do suporte acima.</li>
    <li>No <b>Assunto</b>, escreva o título do problema.<br>
      <small style="color:#777;">Ex: Impressora não puxa papel</small>
    </li>
    <li>No corpo do e-mail, descreva o defeito detalhadamente.<br>
      <small style="color:#777;">Se possível, anexe uma foto.</small>
    </li>
  </ul>

  <p style="font-size:13px;color:#666;margin-top:8px;">
    ✅ Após enviar, sua solicitação será automaticamente encaminhada ao setor de TI.
  </p>

  <div style="text-align:right;margin-top:12px;">
    <button id="closeHelp" class="btn ghost" 
            style="background:#f2f2f2;border:none;padding:6px 14px;border-radius:8px;cursor:pointer;">Fechar</button>
  </div>
</div>

<script>
const helpBtn = document.getElementById('helpBtn');
const helpBox = document.getElementById('helpBox');
const closeHelp = document.getElementById('closeHelp');

if (helpBtn && helpBox && closeHelp) {
  helpBtn.addEventListener('click', () => {
    helpBox.style.display = helpBox.style.display === 'none' ? 'block' : 'none';
  });
  closeHelp.addEventListener('click', () => {
    helpBox.style.display = 'none';
  });
}
</script>

<script src="notifications.js"></script> 
</body>
</html>
