<?php
// chat_api.php
require_once 'config.php';
header('Content-Type: application/json; charset=utf-8');

$mysqli = db_connect();
session_start();

if (empty($_SESSION['user'])) {
    echo json_encode(['ok'=>false,'msg'=>'Not logged']);
    exit;
}
$user = $_SESSION['user'];
$me = $mysqli->real_escape_string($user['username']);

$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action'])?$_POST['action']:'');

function json($a){ echo json_encode($a); exit; }

if ($action === 'list_contacts') {
    // list all other users (simples)
    $res = $mysqli->query("SELECT username, fullName, role FROM users WHERE username != '{$me}'");
    $out = [];
    while($r = $res->fetch_assoc()){
        $out[] = $r;
    }
    json(['ok'=>true,'data'=>$out]);
}

if ($action === 'get_conversation') {
    $with = $mysqli->real_escape_string($_GET['with'] ?? '');
    if (!$with) json(['ok'=>false,'msg'=>'missing with']);
    // select messages not deleted by the requesting party
    $sql = "SELECT id,sender,receiver,message,created_at,is_read FROM chat_messages
            WHERE (sender='{$me}' AND receiver='{$with}' AND deleted_by_sender=0)
               OR (sender='{$with}' AND receiver='{$me}' AND deleted_by_receiver=0)
            ORDER BY created_at ASC";
    $res = $mysqli->query($sql);
    $rows = [];
    while($r = $res->fetch_assoc()) $rows[] = $r;
    // mark messages as read (those received by me)
    $mysqli->query("UPDATE chat_messages SET is_read=1 WHERE receiver='{$me}' AND sender='{$with}'");
    json(['ok'=>true,'data'=>$rows]);
}

if ($action === 'send') {
    $to = $mysqli->real_escape_string($_POST['to'] ?? '');
    $msg = trim($_POST['message'] ?? '');
    if (!$to || $msg === '') json(['ok'=>false,'msg'=>'invalid']);
    $stmt = $mysqli->prepare("INSERT INTO chat_messages (sender,receiver,message) VALUES (?,?,?)");
    $stmt->bind_param('sss',$me,$to,$msg);
    $ok = $stmt->execute();
    if (!$ok) json(['ok'=>false,'msg'=>$stmt->error]);
    json(['ok'=>true,'id'=>$mysqli->insert_id]);
}

if ($action === 'poll_unread') {
    // total unread for me
    $res = $mysqli->query("SELECT COUNT(*) cnt FROM chat_messages WHERE receiver='{$me}' AND is_read=0 AND deleted_by_receiver=0");
    $cnt = $res->fetch_assoc()['cnt'] ?? 0;
    json(['ok'=>true,'unread'=>intval($cnt)]);
}

if ($action === 'recent_preview') {
    // return last message per contact for sidebar preview
    $sql = "SELECT m.* FROM chat_messages m
            JOIN (
              SELECT IF(sender='{$me}', receiver, sender) other, MAX(created_at) mx
              FROM chat_messages
              WHERE (sender='{$me}' OR receiver='{$me}')
                AND ((sender='{$me}' AND deleted_by_sender=0) OR (receiver='{$me}' AND deleted_by_receiver=0))
              GROUP BY other
            ) t ON ((m.sender='{$me}' AND m.receiver=t.other) OR (m.receiver='{$me}' AND m.sender=t.other)) AND m.created_at = t.mx
            ORDER BY m.created_at DESC
            LIMIT 50";
    $res = $mysqli->query($sql);
    $rows = [];
    while($r = $res->fetch_assoc()) {
        $other = ($r['sender']===$me) ? $r['receiver'] : $r['sender'];
        $rows[] = ['other'=>$other,'message'=>$r['message'],'created_at'=>$r['created_at'],'from'=>$r['sender']];
    }
    json(['ok'=>true,'data'=>$rows]);
}

if ($action === 'delete_message') {
    $id = intval($_POST['id'] ?? 0);
    if (!$id) json(['ok'=>false,'msg'=>'missing id']);
    // check message exists and sender/receiver
    $res = $mysqli->query("SELECT sender,receiver FROM chat_messages WHERE id={$id}");
    if (!$res || $res->num_rows===0) json(['ok'=>false,'msg'=>'no message']);
    $row = $res->fetch_assoc();
    if ($row['sender'] === $me) {
        $mysqli->query("UPDATE chat_messages SET deleted_by_sender=1 WHERE id={$id}");
    } elseif ($row['receiver'] === $me) {
        $mysqli->query("UPDATE chat_messages SET deleted_by_receiver=1 WHERE id={$id}");
    } else {
        json(['ok'=>false,'msg'=>'no permission']);
    }
    json(['ok'=>true]);
}

json(['ok'=>false,'msg'=>'unknown action']);


// chat_ui.php
require_once 'config.php';
session_start();
if (empty($_SESSION['user'])) { header('Location: index.php'); exit; }
$user = $_SESSION['user'];
$me = htmlspecialchars($user['username']);
$myFull = htmlspecialchars($user['fullName']);
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Chat — Painel</title>
<link rel="stylesheet" href="style.css"> <!-- usa seu css -->
<style>
/* minimal styles for chat, matches seu visual */
.chat-wrap{max-width:1100px;margin:40px auto;display:flex;gap:18px}
.chat-sidebar{width:300px;background:var(--card);padding:12px;border-radius:14px;box-shadow:var(--shadow)}
.chat-main{flex:1;background:var(--card);padding:18px;border-radius:14px;box-shadow:var(--shadow);display:flex;flex-direction:column;min-height:520px}
.contact{display:flex;align-items:center;justify-content:space-between;padding:10px;border-radius:8px;cursor:pointer}
.contact:hover{background:#f3f8ff}
.contact .name{font-weight:700;color:var(--accent-700)}
.preview{font-size:13px;color:var(--muted);margin-top:4px}
.messages{flex:1;overflow:auto;padding:12px 8px}
.msg{max-width:70%;padding:10px;border-radius:10px;margin-bottom:8px;line-height:1.25}
.msg.me{background:linear-gradient(90deg,var(--accent),var(--accent-700));color:#fff;margin-left:auto;border-bottom-right-radius:2px}
.msg.other{background:#f1f6fb;color:#0b2740;border-bottom-left-radius:2px}
.msg .meta{font-size:11px;color:rgba(0,0,0,0.4);margin-top:6px}
.chat-input{display:flex;gap:8px;padding:10px;border-top:1px solid #eef3f9}
.chat-input textarea{flex:1;border-radius:10px;padding:10px;border:1px solid #e6edf3;height:56px;resize:none}
.btn-send{background:linear-gradient(90deg,var(--accent),var(--accent-700));color:#fff;border:none;padding:10px 16px;border-radius:10px;cursor:pointer}
.badge{background:#e53935;color:#fff;padding:4px 8px;border-radius:999px;font-weight:700}
.small-muted{font-size:13px;color:var(--muted)}
.empty-center{text-align:center;color:var(--muted);padding:30px}
.contact-selected{background:linear-gradient(90deg,#eef7ff,#f3f9ff);border-left:3px solid var(--accent);}

/* mobile */
@media(max-width:900px){ .chat-wrap{flex-direction:column;padding:8px} .chat-sidebar{width:100%} .chat-main{min-height:420px} }
</style>
</head>
<body>
<header class="topbar light">
  <div class="brand">
    <img src="https://tse3.mm.bing.net/th/id/OIP.NeJbj2QckKlAAfZ0YlkgUgHaJw?cb=12&pid=Api" class="brasao" alt="">
    <div><div class="title">Chat Interno</div><div class="subtitle">Conversa entre recepção e vereadores</div></div>
  </div>
  <div class="top-actions">
    <div class="small-muted">Você: <?php echo $myFull; ?></div>
    <button class="btn ghost" onclick="location.href='painel.php'">Voltar</button>
  </div>
</header>

<main class="chat-wrap">
  <aside class="chat-sidebar">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
      <strong>Contatos</strong>
      <button class="btn ghost" id="refreshContacts">Atualizar</button>
    </div>
    <div id="contactsList"></div>
  </aside>

  <section class="chat-main">
    <div id="convHeader" style="padding-bottom:8px;border-bottom:1px solid #eef3f9">
      <strong id="convWith">Selecione um contato</strong>
      <div id="convSub" class="small-muted">Clique em um contato para iniciar</div>
    </div>

    <div class="messages" id="messagesArea">
      <div class="empty-center">Nenhuma conversa selecionada</div>
    </div>

    <div class="chat-input">
      <textarea id="msgText" placeholder="Escreva sua mensagem..." disabled></textarea>
      <button class="btn-send" id="sendBtn" disabled>Enviar</button>
    </div>
  </section>
</main>

<script>
const me = "<?php echo addslashes($user['username']); ?>";
let currentWith = null;
</script>
<script src="chat.js"></script>
</body>
</html>
