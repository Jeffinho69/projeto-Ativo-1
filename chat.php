<?php
// chat.php
require_once 'config.php';
if (empty($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}
$user = $_SESSION['user']; // tem username, fullName, role
$username = htmlspecialchars($user['username']);
$fullName = htmlspecialchars($user['fullName'] ?? $user['username']);
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Chat Interno - Recepção</title>
  <link rel="stylesheet" href="style.css">
  <style>
  /* Chat minimal — mantém a identidade visual (cores, sombras) */
  .chat-wrap { max-width:1100px; margin:24px auto; display:flex; gap:18px; align-items:flex-start; padding:18px; }
  .chat-list { width:300px; background:var(--card); border-radius:12px; box-shadow:var(--shadow); padding:12px; overflow:auto; max-height:70vh; }
  .chat-list .contact { padding:10px; border-radius:8px; display:flex; justify-content:space-between; gap:8px; cursor:pointer; align-items:center; }
  .chat-list .contact:hover { background:#f0f7ff; transform:translateX(4px); }
  .chat-list .contact.active { background: linear-gradient(90deg,var(--accent),var(--accent-700)); color:#fff; }

  .chat-main { flex:1; display:flex; flex-direction:column; background:var(--card); border-radius:12px; box-shadow:var(--shadow); max-height:80vh; overflow:hidden; }
  .chat-header { padding:14px; border-bottom:1px solid #eef3f9; display:flex; align-items:center; justify-content: space-between; gap:12px; }
  .chat-body { padding:16px; overflow:auto; flex:1; background:linear-gradient(#fbfdff,#f7fbff); }
  .chat-footer { padding:12px; border-top:1px solid #eef3f9; display:flex; gap:8px; align-items:center; }
  .msg { max-width:75%; margin-bottom:10px; padding:10px 12px; border-radius:12px; box-shadow:0 6px 18px rgba(11,18,28,0.06); }
  .msg.me { margin-left:auto; background:linear-gradient(90deg,var(--accent),var(--accent-700)); color:#fff; border-bottom-right-radius:4px; }
  .msg.them { margin-right:auto; background:#fff; color:#111; border-bottom-left-radius:4px; }
  .msg .time{ display:block; font-size:12px; color:var(--muted); margin-top:6px; opacity:0.9 }
  
  /* ========================================================= */
  /* ================ CSS DOS BOTÕES (MODIFICADO) ============ */
  /* ========================================================= */
  .msg .time button { 
      background:transparent; 
      border: 1px solid currentColor; /* Borda para visibilidade */
      border-radius: 4px;
      color:inherit; 
      opacity: 0.8; /* Mais opaco */
      cursor:pointer; 
      font-size:11px; 
      padding: 2px 4px; /* Espaçamento interno */
      margin-left: 8px; 
  }
  .msg .time button:hover { opacity: 1; background: rgba(0,0,0,0.1); }
  .msg.me .time { color: rgba(255,255,255,0.7); }
  .msg.me .time button { color: rgba(255,255,255,0.8); }
  .msg.me .time button:hover { background: rgba(255,255,255,0.2); }
  
  /* (NOVO) Estilo para o botão "Apagar para Todos" */
  .msg .time button.btn-everyone {
      color: #ef4444; /* Vermelho */
      border-color: #ef4444;
  }
  .msg.me .time button.btn-everyone {
      color: #ffb8b8; /* Vermelho claro no balão escuro */
      border-color: #ffb8b8;
  }
  /* ========================================================= */
  
  .input { flex:1; padding:10px 12px; border-radius:10px; border:2px solid #e6edf3; outline:none; }
  .btn-send { padding:10px 14px; border-radius:10px; border:none; background:linear-gradient(90deg,var(--accent),var(--accent-700)); color:#fff; font-weight:700; cursor:pointer; }
  .unread-badge { background:#ef4444;color:white;padding:4px 8px;border-radius:999px;font-weight:700;font-size:12px; }

  /* responsivo */
  @media (max-width:900px){
    .chat-wrap { flex-direction:column; }
    .chat-list { width:100%; max-height:200px; order:2; }
    .chat-main { order:1; max-height:60vh; }
  }
  </style>
</head>
<body class="panel-page">
  <header class="topbar light">
    <div class="brand">
      <img src="https://tse3.mm.bing.net/th/id/OIP.NeJbj2QckKlAAfZ0YlkgUgHaJw?cb=12&pid=Api" alt="Brasão" class="brasao">
      <div>
        <div class="title">Chat Interno</div>
        <div class="subtitle">Converse com vereadores e recepcionistas</div>
      </div>
    </div>

    <div class="top-actions">
      <div id="clock" class="clock">--:--:--</div>
      <div class="user-display"><?php echo $fullName;?></div>
      <button class="btn ghost" onclick="history.back()">Voltar</button>
    </div>
  </header>

  <main class="chat-wrap">
    <aside class="chat-list" id="contacts">
      <div style="font-weight:700;margin-bottom:8px">Contatos</div>
      <div id="contactsList">Carregando...</div>
    </aside>

    <section class="chat-main">
      <div class="chat-header" id="chatHeader">
        <div>
            <div style="font-weight:800" id="chatWith">Selecione um contato</div>
            <div style="margin-left:auto" id="contactBadge"></div>
        </div>
        
        <div style="display:flex; gap: 8px;">
            <button id="btnDeleteConv" class="btn ghost" style="display:none; background:#ffc107; color:black;" onclick="deleteEntireConversation()">
                Apagar (p/ mim)
            </button>
            <button id="btnDeleteConvEveryone" class="btn ghost" style="display:none; background:#e53935; color:white;" onclick="deleteConversationEveryone()">
                Apagar (p/ Todos)
            </button>
        </div>
      </div>

      <div class="chat-body" id="chatBody">Selecione um contato para começar a conversar.</div>

      <div class="chat-footer">
        <input id="chatInput" class="input" placeholder="Escreva uma mensagem..." disabled>
        <button id="btnSend" class="btn-send" disabled>Enviar</button>
      </div>
    </section>
  </main>

  <div id="toast-container" class="toast-container"></div>

  <audio id="pingAudio" src=""></audio>

<script>
const myUser = '<?php echo addslashes($username); ?>';
let currentContact = null;
let pollInterval = null;
let lastMessageId = 0;
const API_URL = 'chat_api.php'; // URL da API

// helper
function el(q){ return document.querySelector(q); }
function escapeHtml(s){ return String(s||'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }

function showToast(text, type='success'){
  const c = document.getElementById('toast-container');
  if(!c) return;
  const t = document.createElement('div');
  t.className = 'toast ' + (type==='error'?'error':'success');
  t.innerHTML = '<strong>'+escapeHtml(text)+'</strong>';
  c.appendChild(t);
  setTimeout(()=>{ t.style.opacity='0'; setTimeout(()=>t.remove(),400); }, 3200);
}

// carrega lista de contatos (quem trocou msgs ou usuários do sistema)
async function loadContacts(){
  const res = await fetch(API_URL + '?action=list_contacts');
  const j = await res.json();
  const list = document.getElementById('contactsList');
  list.innerHTML = '';
  if(!j.ok){ list.textContent = 'Erro ao carregar'; return; }
  j.data.forEach(c=>{
    const div = document.createElement('div');
    div.className = 'contact';
    if(c.username === currentContact) div.classList.add('active');
    div.dataset.username = c.username;
    div.innerHTML = `<div><strong>${escapeHtml(c.fullName)}</strong><div style="font-size:13px;color:var(--muted)">${escapeHtml(c.username)}</div></div><div>${c.unread? '<span class="unread-badge">'+c.unread+'</span>':''}</div>`;
    div.addEventListener('click', ()=> openChatWith(c.username, c.fullName));
    list.appendChild(div);
  });
}

// (FUNÇÃO MODIFICADA)
// abre conversa com contato
function openChatWith(username, fullName){
  currentContact = username;
  el('#chatWith').textContent = fullName + ' ('+username+')';
  el('#chatInput').disabled = false;
  el('#btnSend').disabled = false;
  
  // Mostra os botões de apagar conversa
  el('#btnDeleteConv').style.display = 'inline-block';
  el('#btnDeleteConvEveryone').style.display = 'inline-block';
  
  lastMessageId = 0;
  el('#chatBody').innerHTML = 'Carregando...';
  markRead(username);
  loadMessages(true); // true = full reload
  
  if(pollInterval) clearInterval(pollInterval);
  pollInterval = setInterval(()=> { loadMessages(false); loadContacts(); }, 2000); // false = poll new
  
  // Atualiza a lista de contatos para remover o badge
  loadContacts(); 
}

// marca mensagens como lidas (server-side)
async function markRead(contact){
  await fetch(API_URL + '?action=mark_read', {
    method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'contact='+encodeURIComponent(contact)
  });
  loadContacts();
}

// (FUNÇÃO MODIFICADA)
// carrega mensagens
async function loadMessages(isFullReload = false){
  if(!currentContact) return;
  
  // Se for um reload total, limpa o 'lastMessageId' e o chat
  if (isFullReload) {
      lastMessageId = 0;
      el('#chatBody').innerHTML = '';
  }
  
  const res = await fetch(API_URL + '?action=fetch&contact='+encodeURIComponent(currentContact)+'&since_id='+lastMessageId);
  const j = await res.json();
  if(!j.ok) return;
  const body = el('#chatBody');
  
  let newMessages = false;
  j.data.forEach(m=>{
    newMessages = true;
    // Evita duplicar mensagens
    if (document.querySelector(`.msg[data-id="${m.id}"]`)) return; 
    
    lastMessageId = Math.max(lastMessageId, m.id);
    const div = document.createElement('div');
    div.className = 'msg ' + (m.sender === myUser ? 'me' : 'them');
    div.dataset.id = m.id; // Adiciona ID para o 'delete'
    
    // (MODIFICADO) Adiciona os botões de apagar
    let deleteButtonMe = `<button onclick="deleteMessage(${m.id})">Apagar p/ mim</button>`;
    let deleteButtonEveryone = '';
    
    // Botão "Apagar para Todos" (só se eu enviei)
    if (m.sender === myUser) {
        deleteButtonEveryone = `<button class="btn-everyone" onclick="deleteMessageEveryone(${m.id})">Apagar p/ Todos</button>`;
    }
    
    div.innerHTML = `
        <div>${escapeHtml(m.message)}</div>
        <span class="time">
            ${escapeHtml(m.sender)} • ${escapeHtml(m.sent_at)}
            ${deleteButtonMe}
            ${deleteButtonEveryone}
        </span>
    `;
    body.appendChild(div);
  });
  
  if (newMessages) {
      body.scrollTop = body.scrollHeight;
  }
  
  if(j.playPing && !isFullReload) { // Só toca o ping em 'poll', não no 'load' inicial
    const audio = document.getElementById('pingAudio');
    if(audio && audio.src) audio.play().catch(()=>{});
  }
}

// enviar mensagem
async function sendMessage(){
  const txt = el('#chatInput').value.trim();
  if(!txt || !currentContact) return;
  el('#chatInput').value = '';
  const res = await fetch(API_URL + '?action=send', {
    method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body: `receiver=${encodeURIComponent(currentContact)}&message=${encodeURIComponent(txt)}`
  });
  const j = await res.json();
  if(j.ok){
    loadMessages(false); // Carrega novas mensagens
    loadContacts(); // Atualiza a barra lateral (para 'não lido')
  } else {
    showToast(j.msg || 'Erro ao enviar', 'error');
  }
}

// =========================================================
// ================ Apagar Mensagem (p/ mim) ===============
// =========================================================
async function deleteMessage(id) {
    if (!confirm('Tem certeza que deseja apagar esta mensagem?\n(Ela sumirá apenas para você).')) return;
    
    const res = await fetch(API_URL + '?action=delete_message', {
        method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: `id=${id}`
    });
    const j = await res.json();
    if (j.ok) {
        // Remove a mensagem da tela
        const msgElement = document.querySelector(`.msg[data-id="${id}"]`);
        if (msgElement) msgElement.remove();
        showToast('Mensagem apagada');
    } else {
        showToast(j.msg || 'Erro ao apagar', 'error');
    }
}

// =========================================================
// ================ ADICIONADO: Apagar Msg p/ Todos ========
// =========================================================
async function deleteMessageEveryone(id) {
    if (!confirm('Tem certeza que deseja apagar esta mensagem PARA TODOS?\n(Esta ação não pode ser desfeita).')) return;
    
    const res = await fetch(API_URL + '?action=delete_message_everyone', {
        method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: `id=${id}`
    });
    const j = await res.json();
    if (j.ok) {
        // Remove a mensagem da tela
        const msgElement = document.querySelector(`.msg[data-id="${id}"]`);
        if (msgElement) msgElement.remove();
        showToast('Mensagem apagada para todos');
    } else {
        showToast(j.msg || 'Erro ao apagar', 'error');
    }
}

// =========================================================
// ================ Apagar Conversa (p/ mim) ===============
// =========================================================
async function deleteEntireConversation() {
    if (!currentContact) return;
    if (!confirm('Tem certeza que deseja apagar TODAS as mensagens desta conversa?\n(Elas sumirão apenas para você).')) return;
    
    const res = await fetch(API_URL + '?action=delete_conversation', {
        method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: `contact=${encodeURIComponent(currentContact)}`
    });
    const j = await res.json();
    if (j.ok) {
        // Recarrega a conversa (que agora estará vazia)
        loadMessages(true); // true = full reload
        showToast('Conversa limpa');
    } else {
        showToast(j.msg || 'Erro ao limpar conversa', 'error');
    }
}

// =========================================================
// ================ ADICIONADO: Apagar Conv. p/ Todos ======
// =========================================================
async function deleteConversationEveryone() {
    if (!currentContact) return;
    if (!confirm('PERIGO!\nTem certeza que deseja apagar TODAS as mensagens desta conversa PARA TODOS?\n(Esta ação não pode ser desfeita).')) return;
    
    const res = await fetch(API_URL + '?action=delete_conversation_everyone', {
        method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: `contact=${encodeURIComponent(currentContact)}`
    });
    const j = await res.json();
    if (j.ok) {
        loadMessages(true); // Recarrega a conversa (vazia)
        showToast('Conversa limpa para todos');
    } else {
        showToast(j.msg || 'Erro ao limpar conversa', 'error');
    }
}
// =========================================================


document.addEventListener('DOMContentLoaded', ()=>{
  loadContacts();
  document.getElementById('btnSend').addEventListener('click', sendMessage);
  document.getElementById('chatInput').addEventListener('keydown', (e)=>{ if(e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }});
  // inicializa relógio (se tiver)
  const clockEl = document.getElementById('clock');
  if(clockEl) { function updateClock(){ clockEl.textContent = new Date().toLocaleTimeString(); } updateClock(); setInterval(updateClock,1000); }
});
</script>
</body>
</html>