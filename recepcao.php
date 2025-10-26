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
  "Ana Paula", "André de Baú", "Baú Nogueira", "BIU De Genário",
  "Carlos Henrique Queiroz", "Celso Bezerra", "Denis Lima", "Diretoria",
  "Edmilson de Várzea Grande", "Everaldo Arruda", "Fabio Raylux", "Felipe Cezar",
  "Gold do Pneu", "Josias da Saúde", "JOTA Domingos", "Lourinaldo Júnior",
  "Mano Holanda", "Marcos da Prestação", "Mizael de Davi", "Novo da Banca", "TI"
];
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Painel - Recepção</title>
  <link rel="stylesheet" href="style.css">
  <script defer src="script.js"></script>
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
        <form id="visitorForm" class="visitor-form">
          <div class="autocomplete-wrap">
            <input id="v_name" name="v_name" placeholder="Nome do visitante" autocomplete="off" required>
            <div id="v_suggestions" class="suggestions hidden"></div>
          </div>
          <input id="v_doc" name="v_doc" placeholder="Documento (CPF - opcional)" maxlength="14">
          <input id="v_reason" name="v_reason" placeholder="Motivo" required>
          <select id="v_council" name="v_council" required>
            <option value="">-- Selecione Vereador/Setor --</option>
            <?php foreach ($councilors as $c) echo "<option value=\"" . htmlspecialchars($c) . "\">" . htmlspecialchars($c) . "</option>"; ?>
          </select>

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
        <div class="report-controls" style="display:flex;gap:12px;align-items:center;margin-bottom:15px">
          <label>Filtrar por usuário:</label>
          <select id="reportUserFilter"><option value="all">Todos</option></select>
          <label>Período:</label>
          <input type="date" id="reportFrom"> até <input type="date" id="reportTo">
          <button id="generatePdf" class="btn primary">Gerar PDF</button>

          <div class="relatorio-acoes">
            <button id="btn-limpar-relatorios" class="btn-limpar">🧾 Limpar Tudo do Relatório (apagar filtrados)</button>
          </div>
        </div>
        <table class="table" id="reportTable">
          <thead><tr><th>Data</th><th>Visitante</th><th>Documento</th><th>Vereador</th><th>Ação</th><th>Responsável</th></tr></thead>
          <tbody></tbody>
        </table>
      </div>

    </section>
  </main>

  <div id="toast-container" class="toast-container"></div>
</body>
</html>