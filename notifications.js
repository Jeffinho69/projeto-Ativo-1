// notifications.js
document.addEventListener('DOMContentLoaded', () => {
    // Encontra os elementos do sino na página
    const bellBtn = document.getElementById('notificationBellBtn');
    const dropdown = document.getElementById('notificationDropdown');
    const badge = document.getElementById('notificationBadge');
    const list = document.getElementById('notificationList');

    // Se não achar os elementos, para a execução
    if (!bellBtn || !dropdown || !badge || !list) {
        console.warn('Elementos de notificação não encontrados nesta página.');
        return;
    }

    // --- Lógica para Abrir/Fechar o Dropdown ---
    bellBtn.addEventListener('click', (e) => {
        e.stopPropagation(); // Impede que o clique feche o dropdown imediatamente
        
        const isHidden = dropdown.style.display === 'none';
        dropdown.style.display = isHidden ? 'block' : 'none';
        
        if (isHidden) {
            // Ao abrir, limpa o badge (apenas visualmente)
            // A "leitura" real acontece ao visitar a página (chat) ou aprovar (visitor)
            badge.style.display = 'none';
            badge.textContent = '0';
            
            // (Opcional: Chamar uma API para marcar como "visto")
        }
    });

    // Fecha o dropdown se clicar em qualquer outro lugar da tela
    document.addEventListener('click', (e) => {
        if (!dropdown.contains(e.target) && !bellBtn.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });

    // --- Lógica de Polling (Checagem) ---
    async function pollNotifications() {
        try {
            const res = await fetch('notifications_api.php');
            const data = await res.json();

            if (!data.ok) {
                throw new Error(data.msg || 'Erro ao buscar notificações');
            }

            // 1. Atualiza o BADGE (bolinha vermelha)
            // Só atualiza se o dropdown NÃO estiver visível
            if (dropdown.style.display === 'none') {
                if (data.totalUnread > 0) {
                    badge.textContent = data.totalUnread;
                    badge.style.display = 'block';
                } else {
                    badge.style.display = 'none';
                }
            }

            // 2. Atualiza a LISTA de itens no dropdown
            list.innerHTML = ''; // Limpa a lista atual
            
            if (data.items && data.items.length > 0) {
                data.items.forEach(item => {
                    const div = document.createElement('div');
                    div.className = 'notification-item';
                    div.innerHTML = `
                        <div class="text">${escapeHtml(item.text)}</div>
                        <div class="details">${escapeHtml(item.details)}</div>
                    `;
                    
                    // Adiciona o clique para redirecionar
                    div.addEventListener('click', () => {
                        if (item.link) {
                            window.location.href = item.link;
                        }
                    });
                    list.appendChild(div);
                });
            } else {
                list.innerHTML = '<div class="notification-empty">Nenhuma notificação nova.</div>';
            }

        } catch (error) {
            console.error('Falha no polling de notificações:', error);
        }
    }

    // Função de segurança para evitar XSS
    function escapeHtml(s){ 
        if (s===null||s===undefined) return ''; 
        return String(s).replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); 
    }

    // --- Iniciar o Polling ---
    pollNotifications(); // Chama imediatamente ao carregar a página
    setInterval(pollNotifications, 7000); // E depois checa a cada 7 segundos
});