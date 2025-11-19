<?php
// api.php
require_once 'config.php';
header('Content-Type: application/json; charset=utf-8');

// (MODIFICADO) Não podemos exigir login para a 'change_password_public'
$action = $_REQUEST['action'] ?? '';

// Se a ação NÃO for a de trocar senha pública, exija autenticação
if ($action !== 'change_password_public' && empty($_SESSION['user'])) {
    echo json_encode(['ok'=>false,'msg'=>'Não autenticado']); exit;
}

$me = $_SESSION['user'] ?? null; // $me pode ser nulo agora
$mysqli = db_connect();

// ===================================================================
// ================ FUNÇÕES DE NOTIFICAÇÃO ADICIONADAS ===============
// ===================================================================

/**
 * 1. (MODIFICADO) Busca APENAS Recepcionistas
 * Pega o username de todos que devem ser notificados.
 */
function get_notification_recipients($mysqli) {
    $recipients = [];
    // ALTERAÇÃO: Removemos 'OR role='admin'' para notificar apenas a recepção.
    $res = $mysqli->query("SELECT username FROM users WHERE role='recep'");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $recipients[] = $row['username'];
        }
    }
    return $recipients;
}

/**
 * 2. (NOVA FUNÇÃO) Envia uma notificação via sistema de chat
 * Usamos a tabela 'messages' que o 'chat.php' já usa
 */
function send_system_notification($mysqli, $sender_username, $receiver_username, $message_text) {
    // Evita que o usuário envie notificação para si mesmo (ex: recepção aprovando)
    if ($sender_username === $receiver_username) {
        return;
    }
    
    // Insere a mensagem na tabela de chat
    $stmt = $mysqli->prepare("INSERT INTO messages (sender, receiver, message, sent_at, read_status) VALUES (?, ?, ?, NOW(), 'unread')");
    $stmt->bind_param('sss', $sender_username, $receiver_username, $message_text);
    $stmt->execute();
    $stmt->close();
}

// ===================================================================
// =================== FIM DAS FUNÇÕES ADICIONADAS ===================
// ===================================================================


function json_die($arr){ echo json_encode($arr); exit; }

switch ($action) {
  case 'add_visitor':
    // recepção adiciona visitante
    if ($me['role'] !== 'recep' && $me['role'] !== 'admin') json_die(['ok'=>false,'msg'=>'Acesso negado']);
    $name = trim($_POST['name'] ?? '');
    $doc = trim($_POST['doc'] ?? '');
    $council = trim($_POST['council'] ?? '');
    $reason = trim($_POST['reason'] ?? '');
    if ($name === '' || $council === '' || $reason === '') json_die(['ok'=>false,'msg'=>'Campos obrigatórios']);
    $stmt = $mysqli->prepare("INSERT INTO visitors (name, doc, council, reason, added_by) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('sssss', $name, $doc, $council, $reason, $me['username']);
    $ok = $stmt->execute();
    $stmt->close();
    json_die(['ok'=>$ok, 'id'=>$mysqli->insert_id]);
    break;

  case 'list_visitors':
    // (Código original sem alteração)
    $filter = $_GET['filter'] ?? 'all'; // pending/present/all
    $from = $_GET['from'] ?? null;
    $to = $_GET['to'] ?? null;
    $userFilter = $_GET['user'] ?? 'all';

    // (MODIFICADO) Esta consulta agora pega TUDO, incluindo o 'reason'
    $sql = "SELECT v.* FROM visitors v WHERE 1=1";
    $params = [];
    $types = '';

    if ($filter === 'pending') { $sql .= " AND v.status='waiting'"; }
    elseif ($filter === 'present') { $sql .= " AND v.status='inside'"; }

    if ($userFilter !== 'all') { $sql .= " AND (v.added_by = ?)"; $types .= 's'; $params[] = $userFilter; }

    if ($from) { $sql .= " AND v.added_at >= ?"; $types .= 's'; $params[] = $from . " 00:00:00"; }
    if ($to) { $sql .= " AND v.added_at <= ?"; $types .= 's'; $params[] = $to . " 23:59:59"; }

    $sql .= " ORDER BY v.added_at DESC LIMIT 1000";

    if ($stmt = $mysqli->prepare($sql)) {
        if ($types !== '') $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = $res->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        json_die(['ok'=>true,'data'=>$rows]);
    } else {
        json_die(['ok'=>false,'msg'=>$mysqli->error]);
    }
    break;

  case 'approve':
    // aprovar entrada (recepção ou vereador)
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) json_die(['ok'=>false,'msg'=>'ID inválido']);
    
    if ($me['role'] === 'vereador') {
      $stmt = $mysqli->prepare("SELECT council FROM visitors WHERE id = ?");
      $stmt->bind_param('i', $id); $stmt->execute(); $r = $stmt->get_result()->fetch_assoc(); $stmt->close();
      if (!$r || $r['council'] !== $me['fullName']) json_die(['ok'=>false,'msg'=>'Não autorizado para aprovar este visitante']);
    }
    $now = date('Y-m-d H:i:s');
    $stmt = $mysqli->prepare("UPDATE visitors SET status='inside', entered_at = ?, approved_by = ? WHERE id = ?");
    $stmt->bind_param('ssi', $now, $me['username'], $id);
    $ok = $stmt->execute();
    
    // (CÓDIGO DE NOTIFICAÇÃO ADICIONADO)
    if ($ok) {
        $sender_username = $me['username'];
        $sender_fullName = $me['fullName'] ?? $sender_username;
        
        $visitor_name = 'Visitante';
        $stmt_v = $mysqli->prepare("SELECT name FROM visitors WHERE id = ?");
        $stmt_v->bind_param('i', $id);
        if ($stmt_v->execute()) {
            if ($row_v = $stmt_v->get_result()->fetch_assoc()) {
                $visitor_name = $row_v['name'];
            }
        }
        $stmt_v->close();

        // Envia a notificação para todos os admins/recepcionistas
        $message_text = "✅ Entrada Aprovada: " . htmlspecialchars($visitor_name) . " (Por: $sender_fullName)";
        $recipients = get_notification_recipients($mysqli);
        foreach ($recipients as $username) {
            send_system_notification($mysqli, $sender_username, $username, $message_text);
        }
    }
    
    $stmt->close();
    json_die(['ok'=>$ok]);
    break;

    case 'deny':
    // negar visita (recepção ou vereador)
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) json_die(['ok'=>false,'msg'=>'ID inválido']);

    if ($me['role'] === 'vereador') {
        $stmt = $mysqli->prepare("SELECT council, status FROM visitors WHERE id = ?");
        $stmt->bind_param('i', $id); $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) json_die(['ok'=>false,'msg'=>'Registro não encontrado']);
        if ($row['council'] !== $me['fullName']) json_die(['ok'=>false,'msg'=>'Não autorizado para negar este visitante']);
        if ($row['status'] !== 'waiting') json_die(['ok'=>false,'msg'=>'Somente visitas em espera podem ser negadas']);
    }

    if (!($me['role'] === 'admin' || $me['role'] === 'recep' || $me['role'] === 'vereador')) {
        json_die(['ok'=>false,'msg'=>'Sem permissão para negar visite']);
    }

    $now = date('Y-m-d H:i:s');
    $stmt = $mysqli->prepare("UPDATE visitors SET status = 'left', left_at = ?, exited_by = ? WHERE id = ?");
    $stmt->bind_param('ssi', $now, $me['username'], $id);
    $ok = $stmt->execute();
    
    // (CÓDIGO DE NOTIFICAÇÃO ADICIONADO)
    if ($ok) {
        $sender_username = $me['username'];
        $sender_fullName = $me['fullName'] ?? $sender_username;
        
        $visitor_name = 'Visitante';
        $stmt_v = $mysqli->prepare("SELECT name FROM visitors WHERE id = ?");
        $stmt_v->bind_param('i', $id);
        if ($stmt_v->execute()) {
            if ($row_v = $stmt_v->get_result()->fetch_assoc()) {
                $visitor_name = $row_v['name'];
            }
        }
        $stmt_v->close();

        $message_text = "❌ Visita Negada: " . htmlspecialchars($visitor_name) . " (Por: $sender_fullName)";
        $recipients = get_notification_recipients($mysqli);
        foreach ($recipients as $username) {
            send_system_notification($mysqli, $sender_username, $username, $message_text);
        }
    }
    
    $stmt->close();
    json_die(['ok'=>$ok]);
    break;

  case 'exit':
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) json_die(['ok'=>false,'msg'=>'ID inválido']);
    
    $stmt = $mysqli->prepare("SELECT approved_by, council FROM visitors WHERE id = ?");
    $stmt->bind_param('i',$id); $stmt->execute(); $r = $stmt->get_result()->fetch_assoc(); $stmt->close();
    if (!$r) json_die(['ok'=>false,'msg'=>'Registro não encontrado']);
    
    if (!($me['role']==='admin' || $me['role']==='recep' || ($me['role']==='vereador' && $r['council']===$me['fullName']) || $r['approved_by']===$me['username'])) {
      json_die(['ok'=>false,'msg'=>'Sem permissão para registrar saída']);
    }
    $now = date('Y-m-d H:i:s');
    $stmt = $mysqli->prepare("UPDATE visitors SET status='left', left_at = ?, exited_by = ? WHERE id = ?");
    $stmt->bind_param('ssi', $now, $me['username'], $id);
    $ok = $stmt->execute();
    
    // (CÓDIGO DE NOTIFICAÇÃO ADICIONADO)
     if ($ok) {
        $sender_username = $me['username'];
        $sender_fullName = $me['fullName'] ?? $sender_username;
        
        $visitor_name = 'Visitante';
        $stmt_v = $mysqli->prepare("SELECT name FROM visitors WHERE id = ?");
        $stmt_v->bind_param('i', $id);
        if ($stmt_v->execute()) {
            if ($row_v = $stmt_v->get_result()->fetch_assoc()) {
                $visitor_name = $row_v['name'];
            }
        }
        $stmt_v->close();

        $message_text = "🚪 Saída Registrada: " . htmlspecialchars($visitor_name) . " (Por: $sender_fullName)";
        $recipients = get_notification_recipients($mysqli);
        foreach ($recipients as $username) {
            send_system_notification($mysqli, $sender_username, $username, $message_text);
        }
    }
    
    $stmt->close();
    json_die(['ok'=>$ok]);
    break;

  case 'delete_filtered':
    // (Código original sem alteração)
    if ($me['role'] !== 'recep' && $me['role'] !== 'admin') json_die(['ok'=>false,'msg'=>'Acesso negado']);
    $userFilter = $_POST['user'] ?? 'all';
    $from = $_POST['from'] ?? null;
    $to = $_POST['to'] ?? null;
    $council = $_POST['council'] ?? null;

    $sql = "DELETE FROM visitors WHERE 1=1";
    $types = ''; $params=[];
    if ($userFilter !== 'all') { $sql .= " AND added_by = ?"; $types.='s'; $params[] = $userFilter; }
    if ($from) { $sql .= " AND added_at >= ?"; $types.='s'; $params[] = $from . " 00:00:00"; }
    if ($to) { $sql .= " AND added_at <= ?"; $types.='s'; $params[] = $to . " 23:59:59"; }
    if ($council) { $sql .= " AND council = ?"; $types.='s'; $params[] = $council; }

    if ($stmt = $mysqli->prepare($sql)) {
      if ($types !== '') $stmt->bind_param($types, ...$params);
      $ok = $stmt->execute();
      $affected = $stmt->affected_rows;
      $stmt->close();
      json_die(['ok'=>true,'deleted'=>$affected]);
    } else {
      json_die(['ok'=>false,'msg'=>$mysqli->error]);
    }
    break;

  case 'get_users':
    // (Código original sem alteração)
    $res = $mysqli->query("SELECT username, fullName FROM users ORDER BY fullName");
    $arr = $res->fetch_all(MYSQLI_ASSOC);
    json_die(['ok'=>true,'data'=>$arr]);
    break;

  case 'get_receptionists':
    // (Código original sem alteração)
    $res = $mysqli->query("SELECT username, fullName FROM users WHERE role='recep' OR role='admin' ORDER BY fullName");
    $arr = $res->fetch_all(MYSQLI_ASSOC);
    json_die(['ok'=>true,'data'=>$arr]);
    break;

  case 'my_pending_for_vereador':
    // (Código original sem alteração)
    $stmt = $mysqli->prepare("SELECT * FROM visitors WHERE status='waiting' AND council = ? ORDER BY added_at DESC");
    $stmt->bind_param('s', $me['fullName']);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    json_die(['ok'=>true,'data'=>$rows]);
    break;

  case 'my_present_for_vereador':
    // (Código original sem alteração)
    $stmt = $mysqli->prepare("SELECT * FROM visitors WHERE status='inside' AND council = ? ORDER BY entered_at DESC");
    $stmt->bind_param('s', $me['fullName']);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    json_die(['ok'=>true,'data'=>$rows]);
    break;

  // =========================================================
  // ================ ADICIONADO: TROCAR SENHA (PÚBLICO) =====
  // =========================================================
  case 'change_password_public':
    $username = $_POST['username'] ?? '';
    $current_pass = $_POST['current_password'] ?? '';
    $new_pass = $_POST['new_password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';

    if (empty($username) || empty($current_pass) || empty($new_pass) || empty($confirm_pass)) {
        json_die(['ok'=>false,'msg'=>'Preencha todos os campos.']);
    }
    if ($new_pass !== $confirm_pass) {
        json_die(['ok'=>false,'msg'=>'A nova senha e a confirmação não batem.']);
    }

    // Buscar a senha atual do usuário
    // ATENÇÃO: Verifique o nome da sua coluna de senha (ex: password_hash, password, etc)
    $stmt = $mysqli->prepare("SELECT password_hash FROM users WHERE username = ?");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();

    if (!$row) {
        json_die(['ok'=>false,'msg'=>'Usuário não encontrado.']);
    }
    
    // Pega o hash da senha (seja qual for o nome da coluna)
    $current_hash = $row['password_hash'] ?? ($row['password'] ?? '');
    
    if (empty($current_hash)) {
         json_die(['ok'=>false,'msg'=>'Erro: Conta de usuário sem hash de senha.']);
    }

    // Verificar se a senha atual bate (usando password_verify)
    if (!password_verify($current_pass, $current_hash)) {
        json_die(['ok'=>false,'msg'=>'Senha atual incorreta.']);
    }

    // Se bateu, hashear e atualizar a nova senha
    $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
    
    // ATENÇÃO: Atualize a mesma coluna que você buscou (ex: 'password_hash')
    $stmt_update = $mysqli->prepare("UPDATE users SET password_hash = ? WHERE username = ?");
    $stmt_update->bind_param('ss', $new_hash, $username);
    $ok = $stmt_update->execute();
    $stmt_update->close();

    if ($ok) {
        json_die(['ok'=>true,'msg'=>'Senha alterada com sucesso!']);
    } else {
        json_die(['ok'=>false,'msg'=>'Erro ao atualizar a senha no banco.']);
    }
    break;
  // =========================================================

  default:
    json_die(['ok'=>false,'msg'=>'Ação desconhecida']);

}
?>