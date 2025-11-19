<?php
require_once 'config.php';
if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    echo "Acesso negado.";
    exit;
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Gerenciar Usuários</title>
<link rel="stylesheet" href="style.css">
<style>
/*
  ======================================================
  CONFIGURAÇÕES DE TEMA E VARIÁVEIS (Novo)
  ======================================================
*/
:root {
    /* Cores adaptadas ao tema Admin e inspirado na imagem da recepção */
    --primary: #0e4c92;        /* Azul Escuro (Para botões e destaque) */
    --primary-light: #185a9d;  /* Azul mais claro (Para hover) */
    --secondary: #333333;      /* Texto principal */
    --card: #ffffff;           /* Fundo dos painéis/cartões */
    --background: #f4f6f9;     /* Fundo da página (Cinza muito claro) */
    --border-color: #e0e0e0;   /* Borda suave */
    --shadow: 0 4px 12px rgba(0, 0, 0, 0.08); /* Sombra elegante */
    --danger: #e53935;         /* Vermelho para Excluir */
    --edit: #007bff;           /* Azul claro para Editar */
}

/* Aplicação de Fundo Limpo */
.panel-page {
    background-color: var(--background);
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    color: var(--secondary);
    min-height: 100vh;
}

/*
  ======================================================
  ESTILO DO CABEÇALHO (topbar)
  ======================================================
*/
.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 30px;
    background-color: var(--card);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    border-bottom: 1px solid var(--border-color);
}

.topbar .brand {
    display: flex;
    flex-direction: column;
}

.topbar .title {
    font-size: 20px;
    font-weight: 600;
    color: var(--primary);
}

.topbar .subtitle {
    font-size: 14px;
    color: #666;
    margin-top: 2px;
}

.top-actions .btn.ghost {
    background: transparent;
    color: var(--primary);
    border: 1px solid var(--primary);
    padding: 8px 14px;
    border-radius: 8px;
    font-weight: 500;
    transition: background-color 0.2s, color 0.2s;
    margin-left: 10px;
}

.top-actions .btn.ghost:hover {
    background-color: var(--primary);
    color: white;
}

/*
  ======================================================
  ESTILO DO MAIN (Conteúdo Central)
  ======================================================
*/
.main {
    max-width: 1100px; /* Aumentado para melhor visualização da tabela */
    margin: 30px auto;
    background: var(--card);
    padding: 30px; /* Padding maior */
    border-radius: 12px;
    box-shadow: var(--shadow);
}

.main h1 {
    font-size: 28px;
    color: var(--primary);
    border-bottom: 2px solid var(--border-color);
    padding-bottom: 10px;
    margin-bottom: 30px;
}

.main h3 {
    font-size: 20px;
    color: var(--secondary);
    margin-top: 30px;
    margin-bottom: 15px;
}

/*
  ======================================================
  ESTILO DE FORMULÁRIOS
  ======================================================
*/

/* Formulário Inline (Edição) */
.form-inline {
    display: flex;
    gap: 10px; /* Espaço entre os campos */
    align-items: center;
    padding: 15px;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    background-color: #fcfcfc;
    margin-bottom: 30px;
}

.form-inline input,
.form-inline select {
    padding: 10px; /* Padding maior */
    border-radius: 6px;
    border: 1px solid var(--border-color);
    transition: border-color 0.2s;
    flex-grow: 1;
    min-width: 0;
}

.form-inline input:focus,
.form-inline select:focus {
    border-color: var(--primary);
    outline: none;
    box-shadow: 0 0 0 1px var(--primary);
}

/* Formulário de Cadastro (Adicionar Novo Usuário) */
.form.light {
    display: grid;
    grid-template-columns: repeat(2, 1fr); /* 2 colunas */
    gap: 15px 25px;
    padding: 20px;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    background-color: #fcfcfc;
    margin-bottom: 30px;
}

.form.light label {
    font-weight: 500;
    color: var(--secondary);
    grid-column: span 1; /* Cada label em uma coluna */
}

.form.light input[type="text"],
.form.light input[type="password"],
.form.light select {
    padding: 10px;
    border-radius: 6px;
    border: 1px solid var(--border-color);
    transition: border-color 0.2s;
    width: 100%;
    box-sizing: border-box;
    grid-column: span 1; /* Cada campo em uma coluna */
    margin-bottom: 0;
}

.form.light button[type="submit"] {
    grid-column: span 2; /* O botão ocupa as duas colunas */
    margin-top: 10px;
    justify-self: end;
    width: auto;
}

/*
  ======================================================
  ESTILO DA TABELA E DATATABLES
  ======================================================
*/

/* Ajuste Geral da Tabela */
.table {
    width: 100%;
    border-collapse: separate; /* Separado para permitir border-radius */
    border-spacing: 0;
    margin-top: 25px;
    background: var(--card);
    border-radius: 8px;
    overflow: hidden; /* Garante que o border-radius funcione */
    border: 1px solid var(--border-color);
}

.table thead th {
    background: var(--background); /* Fundo da coluna do cabeçalho */
    color: var(--secondary);
    text-transform: uppercase;
    font-weight: 600;
    font-size: 13px;
    border-bottom: 1px solid var(--border-color);
    padding: 15px 12px;
}

.table tbody tr {
    transition: background-color 0.2s;
}

.table tbody tr:hover {
    background-color: #f9f9f9; /* Efeito hover suave */
}

.table td {
    padding: 12px;
    border-bottom: 1px solid #f0f0f0; /* Borda mais fina e clara */
}

/* Estilos para os wrappers do DataTables */
.dataTables_wrapper {
    padding: 10px 0;
}

.dataTables_wrapper .dataTables_filter input,
.dataTables_wrapper .dataTables_length select {
    border-radius: 6px;
    border: 1px solid var(--border-color);
    padding: 6px 10px;
    margin-left: 5px;
}

/*
  ======================================================
  ESTILO DE BOTÕES GERAIS
  ======================================================
*/
.btn {
    padding: 10px 18px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.2s ease;
    text-transform: uppercase;
    font-size: 14px;
}

.btn.primary {
    /* Gradiente sutil para o azul principal */
    background: linear-gradient(90deg, var(--primary), var(--primary-light));
    color: #fff;
}

.btn.primary:hover {
    box-shadow: 0 4px 10px rgba(14, 76, 146, 0.4);
    transform: translateY(-1px);
}

.btn.danger {
    background: var(--danger);
    color: #fff;
}

.btn.danger:hover {
    background: #d32f2f;
}

.btn.edit {
    background: var(--edit);
    color: #fff;
}

.btn.edit:hover {
    background: #0069d9;
}

/* Estilo para os botões dentro das células da tabela */
.table td .btn {
    padding: 6px 10px;
    font-size: 13px;
    text-transform: none;
    font-weight: 500;
}
</style>
</head>
<body class="panel-page">
<header class="topbar light">
  <div class="brand">
    <div class="title">Administração de Usuários</div>
    <div class="subtitle">Gerencie contas do sistema</div>
  </div>
  <div class="top-actions">
    <button class="btn ghost" onclick="location.href='logout.php'">Sair</button>
    <button class="btn ghost" onclick="location.href='chat.php?from=usuarios.php'">Chat Interno</button>

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
  <!-- DataTables CSS e JS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

</header>

<main class="main">
  <h1>Usuários do Sistema</h1>

<h3>Cadastrar Novo Usuário</h3>
<form id="addUserForm" method="POST" action="admin_add_user.php" class="form light">
  <label>Nome Completo:</label>
  <input type="text" name="fullName" required>

  <label>Usuário (login):</label>
  <input type="text" name="username" required>

  <label>Senha:</label>
  <input type="password" name="password" required>

  <label>Função:</label>
  <select name="role" required>
    <option value="recepcionista">Recepcionista</option>
    <option value="vereador">Vereador</option>
    <option value="admin">Administrador</option>
  </select>

  <button type="submit" class="btn primary">Cadastrar</button>
</form>

  <h3>Editar Usuarios Existentes</h3>
  <form id="userForm" class="form-inline">
    <input type="hidden" id="userId">
    <input type="text" id="username" placeholder="Usuário" required>
    <input type="text" id="fullName" placeholder="Nome completo" required>
    <input type="password" id="password" placeholder="Senha (deixe em branco para manter)">
    <select id="role">
      <option value="recep">Recepcionista</option>
      <option value="vereador">Vereador</option>
      <option value="admin">Administrador</option>
    </select>
    <button type="submit" class="btn primary">Salvar</button>
  </form>


  <table class="table" id="usersTable">
    <thead>
      <tr><th>ID</th><th>Usuário</th><th>Nome</th><th>Função</th><th>Ações</th></tr>
    </thead>
    <tbody></tbody>
  </table>

  


</main>

<script>
async function loadUsers(){
  const res = await fetch('usuarios_api.php?action=list');
  const j = await res.json();
  const tbody = document.querySelector('#usersTable tbody');
  tbody.innerHTML = '';
  if (!j.ok) return;
  j.data.forEach(u=>{
    const tr = document.createElement('tr');
    tr.innerHTML = `<td>${u.id}</td><td>${u.username}</td><td>${u.fullName}</td><td>${u.role}</td>
    <td>
      <button class='btn edit' onclick='editUser(${JSON.stringify(u)})'>Editar</button>
      <button class='btn danger' onclick='deleteUser(${u.id})'>Excluir</button>
    </td>`;
    tbody.appendChild(tr);
  });
}

async function deleteUser(id){
  if(!confirm('Excluir este usuário?')) return;
  const res = await fetch('usuarios_api.php?action=delete', {
    method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'id='+id
  });
  const j = await res.json();
  alert(j.msg || (j.ok ? 'Excluído!' : 'Erro'));
  loadUsers();
}

function editUser(u){
  document.querySelector('#userId').value = u.id;
  document.querySelector('#username').value = u.username;
  document.querySelector('#fullName').value = u.fullName;
  document.querySelector('#role').value = u.role;
  document.querySelector('#password').value = '';
}

document.querySelector('#userForm').addEventListener('submit', async e=>{
  e.preventDefault();
  const data = new URLSearchParams();
  data.append('id', document.querySelector('#userId').value);
  data.append('username', document.querySelector('#username').value);
  data.append('fullName', document.querySelector('#fullName').value);
  data.append('password', document.querySelector('#password').value);
  data.append('role', document.querySelector('#role').value);
  const res = await fetch('usuarios_api.php?action=save', {method:'POST', body:data});
  const j = await res.json();
  alert(j.msg || (j.ok ? 'Salvo com sucesso!' : 'Erro'));
  e.target.reset();
  loadUsers();
});

loadUsers();
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const table = $('#usersTable').DataTable({
  language: {
    url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json'
  },
  paging: false,        // ❌ remove paginação
  info: false,          // ❌ remove o “Mostrando 1 até X...”
  lengthChange: false,  // ❌ remove o “Exibir X por página”
  searching: true,      // mantém o campo de busca (opcional)
  order: [[0, 'desc']],
  responsive: true
});

  // Recarrega o DataTable sempre que os usuários forem carregados
  loadUsers = async function(){
    const res = await fetch('usuarios_api.php?action=list');
    const j = await res.json();
    table.clear();
    if (j.ok) {
      j.data.forEach(u => {
        table.row.add([
          u.id,
          u.username,
          u.fullName,
          u.role,
          `
          <button class='btn edit' onclick='editUser(${JSON.stringify(u)})'>Editar</button>
          <button class='btn danger' onclick='deleteUser(${u.id})'>Excluir</button>
          `
        ]);
      });
    }
    table.draw();
  }

  loadUsers();
});
</script>

<style>.dataTables_wrapper .dataTables_filter input {
  border-radius: 6px;
  border: 1px solid #ccc;
  padding: 4px 8px;
}

.dataTables_wrapper .dataTables_length select {
  border-radius: 6px;
  padding: 4px 6px;
}

.table thead th {
  background: #f8fafc;
  color: #333;
  text-transform: uppercase;
  font-weight: 600;
}
</style>

<script src="notifications.js"></script> 
</body>
</html>
