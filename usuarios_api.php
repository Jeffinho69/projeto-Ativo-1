<?php
require_once 'config.php';
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    echo json_encode(['ok'=>false,'msg'=>'Acesso negado']);
    exit;
}

$mysqli = db_connect();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'list':
        $res = $mysqli->query("SELECT id, username, fullName, role FROM users ORDER BY id DESC");
        $arr = $res->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['ok'=>true,'data'=>$arr]);
        break;

    case 'save':
        $id = intval($_POST['id'] ?? 0);
        $u = trim($_POST['username'] ?? '');
        $f = trim($_POST['fullName'] ?? '');
        $p = $_POST['password'] ?? '';
        $r = $_POST['role'] ?? 'recep';
        if (!$u || !$f) { echo json_encode(['ok'=>false,'msg'=>'Campos obrigatórios']); exit; }
        if ($id > 0) {
            if ($p) {
                $hash = password_hash($p, PASSWORD_DEFAULT);
                $stmt = $mysqli->prepare("UPDATE users SET username=?, fullName=?, role=?, password_hash=? WHERE id=?");
                $stmt->bind_param('ssssi',$u,$f,$r,$hash,$id);
            } else {
                $stmt = $mysqli->prepare("UPDATE users SET username=?, fullName=?, role=? WHERE id=?");
                $stmt->bind_param('sssi',$u,$f,$r,$id);
            }
            $ok = $stmt->execute();
            $stmt->close();
            echo json_encode(['ok'=>$ok,'msg'=>'Usuário atualizado']);
        } else {
            if (!$p) { echo json_encode(['ok'=>false,'msg'=>'Senha obrigatória para novo usuário']); exit; }
            $hash = password_hash($p, PASSWORD_DEFAULT);
            $stmt = $mysqli->prepare("INSERT INTO users (username, fullName, role, password_hash) VALUES (?,?,?,?)");
            $stmt->bind_param('ssss',$u,$f,$r,$hash);
            $ok = $stmt->execute();
            $stmt->close();
            echo json_encode(['ok'=>$ok,'msg'=>'Usuário criado']);
        }
        break;

    case 'delete':
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) { echo json_encode(['ok'=>false,'msg'=>'ID inválido']); exit; }
        $stmt = $mysqli->prepare("DELETE FROM users WHERE id=?");
        $stmt->bind_param('i',$id);
        $ok = $stmt->execute();
        $stmt->close();
        echo json_encode(['ok'=>$ok,'msg'=>'Usuário excluído']);
        break;

    default:
        echo json_encode(['ok'=>false,'msg'=>'Ação desconhecida']);
}
