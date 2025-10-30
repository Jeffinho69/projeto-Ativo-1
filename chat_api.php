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
                AND m.read_status = 'unread') AS unread
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
    // fetch messages between me and contact newer than since_id
    $stmt = $mysqli->prepare("SELECT id, sender, receiver, message, DATE_FORMAT(sent_at, '%d/%m/%Y %H:%i:%s') as sent_at FROM messages WHERE ((sender = ? AND receiver = ?) OR (sender = ? AND receiver = ?)) AND id > ? ORDER BY id ASC");
    $stmt->bind_param('ssssi', $me, $contact, $contact, $me, $since_id);
    if(!$stmt->execute()) json_err('DB fetch erro');
    $res = $stmt->get_result();
    $arr = [];
    $playPing = false;
    while($r = $res->fetch_assoc()){
      $arr[] = $r;
      // if message is from contact and unread -> we'll tell frontend to play ping
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

echo json_encode(['ok'=>false,'msg'=>'Ação desconhecida']);
exit;
