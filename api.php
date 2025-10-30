<?php
// api.php
require_once 'config.php';
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user'])) {
    echo json_encode(['ok'=>false,'msg'=>'Não autenticado']); exit;
}
$me = $_SESSION['user'];
$mysqli = db_connect();

$action = $_REQUEST['action'] ?? '';

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
    // lista visitantes (opcional filters)
    $filter = $_GET['filter'] ?? 'all'; // pending/present/all
    $from = $_GET['from'] ?? null;
    $to = $_GET['to'] ?? null;
    $userFilter = $_GET['user'] ?? 'all';

    $sql = "SELECT * FROM visitors WHERE 1=1";
    $params = [];
    $types = '';

    if ($filter === 'pending') { $sql .= " AND status='waiting'"; }
    elseif ($filter === 'present') { $sql .= " AND status='inside'"; }

    if ($userFilter !== 'all') { $sql .= " AND (added_by = ?)"; $types .= 's'; $params[] = $userFilter; }

    if ($from) { $sql .= " AND added_at >= ?"; $types .= 's'; $params[] = $from . " 00:00:00"; }
    if ($to) { $sql .= " AND added_at <= ?"; $types .= 's'; $params[] = $to . " 23:59:59"; }

    $sql .= " ORDER BY added_at DESC LIMIT 1000";

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
    // se for vereador, só pode aprovar visitantes destinados ao seu name
    // BUT we stored council as name; so we check that when role=vereador
    if ($me['role'] === 'vereador') {
      $stmt = $mysqli->prepare("SELECT council FROM visitors WHERE id = ?");
      $stmt->bind_param('i', $id); $stmt->execute(); $r = $stmt->get_result()->fetch_assoc(); $stmt->close();
      if (!$r || $r['council'] !== $me['fullName']) json_die(['ok'=>false,'msg'=>'Não autorizado para aprovar este visitante']);
    }
    $now = date('Y-m-d H:i:s');
    $stmt = $mysqli->prepare("UPDATE visitors SET status='inside', entered_at = ?, approved_by = ? WHERE id = ?");
    $stmt->bind_param('ssi', $now, $me['username'], $id);
    $ok = $stmt->execute();
    $stmt->close();
    json_die(['ok'=>$ok]);
    break;

    case 'deny':
    // negar visita (recepção ou vereador)
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) json_die(['ok'=>false,'msg'=>'ID inválido']);

    // se for vereador, só pode negar visitantes destinados ao seu fullName
    if ($me['role'] === 'vereador') {
        $stmt = $mysqli->prepare("SELECT council, status FROM visitors WHERE id = ?");
        $stmt->bind_param('i', $id); $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) json_die(['ok'=>false,'msg'=>'Registro não encontrado']);
        if ($row['council'] !== $me['fullName']) json_die(['ok'=>false,'msg'=>'Não autorizado para negar este visitante']);
        // opcional: não negar se já estiver inside/left
        if ($row['status'] !== 'waiting') json_die(['ok'=>false,'msg'=>'Somente visitas em espera podem ser negadas']);
    }

    // quem pode negar: admin, recep, ou vereador responsável (já checado)
    if (!($me['role'] === 'admin' || $me['role'] === 'recep' || $me['role'] === 'vereador')) {
        json_die(['ok'=>false,'msg'=>'Sem permissão para negar visite']);
    }

    $now = date('Y-m-d H:i:s');
    // Observação: sua tabela status tem 'waiting','inside','left' — 
    // aqui definimos 'left' (como "não entrou") e guardamos exited_by para registro
    $stmt = $mysqli->prepare("UPDATE visitors SET status = 'left', left_at = ?, exited_by = ? WHERE id = ?");
    $stmt->bind_param('ssi', $now, $me['username'], $id);
    $ok = $stmt->execute();
    $stmt->close();
    json_die(['ok'=>$ok]);
    break;

  case 'exit':
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) json_die(['ok'=>false,'msg'=>'ID inválido']);
    // somente quem aprovou OU vereador responsável pode registrar saida (simple rule)
    $stmt = $mysqli->prepare("SELECT approved_by, council FROM visitors WHERE id = ?");
    $stmt->bind_param('i',$id); $stmt->execute(); $r = $stmt->get_result()->fetch_assoc(); $stmt->close();
    if (!$r) json_die(['ok'=>false,'msg'=>'Registro não encontrado']);
    // autorizações: admin, recep, or if vereador and council==me.fullName OR if approved_by==me.username
    if (!($me['role']==='admin' || $me['role']==='recep' || ($me['role']==='vereador' && $r['council']===$me['fullName']) || $r['approved_by']===$me['username'])) {
      json_die(['ok'=>false,'msg'=>'Sem permissão para registrar saída']);
    }
    $now = date('Y-m-d H:i:s');
    $stmt = $mysqli->prepare("UPDATE visitors SET status='left', left_at = ?, exited_by = ? WHERE id = ?");
    $stmt->bind_param('ssi', $now, $me['username'], $id);
    $ok = $stmt->execute();
    $stmt->close();
    json_die(['ok'=>$ok]);
    break;

  case 'delete_filtered':
    // apagar apenas os registros que estão sendo exibidos/filtreados
    if ($me['role'] !== 'recep' && $me['role'] !== 'admin') json_die(['ok'=>false,'msg'=>'Acesso negado']);
    // Accept filters via POST: user, from, to, council (optional)
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
    // lista usuários para filtro
    $res = $mysqli->query("SELECT username, fullName FROM users ORDER BY fullName");
    $arr = $res->fetch_all(MYSQLI_ASSOC);
    json_die(['ok'=>true,'data'=>$arr]);
    break;

  case 'my_pending_for_vereador':
    // para a tela do vereador — retorna waiting visitors whose council == my fullName
    $stmt = $mysqli->prepare("SELECT * FROM visitors WHERE status='waiting' AND council = ? ORDER BY added_at DESC");
    $stmt->bind_param('s', $me['fullName']);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    json_die(['ok'=>true,'data'=>$rows]);
    break;

  case 'my_present_for_vereador':
    $stmt = $mysqli->prepare("SELECT * FROM visitors WHERE status='inside' AND council = ? ORDER BY entered_at DESC");
    $stmt->bind_param('s', $me['fullName']);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    json_die(['ok'=>true,'data'=>$rows]);
    break;

  default:
    json_die(['ok'=>false,'msg'=>'Ação desconhecida']);



}