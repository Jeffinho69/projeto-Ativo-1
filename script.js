// script.js

document.addEventListener('DOMContentLoaded', () => {

  // elementos comuns

  const clockEl = document.getElementById('clock');

  if (clockEl) {

    function updateClock(){ clockEl.textContent = new Date().toLocaleTimeString(); }

    updateClock(); setInterval(updateClock, 1000);

  }



  // abas

  const tabRegister = document.getElementById('tabRegister');

  const tabPending = document.getElementById('tabPending');

  const tabPresent = document.getElementById('tabPresent');

  const tabReports = document.getElementById('tabReports');



  function switchTab(panelId, tabId){

    document.querySelectorAll('.panel').forEach(p=>p.classList.remove('active'));

    const panel = document.getElementById(panelId);

    if(panel) panel.classList.add('active');

    document.querySelectorAll('.tab').forEach(t=>t.classList.remove('active'));

    const tab = document.getElementById(tabId);

    if(tab) tab.classList.add('active');

  }

  if(tabRegister) tabRegister.addEventListener('click', ()=> switchTab('panelRegister','tabRegister'));

  if(tabPending) tabPending.addEventListener('click', ()=> switchTab('panelPending','tabPending'));

  if(tabPresent) tabPresent.addEventListener('click', ()=> switchTab('panelPresent','tabPresent'));

  if(tabReports) tabReports.addEventListener('click', ()=> { switchTab('panelReports','tabReports'); loadReportUsers(); renderReport(); });



  // ---------------------- RECEPÇÃO ----------------------

  const visitorForm = document.getElementById('visitorForm');

  const v_name = document.getElementById('v_name');

  const v_doc = document.getElementById('v_doc');

  const v_reason = document.getElementById('v_reason');

  const v_council = document.getElementById('v_council');

  const pendingTable = document.querySelector('#pendingTable tbody');

  const presentTable = document.querySelector('#presentTable tbody');



  if (v_doc) {

    v_doc.addEventListener('input', (e)=>{

      let nums = (e.target.value||'').replace(/\D/g,'').slice(0,11);

      // mask

      nums = nums.replace(/(\d{3})(\d)/,'$1.$2').replace(/(\d{3})(\d)/,'$1.$2').replace(/(\d{3})(\d{1,2})$/,'$1-$2');

      e.target.value = nums;

    });

  }



  if (visitorForm) {

    visitorForm.addEventListener('submit', (ev)=>{

      ev.preventDefault();

      const name = (v_name && v_name.value || '').trim();

      const doc = (v_doc && v_doc.value || '').trim();

      const reason = (v_reason && v_reason.value || '').trim();

      const council = (v_council && v_council.value || '').trim();

      if (!name || !council || !reason) { showToast('Preencha todos os campos','error'); return; }

      fetch('api.php?action=add_visitor', {

        method: 'POST',

        headers:{'Content-Type':'application/x-www-form-urlencoded'},

        body: `name=${encodeURIComponent(name)}&doc=${encodeURIComponent(doc)}&council=${encodeURIComponent(council)}&reason=${encodeURIComponent(reason)}`

      }).then(r=>r.json()).then(j=>{

        if (j.ok) {

          showToast('Visitante registrado e aguardando aprovação');

          visitorForm.reset();

          renderLists();

        } else showToast(j.msg || 'Erro','error');

      });

    });

  }



  // actions globales

  window.approveVisitor = function(id){

    fetch('api.php?action=approve', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:`id=${id}` })

      .then(r=>r.json()).then(j=>{ if (j.ok) { showToast('Entrada aprovada'); renderLists(); } else showToast(j.msg,'error'); });

  };

// função global para negar (colocar junto das outras window.* functions)
window.denyVisitor = function(id) {
  if (!confirm('Tem certeza que deseja negar este visitante?')) return;

  fetch('api.php?action=deny', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `id=${encodeURIComponent(id)}`
  })
  .then(r => r.json())
  .then(j => {
    if (j.ok) {
      showToast('Visita negada com sucesso');
      // atualiza listas (mesma função que já existe)
      renderLists();
    } else {
      showToast(j.msg || 'Erro ao negar visita', 'error');
    }
  })
  .catch(() => showToast('Erro de conexão ao negar visita', 'error'));
};




  window.registerExit = function(id){

    fetch('api.php?action=exit', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:`id=${id}` })

      .then(r=>r.json()).then(j=>{ if (j.ok) { showToast('Saída registrada'); renderLists(); } else showToast(j.msg,'error'); });

  };

  window.removeVisitor = function(id){

    if(!confirm('Remover visitante?')) return;

    // We don't have a specific delete endpoint — but can reuse delete_filtered with council and id? Simpler: call exit to mark left then remove via API? For now implement as "mark left" to avoid deletion.

    // Alternative: implement delete (but server side doesn't have delete single). So call exit as last resource:

    fetch('api.php?action=exit', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:`id=${id}` })

      .then(r=>r.json()).then(j=>{ if (j.ok) { showToast('Visitante marcado como saiu'); renderLists(); } else showToast(j.msg,'error'); });

  };



  // carregar listas

  function renderLists(){

    // pending

    fetch('api.php?action=list_visitors&filter=pending').then(r=>r.json()).then(j=>{

      if (!j.ok) return;

      if (pendingTable) {

        pendingTable.innerHTML='';

        j.data.forEach(v=>{

          const tr = document.createElement('tr');

          tr.innerHTML = `<td>${escapeHtml(v.name)}</td><td>${escapeHtml(v.doc||'')}</td><td>${escapeHtml(v.council)}</td><td>${escapeHtml(v.added_at)}</td>

            <td>

              <button class="ghost" onclick="approveVisitor(${v.id})">Aprovar</button>

              <button class="ghost" onclick="removeVisitor(${v.id})">Remover</button>

            </td>`;

          pendingTable.appendChild(tr);

        });

      }

    });



    // present

    fetch('api.php?action=list_visitors&filter=present').then(r=>r.json()).then(j=>{

      if (!j.ok) return;

      if (presentTable) {

        presentTable.innerHTML='';

        j.data.forEach(v=>{

          const tr = document.createElement('tr');

          tr.innerHTML = `<td>${escapeHtml(v.name)}</td><td>${escapeHtml(v.doc||'')}</td><td>${escapeHtml(v.council)}</td><td>${escapeHtml(v.entered_at||'-')}</td><td>${escapeHtml(v.left_at||'-')}</td>

            <td><button class="ghost" onclick="registerExit(${v.id})">Registrar Saída</button></td>`;

          presentTable.appendChild(tr);

        });

      }

    });



    // report users list

    loadReportUsers();

  }



  // ---------------------- RELATÓRIO ----------------------

  const reportTableBody = document.querySelector('#reportTable tbody');

  const reportUserFilter = document.getElementById('reportUserFilter');

  const reportFrom = document.getElementById('reportFrom');

  const reportTo = document.getElementById('reportTo');



  function loadReportUsers(){

    if (!reportUserFilter) return;

    fetch('api.php?action=get_users').then(r=>r.json()).then(j=>{

      if (!j.ok) return;

      reportUserFilter.innerHTML = '<option value="all">Todos</option>';

      j.data.forEach(u => {

        const opt = document.createElement('option');

        opt.value = u.username; opt.textContent = u.fullName;

        reportUserFilter.appendChild(opt);

      });

    });

  }



  function renderReport(){

    const user = (reportUserFilter && reportUserFilter.value) ? reportUserFilter.value : 'all';

    const from = (reportFrom && reportFrom.value) ? reportFrom.value : '';

    const to = (reportTo && reportTo.value) ? reportTo.value : '';

    // call API list_visitors without filter, but with dates and user

    const qs = `action=list_visitors&from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}&user=${encodeURIComponent(user)}`;

    fetch('api.php?' + qs).then(r=>r.json()).then(j=>{

      if (!j.ok) return;

      if (reportTableBody) {

        reportTableBody.innerHTML = '';

        // j.data is list of visitor rows — we need to expand actions (added/entered/left)

        j.data.forEach(rec => {

          // push row for added

          const added = rec.added_at || rec.added_at;

          const tbody = reportTableBody;

          // action added

          const tr1 = document.createElement('tr');

          tr1.innerHTML = `<td>${escapeHtml(rec.added_at)}</td><td>${escapeHtml(rec.name)}</td><td>${escapeHtml(rec.doc||'')}</td><td>${escapeHtml(rec.council)}</td><td>Adicionado</td><td>${escapeHtml(rec.added_by||'--')}</td>`;

          tbody.appendChild(tr1);

          // entered

          if (rec.entered_at) {

            const tr2 = document.createElement('tr');

            tr2.innerHTML = `<td>${escapeHtml(rec.entered_at)}</td><td>${escapeHtml(rec.name)}</td><td>${escapeHtml(rec.doc||'')}</td><td>${escapeHtml(rec.council)}</td><td>Aprovado/Entrou</td><td>${escapeHtml(rec.approved_by||'--')}</td>`;

            tbody.appendChild(tr2);

          }

          if (rec.left_at) {

            const tr3 = document.createElement('tr');

            tr3.innerHTML = `<td>${escapeHtml(rec.left_at)}</td><td>${escapeHtml(rec.name)}</td><td>${escapeHtml(rec.doc||'')}</td><td>${escapeHtml(rec.council)}</td><td>Saiu</td><td>${escapeHtml(rec.exited_by||'--')}</td>`;

            tbody.appendChild(tr3);

          }

        });

      }

    });

  }



  // limpar relatórios — APAGAR APENAS OS REGISTROS QUE ESTÃO FILTRADOS

  const btnLimpar = document.getElementById('btn-limpar-relatorios');

  if (btnLimpar) {

    btnLimpar.addEventListener('click', () => {

      if (!confirm('Tem certeza que deseja apagar DO BANCO apenas os registros que estão no filtro atual?')) return;

      const user = (reportUserFilter && reportUserFilter.value) ? reportUserFilter.value : 'all';

      const from = (reportFrom && reportFrom.value) ? reportFrom.value : '';

      const to = (reportTo && reportTo.value) ? reportTo.value : '';

      fetch('api.php?action=delete_filtered', {

        method:'POST',

        headers:{'Content-Type':'application/x-www-form-urlencoded'},

        body: `user=${encodeURIComponent(user)}&from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}`

      }).then(r=>r.json()).then(j=>{

        if (j.ok) {

          showToast('Registros filtrados apagados: ' + j.deleted);

          renderLists(); renderReport();

        } else showToast(j.msg || 'Erro','error');

      });

    });

  }



  // ---------------------- VEREADOR (polling & UI) ----------------------
if (document.getElementById('vereadorPendingList')) {

  // Funções que chamam a API (assumem api.php?action=...)
  window.approveVisitor = function(id){
    fetch('api.php?action=approve', {
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body:`id=${encodeURIComponent(id)}`
    }).then(r=>r.json()).then(j=>{
      if (j.ok) {
        showToast('Entrada aprovada', 'success');
        loadVereador(); // atualizar lists
      } else showToast(j.msg || 'Erro ao aprovar','error');
    }).catch(()=> showToast('Erro de comunicação','error'));
  };

  window.denyVisitor = function(id){
    if (!confirm('Tem certeza que deseja negar este visitante?')) return;
    fetch('api.php?action=deny', {
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body:`id=${encodeURIComponent(id)}`
    }).then(r=>r.json()).then(j=>{
      if (j.ok) {
        showToast('Visita negada com sucesso', 'success');
        loadVereador();
      } else showToast(j.msg || 'Erro ao negar','error');
    }).catch(()=> showToast('Erro de comunicação','error'));
  };

  window.registerExit = function(id){
    fetch('api.php?action=exit', {
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body:`id=${encodeURIComponent(id)}`
    }).then(r=>r.json()).then(j=>{
      if (j.ok) {
        showToast('Saída registrada', 'success');
        loadVereador();
      } else showToast(j.msg || 'Erro ao registrar saída','error');
    }).catch(()=> showToast('Erro de comunicação','error'));
  };

  // Renderiza a lista pendente e presente como "cards"
  function loadVereador() {
    // pendentes para este vereador
    fetch('api.php?action=my_pending_for_vereador').then(r=>r.json()).then(j=>{
      if (!j.ok) { console.warn('pendentes vazio'); return; }
      const container = document.getElementById('vereadorPendingList');
      container.innerHTML = '';
      if (j.data.length === 0) {
        container.innerHTML = '<div class="small-muted">Nenhuma visita pendente.</div>';
        return;
      }
      j.data.forEach(v => {
        // v should contain: id, name, doc, added_at, reason
        const row = document.createElement('div');
        row.className = 'notif-row';
        row.setAttribute('data-id', v.id);
        row.innerHTML = `
          <div>
            <h4>${escapeHtml(v.name)}</h4>
            <div class="small-muted">${escapeHtml(v.council || '')}</div>
          </div>
          <div>
            <div class="small-muted">Motivo</div>
            <div>${escapeHtml(v.reason || '-')}</div>
          </div>
          <div>
            <div class="small-muted">Entrada</div>
            <div>${escapeHtml(v.added_at || '-')}</div>
          </div>
          <div class="actions">
            <button class="btn-accept" onclick="approveVisitor(${v.id})">Aceitar</button>
            <button class="btn-deny" onclick="denyVisitor(${v.id})">Negar</button>
          </div>
        `;
        container.appendChild(row);
      });
    }).catch(e=> console.error(e));

    // presentes atribuídos a este vereador
    fetch('api.php?action=my_present_for_vereador').then(r=>r.json()).then(j=>{
      if (!j.ok) return;
      const container = document.getElementById('vereadorPresentList');
      container.innerHTML = '';
      if (j.data.length === 0) {
        container.innerHTML = '<div class="small-muted">Nenhuma pessoa presente.</div>';
        return;
      }
      j.data.forEach(v => {
        const row = document.createElement('div');
        row.className = 'notif-row';
        row.setAttribute('data-id', v.id);
        row.innerHTML = `
          <div>
            <h4>${escapeHtml(v.name)}</h4>
            <div class="small-muted">${escapeHtml(v.council || '')}</div>
          </div>
          <div>
            <div class="small-muted">Motivo</div>
            <div>${escapeHtml(v.reason || '-')}</div>
          </div>
          <div>
            <div class="small-muted">Entrada</div>
            <div>${escapeHtml(v.entered_at || '-')}</div>
          </div>
          <div class="actions">
            <button class="btn-accept" onclick="registerExit(${v.id})">Registrar Saída</button>
          </div>
        `;
        container.appendChild(row);
      });
    }).catch(e=> console.error(e));
  }

  // chama logo e depois a cada 5s (polling)
  loadVereador();
  setInterval(loadVereador, 5000);
}


  // ---------------------- util ----------------------

  function showToast(text, type = 'success'){

    const container = document.getElementById('toast-container');

    if(!container) return;

    const t = document.createElement('div');

    t.className = 'toast ' + (type === 'error' ? 'error' : 'success');

    t.innerHTML = `<strong>${text}</strong>`;

    container.appendChild(t);

    setTimeout(()=>{ t.style.opacity = 0; setTimeout(()=>t.remove(), 400); }, 3000);

  }

  function escapeHtml(s){

    if (s === null || s === undefined) return '';

    return String(s).replace(/[&<>"']/g, function(m){ return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]); });

  }



  // inicial

  renderLists();

  loadReportUsers();

// Mantém o banco ativo — ping a cada 4 minutos
setInterval(() => {
  fetch('keep_alive.php')
    .then(r => r.json())
    .then(j => {
      if (j.ok) console.log('Banco ativo:', j.hora);
    })
    .catch(() => console.warn('Ping falhou'));
}, 240000); // 240000 ms = 4 minutos

});

