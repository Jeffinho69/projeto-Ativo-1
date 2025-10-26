<?php
$DB_HOST='localhost';$DB_NAME='painel_recepcao';$DB_USER='usuario_banco';$DB_PASS='senha_banco';
$mysqli=new mysqli($DB_HOST,$DB_USER,$DB_PASS,$DB_NAME);
if($mysqli->connect_errno){die('Erro DB');}
?>