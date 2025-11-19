<?php
session_start();
unset($_SESSION['login_error']);
echo json_encode(['ok' => true]);
?>