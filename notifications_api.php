<?php
// notifications_api.php (Versão CORRIGIDA)
require_once 'config.php'; // Isso já inclui o session_start()
header('Content-Type: application/json; charset=utf-8');

// Verifica se o usuário está logado
if (empty($_SESSION['user'])) {
    echo json_encode(['ok'=>false,'msg'=>'Não autenticado']);
    exit;
}

$user = $_SESSION['user'];
$role = $user['role'];
$me = $user['username'];
// Vamos assumir que o 'destino' do visitante (council) é o 'fullName' do vereador.
// Se for o 'username', troque $myCouncilName = $user['username'];
$myCouncilName = $user['fullName']; 

$mysqli = db_connect();

$notifications = []; // Array para guardar os textos das notificações
$totalUnread = 0;    // Contador total de itens não lidos

// 1. Notificações de CHAT (Para todos)
//    (Corrigido para usar a tabela 'messages' e 'read_status')
$stmt_chat_total = $mysqli->prepare("SELECT COUNT(*) cnt FROM messages WHERE receiver = ? AND read_status = 'unread'");
$stmt_chat_total->bind_param('s', $me);
$stmt_chat_total->execute();
$chat_count = $stmt_chat_total->get_result()->fetch_assoc()['cnt'] ?? 0;
$totalUnread += $chat_count;

if ($chat_count > 0) {
    // Pega as 3 mensagens mais recentes não lidas para o dropdown
    $stmt_chat_items = $mysqli->prepare("SELECT sender, message FROM messages WHERE receiver = ? AND read_status = 'unread' ORDER BY sent_at DESC LIMIT 3");
    $stmt_chat_items->bind_param('s', $me);
    $stmt_chat_items->execute();
    $res_chat = $stmt_chat_items->get_result();
    
    while ($row = $res_chat->fetch_assoc()) {
        $notifications[] = [
            'type' => 'chat',
            'text' => 'Nova mensagem de: ' . htmlspecialchars($row['sender']),
            'details' => htmlspecialchars(substr($row['message'], 0, 40)) . '...',
            'link' => 'chat.php' // Link para a página de chat
        ];
    }
}


// 2. Notificações de VISITANTES (Depende da Função)
//    (Esta parte estava correta e usa a tabela 'visitors')

if ($role === 'admin' || $role === 'recep') {
    // Admin e Recepção veem visitantes PENDENTES
    $stmt_visitor_total = $mysqli->prepare("SELECT COUNT(*) cnt FROM visitors WHERE status = 'pending'");
    $stmt_visitor_total->execute();
    $visitor_count = $stmt_visitor_total->get_result()->fetch_assoc()['cnt'] ?? 0;
    $totalUnread += $visitor_count;

    if ($visitor_count > 0) {
        $stmt_visitor_items = $mysqli->prepare("SELECT name, council FROM visitors WHERE status = 'pending' ORDER BY added_at DESC LIMIT 3");
        $stmt_visitor_items->execute();
        $res_visitor = $stmt_visitor_items->get_result();

        while ($row = $res_visitor->fetch_assoc()) {
             $notifications[] = [
                'type' => 'visitor_pending',
                'text' => 'Visitante aguardando: ' . htmlspecialchars($row['name']),
                'details' => 'Destino: ' . htmlspecialchars($row['council']),
                'link' => 'recepcao.php' // Link para a recepção (poderia ser #pending)
            ];
        }
    }

} elseif ($role === 'vereador') {
    // Vereador vê aprovações PENDENTES para ele
    $stmt_visitor_total = $mysqli->prepare("SELECT COUNT(*) cnt FROM visitors WHERE status = 'pending' AND council = ?");
    $stmt_visitor_total->bind_param('s', $myCouncilName);
    $stmt_visitor_total->execute();
    $visitor_count = $stmt_visitor_total->get_result()->fetch_assoc()['cnt'] ?? 0;
    $totalUnread += $visitor_count;

    if ($visitor_count > 0) {
        $stmt_visitor_items = $mysqli->prepare("SELECT name, reason FROM visitors WHERE status = 'pending' AND council = ? ORDER BY added_at DESC LIMIT 3");
        $stmt_visitor_items->bind_param('s', $myCouncilName);
        $stmt_visitor_items->execute();
        $res_visitor = $stmt_visitor_items->get_result();

        while ($row = $res_visitor->fetch_assoc()) {
             $notifications[] = [
                'type' => 'visitor_approval',
                'text' => 'Aprovação pendente: ' . htmlspecialchars($row['name']),
                'details' => 'Motivo: ' . htmlspecialchars($row['reason'] ?? '-'),
                'link' => 'vereador.php' // Link para o painel do vereador
            ];
        }
    }
}

// Retorna o JSON final
echo json_encode([
    'ok' => true,
    'totalUnread' => $totalUnread,
    'items' => array_slice($notifications, 0, 7) // Retorna no máximo 7 notificações misturadas
]);
exit;
?>