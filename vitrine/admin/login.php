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
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',system-ui,-apple-system,'Segoe UI',sans-serif;min-height:100vh;display:flex;flex-direction:column;
  background:#0b1628;background-image:linear-gradient(160deg,#0b1628 0%,#0a1f3a 50%,#091628 100%);color:#fff;
  -webkit-font-smoothing:antialiased}
.centro{flex:1;display:flex;align-items:center;justify-content:center;padding:40px 24px}
.caixa{background:#0f2044;border:1px solid rgba(30,80,160,.55);border-radius:12px;
  box-shadow:0 10px 48px rgba(0,0,0,.65);width:100%;max-width:460px;padding:40px 44px}
.logos{display:flex;align-items:center;justify-content:center;gap:14px;margin-bottom:26px}
.logos img{height:38px;width:auto;max-width:29%;object-fit:contain;filter:brightness(1.05)}
.logos .div{width:1px;height:30px;background:rgba(255,255,255,.15);flex-shrink:0}
.caixa h1{font-size:22px;font-weight:800;text-align:center;color:#fff;margin-bottom:6px}
.caixa p.sub{text-align:center;font-size:13px;color:#8baac8;margin-bottom:26px;line-height:1.7}
label{display:block;font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#8baac8;margin:14px 0 7px}
input{width:100%;padding:12px 14px;background:#0b1e3d;border:1px solid rgba(30,80,160,.35);border-radius:8px;
  font-family:inherit;font-size:14px;color:#fff;outline:none;transition:border-color .2s,box-shadow .2s}
input:focus{border-color:#22d3ee;box-shadow:0 0 0 3px rgba(34,211,238,.12)}
input::placeholder{color:#3d6080}
button{margin-top:22px;width:100%;border:0;cursor:pointer;background:linear-gradient(135deg,#1d4ed8 0%,#06b6d4 100%);
  color:#fff;font-family:inherit;font-weight:700;font-size:15px;padding:13px;border-radius:8px;transition:all .18s}
button:hover{transform:translateY(-2px);box-shadow:0 0 28px rgba(34,211,238,.18)}
.erro{background:rgba(239,68,68,.1);color:#f87171;border:1px solid rgba(239,68,68,.35);padding:12px 15px;
  border-radius:8px;font-size:13.5px;margin-bottom:6px}
.rodape{margin-top:22px;text-align:center;font-size:12.5px;color:#3d6080;line-height:1.6}
.site-footer{background:#0d9488;padding:16px 24px;text-align:center;font-size:12px;color:rgba(255,255,255,.75);
  text-transform:uppercase;letter-spacing:.04em}
@media(max-width:520px){.caixa{padding:28px 22px}.logos img{height:28px}.logos{gap:10px}}
</style>
</head>
<body>
<div class="centro">
<form class="caixa" method="post" autocomplete="off">
  <div class="logos">
    <img src="https://cecapescs.com.br/logos/logo-seeduc.png" alt="SEEDUC">
    <div class="div"></div>
    <img src="https://cecapescs.com.br/logos/logo-autoriascs.png" alt="AutoriaSCS">
    <div class="div"></div>
    <img src="https://cecapescs.com.br/logos/logo-cecape-new.png" alt="CECAPE">
  </div>
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
</div>
<footer class="site-footer">CECAPE - Centro de Capacitação de Profissionais da Educação · Secretaria Municipal de Educação de São Caetano do Sul</footer>
</body>
</html>
