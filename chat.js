// chat.js
// polling chat basic
const API = 'chat_api.php';
let pollTimer = null;
let sidebarTimer = null;
let audioPing = null;

// load audio
try {
  audioPing = new Audio();
  audioPing.src = 'data:audio/mp3;base64,//uQx...'; // optional: placeholder (you can replace with real file path 'ping.mp3')
} catch(e){ audioPing = null; }

function fetchContacts(){
  fetch(API + '?action=list_contacts').then(r=>r.json()).then(j=>{
    if(!j.ok) return;
    const box = document.getElementById('contactsList');
    box.innerHTML = '';
    j.data.forEach(c=>{
      const el = document.createElement('div');
      el.className = 'contact';
      el.dataset.username = c.username;
      el.innerHTML = `<div>
          <div class="name">${escapeHtml(c.fullName||c.username)}</div>
          <div class="preview small-muted">${escapeHtml(c.role||'')}</div>
        </div>
        <div><span class="badge" id="badge-${c.username}" style="display:none">0</span></div>`;
      el.addEventListener('click', ()=> openConversation(c.username, c.fullName));
      box.appendChild(el);
    });
  });
}

function openConversation(username, full){
  currentWith = username;
  document.getElementById('convWith').textContent = full || username;
  document.getElementById('convSub').textContent = 'Carregando...';
  document.getElementById('msgText').disabled = false;
  document.getElementById('sendBtn').disabled = false;
  // highlight in sidebar
  document.querySelectorAll('.contact').forEach(el=>{
    el.classList.toggle('contact-selected', el.dataset.username===username);
  });
  loadConversation(username);
}

function loadConversation(username){
  fetch(API + '?action=get_conversation&with=' + encodeURIComponent(username)).then(r=>r.json()).then(j=>{
    if(!j.ok) return;
    renderMessages(j.data);
    // clear badge
    const b = document.getElementById('badge-' + username);
    if (b) b.style.display='none';
  });
}

function renderMessages(msgs){
  const area = document.getElementById('messagesArea');
  area.innerHTML = '';
  if (!msgs.length) {
    area.innerHTML = '<div class="empty-center">Sem mensagens</div>';
    return;
  }
  msgs.forEach(m=>{
    const div = document.createElement('div');
    div.className = 'msg ' + (m.sender === me ? 'me' : 'other');
    div.dataset.id = m.id;
    div.innerHTML = `<div>${escapeHtml(m.message)}</div>
      <div class="meta">${escapeHtml(m.sender)} • ${m.created_at}
        <button style="margin-left:8px;font-size:12px;border:none;background:transparent;cursor:pointer;color:rgba(0,0,0,0.35)" onclick="deleteMessage(${m.id})">Apagar</button>
      </div>`;
    area.appendChild(div);
  });
  area.scrollTop = area.scrollHeight;
}

document.getElementById && document.addEventListener('DOMContentLoaded', ()=>{
  fetchContacts();
  document.getElementById('refreshContacts').addEventListener('click', fetchContacts);
  document.getElementById('sendBtn').addEventListener('click', sendMessage);
  document.getElementById('msgText').addEventListener('keydown', (e)=>{
    if(e.key === 'Enter' && !e.shiftKey){ e.preventDefault(); sendMessage(); }
  });

  // poll unread / sidebar every 4s
  sidebarTimer = setInterval(pollUnread, 4000);
  pollUnread();
});

function pollUnread(){
  fetch(API + '?action=poll_unread').then(r=>r.json()).then(j=>{
    if(!j.ok) return;
    // show global notification in top? Here we update badges for contacts via recent_preview
    fetch(API + '?action=recent_preview').then(r=>r.json()).then(resp=>{
      if(!resp.ok) return;
      // resp.data: other,message,created_at,from
      resp.data.forEach(item=>{
        const other = item.other;
        const badge = document.getElementById('badge-' + other);
        if (!badge) return;
        // if message from other to me and not read -> show badge
        if (item.from !== me) {
          badge.textContent = '1';
          badge.style.display = 'inline-block';
          // play sound if conversation not open
          if (currentWith !== other) {
            if (audioPing) { try { audioPing.currentTime = 0; audioPing.play(); } catch(e){} }
          }
        } else {
          badge.style.display = 'none';
        }
      });
    });
  });
}

function sendMessage(){
  if (!currentWith) return alert('Selecione um contato.');
  const ta = document.getElementById('msgText');
  const txt = ta.value.trim();
  if (!txt) return;
  const form = new URLSearchParams();
  form.append('action','send');
  form.append('to', currentWith);
  form.append('message', txt);
  fetch(API, {method:'POST', body: form}).then(r=>r.json()).then(j=>{
    if (j.ok) {
      ta.value = '';
      loadConversation(currentWith);
    } else {
      alert(j.msg || 'Erro ao enviar');
    }
  });
}

function deleteMessage(id){
  if (!confirm('Apagar essa mensagem?')) return;
  const f = new URLSearchParams();
  f.append('action','delete_message');
  f.append('id', id);
  fetch(API, {method:'POST', body: f}).then(r=>r.json()).then(j=>{
    if (j.ok) {
      // remove element
      const el = document.querySelector(`.msg[data-id="${id}"]`);
      if (el) el.remove();
    } else alert(j.msg || 'Erro ao apagar');
  });
}

function escapeHtml(s){ if (s===null||s===undefined) return ''; return String(s).replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }
