<?php
require __DIR__ . '/includes/init.php';

if (usuario_logado()) {
    registrar_log('logout', 'sessao', usuario_logado()['id'], 'Saída do painel administrativo.');
}

session_unset();
session_destroy();
header('Location: login.php');
exit;
