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
        
        <button id="showChangePassModal" class="btn ghost" type="button" style="width:100%; margin-top: 10px;">
          Trocar Senha
        </button>

        <p id="loginError" class="error"></p>
      </div>
    </div>
  </main>
  
  
  <div id="passwordModal" class="modal-overlay" style="display:none;">
    <div class="modal-content">
      <span class="modal-close" id="modalCloseBtn">&times;</span>
      <h3>Alterar Senha</h3>
      <form id="passwordForm" class="form-modal">
        <label for="modal_username">Usuário:</label>
        <input type="text" id="modal_username" required autocomplete="username">
        
        <label for="current_password">Senha Atual:</label>
        <input type="password" id="current_password" required autocomplete="current-password">
        
        <label for="new_password">Nova Senha:</label>
        <input type="password" id="new_password" required autocomplete="new-password">
        
        <label for="confirm_password">Confirmar Nova Senha:</label>
        <input type="password" id="confirm_password" required autocomplete="new-password">
        
        <button type="submit" class="btn primary">Salvar Nova Senha</button>
        <p id="modalMessage" style="display:none; text-align:center; font-weight:bold; margin-top:15px;"></p>
      </form>
    </div>
  </div>
  <div id="toast-container" class="toast-container"></div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  // --- Lógica de Erro de Login (Original) ---
  const loginError = "<?php echo isset($_SESSION['login_error']) ? addslashes($_SESSION['login_error']) : ''; ?>";

  if (loginError) {
    const errorBox = document.getElementById('loginError');
    errorBox.textContent = loginError;
    errorBox.style.color = '#fff';
    errorBox.style.background = '#e63946';
    errorBox.style.padding = '10px';
    errorBox.style.borderRadius = '8px';
    errorBox.style.marginTop = '10px';
    errorBox.style.textAlign = 'center';
    errorBox.style.fontWeight = 'bold';

    // Limpa a mensagem de erro da sessão pra não reaparecer
    fetch('clear_error.php').catch(() => {});
  }
  
  
  // =========================================================
  // ================ (JAVASCRIPT ADICIONADO) ================
  // ================ LÓGICA MODAL SENHA =====================
  // =========================================================
  const showPassBtn = document.getElementById('showChangePassModal');
  const passwordModal = document.getElementById('passwordModal');
  const modalCloseBtn = document.getElementById('modalCloseBtn');
  const passwordForm = document.getElementById('passwordForm');
  const modalMessage = document.getElementById('modalMessage');

  if (showPassBtn && passwordModal && modalCloseBtn && passwordForm) {
      // Função para mostrar mensagem no modal
      const showModalMessage = (message, isError = false) => {
          modalMessage.textContent = message;
          modalMessage.style.color = isError ? 'var(--danger)' : 'var(--success)';
          modalMessage.style.display = 'block';
      };
      
      // Abrir o modal
      showPassBtn.addEventListener('click', () => {
          passwordModal.style.display = 'flex';
          modalMessage.style.display = 'none'; // Limpa mensagens antigas
          passwordForm.reset();
      });

      // Fechar o modal
      const closeModal = () => {
          passwordModal.style.display = 'none';
      };
      modalCloseBtn.addEventListener('click', closeModal);
      passwordModal.addEventListener('click', (e) => {
          if (e.target === passwordModal) { // Fecha se clicar fora
              closeModal();
          }
      });

      // Enviar o formulário
      passwordForm.addEventListener('submit', (e) => {
          e.preventDefault();
          const username = document.getElementById('modal_username').value;
          const current_password = document.getElementById('current_password').value;
          const new_password = document.getElementById('new_password').value;
          const confirm_password = document.getElementById('confirm_password').value;
          
          if (new_password !== confirm_password) {
              showModalMessage('A nova senha e a confirmação não batem.', true);
              return;
          }
          
          const btn = passwordForm.querySelector('button[type="submit"]');
          btn.disabled = true;
          btn.textContent = 'Salvando...';
          modalMessage.style.display = 'none';
          
          const body = new URLSearchParams();
          body.append('action', 'change_password_public'); // Ação pública
          body.append('username', username);
          body.append('current_password', current_password);
          body.append('new_password', new_password);
          body.append('confirm_password', confirm_password);
          
          fetch('api.php', { method: 'POST', body: body })
              .then(r => r.json())
              .then(j => {
                  if (j.ok) {
                      showModalMessage(j.msg || 'Senha alterada com sucesso!', false);
                      setTimeout(closeModal, 2000); // Fecha após 2s
                  } else {
                      showModalMessage(j.msg || 'Erro desconhecido.', true);
                  }
              })
              .finally(() => {
                  btn.disabled = false;
                  btn.textContent = 'Salvar Nova Senha';
              });
      });
  }
  // =========================================================
  // ================ FIM DO JAVASCRIPT ADICIONADO ===========
  // =========================================================
});
</script>

</body>
</html>