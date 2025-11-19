<?php
// chat_api.php
require_once 'config.php';
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user'])) {
    echo json_encode(['ok'=>false,'msg'=>'Não autenticado']);
    exit;
}
$me = $_SESSION['user']['username'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

$mysqli = db_connect();

function json_ok($data=[]){ echo json_encode(array_merge(['ok'=>true], $data)); exit; }
function json_err($msg='Erro'){ echo json_encode(['ok'=>false,'msg'=>$msg]); exit; }

if ($action === 'list_contacts') {
    // lista todos usuários do sistema, exceto o próprio
    $stmt = $mysqli->prepare("
        SELECT 
            u.username,
            u.fullName,
            (SELECT COUNT(*) FROM messages m 
                WHERE m.receiver = ? AND m.sender = u.username 
                AND m.read_status = 'unread'
                AND m.deleted_by_receiver = 0) AS unread
        FROM users u
        WHERE u.username != ?
        ORDER BY u.fullName ASC
    ");
    $stmt->bind_param('ss', $me, $me);
    if(!$stmt->execute()) json_err('DB erro');
    $res = $stmt->get_result();
    $out = [];
    while($row = $res->fetch_assoc()) {
        $out[] = [
            'username' => $row['username'],
            'fullName' => $row['fullName'] ?: $row['username'],
            'unread' => intval($row['unread'])
        ];
    }
    json_ok(['data'=>$out]);
}


if ($action === 'fetch') {
    $contact = $_GET['contact'] ?? '';
    $since_id = intval($_GET['since_id'] ?? 0);
    if (!$contact) json_err('Contato inválido');

    // ATUALIZADO: Agora só busca mensagens que não foram apagadas pelo usuário
    $stmt = $mysqli->prepare("
        SELECT id, sender, receiver, message, DATE_FORMAT(sent_at, '%d/%m/%Y %H:%i:%s') as sent_at 
        FROM messages 
        WHERE id > ? AND (
            (sender = ? AND receiver = ? AND deleted_by_sender = 0) OR 
            (sender = ? AND receiver = ? AND deleted_by_receiver = 0)
        )
        ORDER BY id ASC");
    $stmt->bind_param('issss', $since_id, $me, $contact, $contact, $me);
    
    if(!$stmt->execute()) json_err('DB fetch erro');
    $res = $stmt->get_result();
    $arr = [];
    $playPing = false;
    while($r = $res->fetch_assoc()){
      $arr[] = $r;
      if ($r['sender'] === $contact) $playPing = true;
    }
    json_ok(['data'=>$arr, 'since_id'=>$since_id, 'playPing'=>$playPing]);
}

if ($action === 'send') {
    $receiver = $_POST['receiver'] ?? '';
    $message = trim($_POST['message'] ?? '');
    if (!$receiver || $message === '') json_err('Dados inválidos');
    $stmt = $mysqli->prepare("INSERT INTO messages (sender, receiver, message, sent_at, read_status) VALUES (?, ?, ?, NOW(), 'unread')");
    $stmt->bind_param('sss', $me, $receiver, $message);
    if(!$stmt->execute()) json_err('Erro ao inserir');
    json_ok(['id' => $mysqli->insert_id]);
}

if ($action === 'mark_read') {
    $contact = $_POST['contact'] ?? '';
    if (!$contact) json_err('Contato inválido');
    $stmt = $mysqli->prepare("UPDATE messages SET read_status = 'read' WHERE sender = ? AND receiver = ? AND read_status = 'unread'");
    $stmt->bind_param('ss', $contact, $me);
    if(!$stmt->execute()) json_err('Erro ao marcar lido');
    json_ok(['updated' => $stmt->affected_rows]);
}


// =========================================================
// ================ Apagar Mensagem (p/ mim) ===============
// =========================================================
if ($action === 'delete_message') {
    $id = intval($_POST['id'] ?? 0);
    if (!$id) json_err('ID inválido');

    $stmt = $mysqli->prepare("SELECT sender, receiver FROM messages WHERE id = ?");
    $stmt->bind_param('i', $id);
    if (!$stmt->execute()) json_err('Erro DB');
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();

    if (!$row) json_err('Mensagem não encontrada');

    // Decide qual coluna 'deleted_by_' atualizar
    if ($row['sender'] === $me) {
        $stmt_del = $mysqli->prepare("UPDATE messages SET deleted_by_sender = 1 WHERE id = ?");
    } elseif ($row['receiver'] === $me) {
        $stmt_del = $mysqli->prepare("UPDATE messages SET deleted_by_receiver = 1 WHERE id = ?");
    } else {
        json_err('Sem permissão'); // Não é o remetente nem o destinatário
    }
    
    $stmt_del->bind_param('i', $id);
    $ok = $stmt_del->execute();
    $stmt_del->close();
    
    json_ok(['deleted' => $ok]);
}

// =========================================================
// ================ Apagar Conversa (p/ mim) ===============
// =========================================================
if ($action === 'delete_conversation') {
    $contact = $_POST['contact'] ?? '';
    if (!$contact) json_err('Contato inválido');

    // Marca como deletado por mim (quando eu enviei)
    $stmt_sent = $mysqli->prepare("UPDATE messages SET deleted_by_sender = 1 WHERE sender = ? AND receiver = ?");
    $stmt_sent->bind_param('ss', $me, $contact);
    $stmt_sent->execute();
    $stmt_sent->close();
    
    // Marca como deletado por mim (quando eu recebi)
    $stmt_rcv = $mysqli->prepare("UPDATE messages SET deleted_by_receiver = 1 WHERE receiver = ? AND sender = ?");
    $stmt_rcv->bind_param('ss', $me, $contact);
    $stmt_rcv->execute();
    $stmt_rcv->close();

    json_ok(['cleared' => true]);
}

// =========================================================
// ================ ADICIONADO: Apagar Msg p/ Todos ========
// =========================================================
if ($action === 'delete_message_everyone') {
    $id = intval($_POST['id'] ?? 0);
    if (!$id) json_err('ID inválido');

    // Verifica se EU sou o remetente
    $stmt = $mysqli->prepare("SELECT sender FROM messages WHERE id = ?");
    $stmt->bind_param('i', $id);
    if (!$stmt->execute()) json_err('Erro DB');
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) json_err('Mensagem não encontrada');
    if ($row['sender'] !== $me) {
        json_err('Você só pode apagar suas próprias mensagens para todos.');
    }

    // Se for o remetente, apaga DE VERDADE (hard delete)
    $stmt_del = $mysqli->prepare("DELETE FROM messages WHERE id = ? AND sender = ?");
    $stmt_del->bind_param('is', $id, $me);
    $ok = $stmt_del->execute();
    $stmt_del->close();
    
    json_ok(['deleted' => $ok]);
}

// =========================================================
// ================ ADICIONADO: Apagar Conv. p/ Todos ======
// =========================================================
if ($action === 'delete_conversation_everyone') {
    $contact = $mysqli->real_escape_string($_POST['contact'] ?? '');
    if (!$contact) json_err('Contato inválido');

    // Apaga DE VERDADE (hard delete) todas as mensagens entre os dois
    $stmt = $mysqli->prepare("DELETE FROM messages WHERE (sender = ? AND receiver = ?) OR (sender = ? AND receiver = ?)");
    $stmt->bind_param('ssss', $me, $contact, $contact, $me);
    $stmt->execute();
    $stmt->close();

    json_ok(['cleared' => true]);
}
// =========================================================
// =================== FIM DAS ADIÇÕES =====================
// =========================================================


echo json_encode(['ok'=>false,'msg'=>'Ação desconhecida']);
exit;