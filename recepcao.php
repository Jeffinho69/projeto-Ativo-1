<?php
// recepcao.php
require_once 'config.php';
if (empty($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}
$user = $_SESSION['user'];
if ($user['role'] !== 'recep' && $user['role'] !== 'admin') {
    echo "Acesso negado.";
    exit;
}

// lista de vereadores (pode ser dinâmica, aqui você pode buscar de uma tabela; mantemos estática)
$councilors = [
  "Ana Paula", "TI", 
];
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Painel - Recepção</title>
  <link rel="stylesheet" href="style.css">
  
  <script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/jspdf-autotable@3.8.2/dist/jspdf.plugin.autotable.min.js"></script>
  
</head>
<body class="panel-page">
  <header class="topbar light">
    <div class="brand">
      <img src="https://tse3.mm.bing.net/th/id/OIP.NeJbj2QckKlAAfZ0YlkgUgHaJw?cb=12&pid=Api" alt="Brasão" class="brasao">
      <div>
        <div class="title">Painel da Recepção</div>
        <div class="subtitle">Câmara Municipal de Vitória de Santo Antão</div>
      </div>
    </div>
    <div class="top-actions">
      <div id="clock" class="clock">--:--:--</div>
    <div id="userDisplay" class="user-display"><?php echo htmlspecialchars($user['fullName']); ?></div>
    <button id="logoutBtn" class="btn ghost" onclick="location.href='logout.php'">Sair</button>
    <button id="openChatBtn" class="btn ghost" onclick="location.href='chat.php'">💬 Chat</button>

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
    <aside class="sidebar light">
      <h3>Vereadores / Setores</h3>
      <ul id="councilList" class="council-list">
        <?php foreach ($councilors as $c) echo "<li>" . htmlspecialchars($c) . "</li>"; ?>
      </ul>
    </aside>

    <section class="content light">
      <div class="controls">
        <button id="tabRegister" class="tab active">Registrar visitante</button>
        <button id="tabPending" class="tab">Aguardando aprovação</button>
        <button id="tabPresent" class="tab">Presentes</button>
        <button id="tabReports" class="tab">Relatórios</button>
      </div>

      <div id="panelRegister" class="panel active">
      
        <form id="visitorForm" class="visitor-form-grid">
          
          <div class="form-column">
            <label for="v_name">Nome do visitante</label>
            <div class="autocomplete-wrap">
              <input id="v_name" name="v_name" placeholder="Nome do visitante" autocomplete="off" required>
              <div id="v_suggestions" class="suggestions hidden"></div>
            </div>
            
            <label for="v_doc">Documento (CPF - opcional)</label>
            <input id="v_doc" name="v_doc" placeholder="Documento (CPF - opcional)" maxlength="14">
            
            <label for="v_reason">Motivo</label>
            <input id="v_reason" name="v_reason" placeholder="Motivo" required>
          </div>
          
          <div class="form-column-span2">
            <label>Selecione os Vereadores / Setores</label>
            <div id="v_council_list" class="council-checkbox-grid">
                <?php foreach ($councilors as $c): ?>
                    <label class="checkbox-label">
                        <input type="checkbox" name="council[]" value="<?php echo htmlspecialchars($c); ?>">
                        <span><?php echo htmlspecialchars($c); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
          </div>
          
          <button type="submit" class="btn primary">Registrar Entrada (Adicionar Visita)</button>
        </form>
        <div class="note muted">Visitas adicionadas aparecem em "Aguardando aprovação".</div>
      </div>

      <div id="panelPending" class="panel">
        <h3>Aguardando aprovação</h3>
        <table class="table" id="pendingTable">
          <thead><tr><th>Nome</th><th>Documento</th><th>Vereador / Setor</th><th>Entrada</th><th>Ações</th></tr></thead>
          <tbody></tbody>
        </table>
      </div>

      <div id="panelPresent" class="panel">
        <h3>Pessoas presentes</h3>
        <table class="table" id="presentTable">
          <thead><tr><th>Nome</th><th>Documento</th><th>Vereador / Setor</th><th>Entrada</th><th>Saída</th><th>Ações</th></tr></thead>
          <tbody></tbody>
        </table>
      </div>

      <div id="panelReports" class="panel">
        <h3>Relatórios</h3>
        
        <div class="report-controls">
            <div class="report-filter-group">
                <label>Filtrar por recepcionista:</label>
                <select id="reportUserFilter"><option value="all">Todos</option></select>
            </div>
            <div class="report-filter-group">
                <label>Período:</label>
                <input type="date" id="reportFrom" title="Data de Início">
                <span>até</span>
                <input type="date" id="reportTo" title="Data de Fim">
            </div>
            <div class="report-filter-actions">
                <button id="generatePdf" class="btn primary">Gerar PDF</button>
                <button id="btn-limpar-relatorios" class="btn-limpar">Limpar Filtrados</button>
            </div>
        </div>

        <div class="table-wrapper">
            <table class="table" id="reportTable">
              <thead>
                <tr>
                    <th>Visitante</th>
                    <th>Documento</th>
                    <th>Motivo</th>
                    <th>Vereador/Setor</th>
                    <th>Data Adição</th>
                    <th>Adicionado Por</th>
                    <th>Data Entrada</th>
                    <th>Data Saída</th>
                </tr>
              </thead>
              <tbody>
                </tbody>
            </table>
        </div> </div>

    </section>
  </main>

  <div id="toast-container" class="toast-container"></div>

<button id="helpBtn" class="btn ghost" 
        style="position:fixed;bottom:20px;right:20px;z-index:1000;background:#0b73e0;color:#fff;border:none;padding:12px 20px;border-radius:30px;box-shadow:0 3px 6px rgba(0,0,0,0.2);cursor:pointer;font-weight:bold;">
  💬 Ajuda / Suporte
</button>
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
<script>
function updateTopBadge(){
  fetch('chat_api.php?action=poll_unread').then(r=>r.json()).then(j=>{
  });
}
updateTopBadge();
setInterval(updateTopBadge, 5000);
</script>
<script src="script.js"></script>
<script src="notifications.js"></script> 
</body>
</html>
