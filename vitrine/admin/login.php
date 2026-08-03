<?php
require __DIR__ . '/includes/init.php';

if (usuario_logado()) {
    header('Location: index.php');
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $email = mb_strtolower(trim($_POST['email'] ?? ''));
    $senha = (string) ($_POST['senha'] ?? '');

    if ($email === '' || $senha === '') {
        $erro = 'Informe e-mail e senha.';
    } elseif (login_bloqueado($email)) {
        $erro = 'Muitas tentativas de acesso. Aguarde alguns minutos e tente novamente.';
    } else {
        $st = db()->prepare('SELECT * FROM admin_usuarios WHERE email = ? AND ativo = 1 LIMIT 1');
        $st->execute([$email]);
        $usuario = $st->fetch();

        if ($usuario && password_verify($senha, $usuario['senha_hash'])) {
            registrar_tentativa($email, true);

            // Renova o ID da sessão para impedir fixação de sessão
            session_regenerate_id(true);
            $_SESSION['admin'] = [
                'id'    => (int) $usuario['id'],
                'nome'  => $usuario['nome'],
                'email' => $usuario['email'],
                'senha_provisoria' => (int) ($usuario['senha_provisoria'] ?? 0),
            ];
            unset($_SESSION['csrf']); // novo token para a sessão autenticada

            db()->prepare('UPDATE admin_usuarios SET ultimo_login = NOW() WHERE id = ?')
                ->execute([$usuario['id']]);

            registrar_log('login', 'sessao', (int) $usuario['id'], 'Acesso realizado no painel administrativo.');

            // Rehash automático se o algoritmo padrão evoluir
            if (password_needs_rehash($usuario['senha_hash'], PASSWORD_DEFAULT)) {
                db()->prepare('UPDATE admin_usuarios SET senha_hash = ? WHERE id = ?')
                    ->execute([password_hash($senha, PASSWORD_DEFAULT), $usuario['id']]);
            }

            // Senha provisória: obriga a troca antes de liberar o painel
            header('Location: ' . (!empty($usuario['senha_provisoria']) ? 'trocar_senha.php' : 'index.php'));
            exit;
        }

        registrar_tentativa($email, false);
        registrar_log('login_falha', 'sessao', null, 'Tentativa de acesso inválida para o e-mail: ' . $email);
        $erro = 'E-mail ou senha inválidos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Acesso restrito · Admin AutoriaSCS</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',system-ui,Arial,sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;
  background:linear-gradient(135deg,#071f8f 0%,#0d37d0 55%,#2898a4 130%);padding:20px}
.caixa{background:#fff;border-radius:22px;box-shadow:0 24px 60px rgba(4,15,70,.35);width:100%;max-width:420px;padding:38px 34px}
.caixa h1{color:#071f8f;font-size:23px;margin-bottom:4px}
.caixa p.sub{color:#5b6475;font-size:14px;margin-bottom:24px}
label{display:block;font-weight:600;font-size:13.5px;margin:14px 0 6px;color:#071f8f}
input{width:100%;padding:12px 14px;border:1px solid #e3e8f2;border-radius:11px;font-size:15px}
input:focus{outline:2px solid #2898a4;border-color:#2898a4}
button{margin-top:22px;width:100%;border:0;cursor:pointer;background:#9dff00;color:#08215d;font-weight:800;font-size:16px;
  padding:14px;border-radius:999px;box-shadow:0 10px 24px rgba(157,255,0,.35);transition:transform .12s}
button:hover{transform:translateY(-2px)}
.erro{background:#fde8e8;color:#b91c1c;border:1px solid #f5c2c2;padding:12px 15px;border-radius:11px;font-size:14px;margin-bottom:6px}
.rodape{margin-top:22px;text-align:center;font-size:12.5px;color:#8a92a6}
</style>
</head>
<body>
<form class="caixa" method="post" autocomplete="off">
  <h1>🔒 Acesso restrito</h1>
  <p class="sub">Painel da vitrine · Plataforma AutoriaSCS / CECAPE</p>
  <?php if ($erro): ?><div class="erro"><?= e($erro) ?></div><?php endif; ?>
  <?= csrf_campo() ?>
  <label for="email">E-mail</label>
  <input type="email" id="email" name="email" required autofocus value="<?= e($_POST['email'] ?? '') ?>">
  <label for="senha">Senha</label>
  <input type="password" id="senha" name="senha" required>
  <button type="submit">Entrar no painel</button>
  <div class="rodape">Acesso monitorado — todas as ações são registradas em log.</div>
</form>
</body>
</html>
