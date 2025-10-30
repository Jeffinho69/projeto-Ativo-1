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
  .chat-header { padding:14px; border-bottom:1px solid #eef3f9; display:flex; align-items:center; gap:12px; }
  .chat-body { padding:16px; overflow:auto; flex:1; background:linear-gradient(#fbfdff,#f7fbff); }
  .chat-footer { padding:12px; border-top:1px solid #eef3f9; display:flex; gap:8px; align-items:center; }
  .msg { max-width:75%; margin-bottom:10px; padding:10px 12px; border-radius:12px; box-shadow:0 6px 18px rgba(11,18,28,0.06); }
  .msg.me { margin-left:auto; background:linear-gradient(90deg,var(--accent),var(--accent-700)); color:#fff; border-bottom-right-radius:4px; }
  .msg.them { margin-right:auto; background:#fff; color:#111; border-bottom-left-radius:4px; }
  .msg .time{ display:block; font-size:12px; color:var(--muted); margin-top:6px; opacity:0.9 }
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
      <button class="btn ghost" onclick="location.href='painel.php'">Voltar</button>
    </div>
  </header>

  <main class="chat-wrap">
    <aside class="chat-list" id="contacts">
      <!-- contatos carregados por JS -->
      <div style="font-weight:700;margin-bottom:8px">Contatos</div>
      <div id="contactsList">Carregando...</div>
    </aside>

    <section class="chat-main">
      <div class="chat-header" id="chatHeader">
        <div style="font-weight:800" id="chatWith">Selecione um contato</div>
        <div style="margin-left:auto" id="contactBadge"></div>
      </div>

      <div class="chat-body" id="chatBody">Selecione um contato para começar a conversar.</div>

      <div class="chat-footer">
        <input id="chatInput" class="input" placeholder="Escreva uma mensagem..." disabled>
        <button id="btnSend" class="btn-send" disabled>Enviar</button>
      </div>
    </section>
  </main>

  <div id="toast-container" class="toast-container"></div>

  <!-- Som de notificação (arquivo embutido como base64 simples Opcional) -->
  <audio id="pingAudio" src=""></audio>

<script>
const myUser = '<?php echo addslashes($username); ?>';
let currentContact = null;
let pollInterval = null;
let lastMessageId = 0;

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
  const res = await fetch('chat_api.php?action=list_contacts');
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



// abre conversa com contato
function openChatWith(username, fullName){
  currentContact = username;
  el('#chatWith').textContent = fullName + ' ('+username+')';
  el('#chatInput').disabled = false;
  el('#btnSend').disabled = false;
  lastMessageId = 0;
  el('#chatBody').innerHTML = 'Carregando...';
  markRead(username);
  loadMessages();
  if(pollInterval) clearInterval(pollInterval);
  pollInterval = setInterval(()=> { loadMessages(); loadContacts(); }, 2000);
  loadContacts();
}

// marca mensagens como lidas (server-side)
async function markRead(contact){
  await fetch('chat_api.php?action=mark_read', {
    method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'contact='+encodeURIComponent(contact)
  });
  loadContacts();
}

// carrega mensagens
async function loadMessages(){
  if(!currentContact) return;
  const res = await fetch('chat_api.php?action=fetch&contact='+encodeURIComponent(currentContact)+'&since_id='+lastMessageId);
  const j = await res.json();
  if(!j.ok) return;
  const body = el('#chatBody');
  // if full replace when since_id==0
  if(Number(j.since_id) === 0 || lastMessageId === 0){
    body.innerHTML = '';
  }
  j.data.forEach(m=>{
    lastMessageId = Math.max(lastMessageId, m.id);
    const div = document.createElement('div');
    div.className = 'msg ' + (m.sender === myUser ? 'me' : 'them');
    div.innerHTML = `<div>${escapeHtml(m.message)}</div><span class="time">${escapeHtml(m.sender)} • ${escapeHtml(m.sent_at)}</span>`;
    body.appendChild(div);
  });
  body.scrollTop = body.scrollHeight;
  if(j.playPing) {
    const audio = document.getElementById('pingAudio');
    if(audio && audio.src) audio.play().catch(()=>{});
  }
}

// enviar mensagem
async function sendMessage(){
  const txt = el('#chatInput').value.trim();
  if(!txt || !currentContact) return;
  el('#chatInput').value = '';
  const res = await fetch('chat_api.php?action=send', {
    method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body: `receiver=${encodeURIComponent(currentContact)}&message=${encodeURIComponent(txt)}`
  });
  const j = await res.json();
  if(j.ok){
    loadMessages();
    loadContacts();
  } else {
    showToast(j.msg || 'Erro ao enviar', 'error');
  }
}

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
