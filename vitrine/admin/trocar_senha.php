<?php
/**
 * Troca de senha obrigatória no primeiro acesso (senha provisória).
 * O usuário não consegue acessar nenhuma outra página do painel
 * enquanto não definir a própria senha.
 */

require __DIR__ . '/includes/init.php';
exigir_login(true);

$usuario = usuario_logado();

// Quem já tem senha definitiva não precisa desta página
if (empty($_SESSION['admin']['senha_provisoria'])) {
    header('Location: index.php');
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $senhaAtual = (string) ($_POST['senha_atual'] ?? '');
    $novaSenha  = (string) ($_POST['nova_senha'] ?? '');
    $confirmar  = (string) ($_POST['confirmar_senha'] ?? '');

    $st = db()->prepare('SELECT senha_hash FROM admin_usuarios WHERE id = ?');
    $st->execute([$usuario['id']]);
    $hashAtual = $st->fetchColumn();

    if (!$hashAtual || !password_verify($senhaAtual, $hashAtual)) {
        $erro = 'A senha provisória informada está incorreta.';
    } elseif ($novaSenha === $senhaAtual) {
        $erro = 'A nova senha deve ser diferente da senha provisória.';
    } elseif ($erroPolitica = validar_politica_senha($novaSenha, $usuario['nome'], $usuario['email'])) {
        $erro = $erroPolitica;
    } elseif ($novaSenha !== $confirmar) {
        $erro = 'A confirmação não confere com a nova senha.';
    } else {
        db()->prepare('UPDATE admin_usuarios SET senha_hash = ?, senha_provisoria = 0 WHERE id = ?')
            ->execute([password_hash($novaSenha, PASSWORD_DEFAULT), $usuario['id']]);
        $_SESSION['admin']['senha_provisoria'] = 0;

        registrar_log('atualizar', 'usuario', $usuario['id'], 'Senha definitiva criada no primeiro acesso.');
        enviar_email_conta(
            $usuario['email'],
            $usuario['nome'],
            'Senha definida com sucesso',
            'Sua senha de acesso ao painel da Plataforma AutoriaSCS foi definida com sucesso. A partir de agora, utilize a nova senha para entrar.',
            [
                'Ação'          => 'Definição da senha definitiva (primeiro acesso)',
                'Data e horário' => date('d/m/Y H:i'),
                'IP'            => ip_cliente(),
            ]
        );

        $_SESSION['flash_ok'] = 'Senha definida com sucesso. Bem-vindo(a) ao painel!';
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Defina sua senha · Admin AutoriaSCS</title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',system-ui,-apple-system,'Segoe UI',sans-serif;min-height:100vh;display:flex;flex-direction:column;
  background:#0b1628;background-image:linear-gradient(160deg,#0b1628 0%,#0a1f3a 50%,#091628 100%);color:#fff;
  -webkit-font-smoothing:antialiased}
.centro{flex:1;display:flex;align-items:center;justify-content:center;padding:40px 24px}
.caixa{background:#0f2044;border:1px solid rgba(30,80,160,.55);border-radius:12px;
  box-shadow:0 10px 48px rgba(0,0,0,.65);width:100%;max-width:480px;padding:40px 44px}
.logos{display:flex;align-items:center;justify-content:center;gap:14px;margin-bottom:26px}
.logos img{height:38px;width:auto;max-width:29%;object-fit:contain;filter:brightness(1.05)}
.logos .div{width:1px;height:30px;background:rgba(255,255,255,.15);flex-shrink:0}
.caixa h1{font-size:22px;font-weight:800;text-align:center;color:#fff;margin-bottom:6px}
.caixa p.sub{text-align:center;font-size:13px;color:#8baac8;margin-bottom:22px;line-height:1.7}
label{display:block;font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#8baac8;margin:14px 0 7px}
input{width:100%;padding:12px 14px;background:#0b1e3d;border:1px solid rgba(30,80,160,.35);border-radius:8px;
  font-family:inherit;font-size:14px;color:#fff;outline:none;transition:border-color .2s,box-shadow .2s}
input:focus{border-color:#22d3ee;box-shadow:0 0 0 3px rgba(34,211,238,.12)}
button{margin-top:22px;width:100%;border:0;cursor:pointer;background:linear-gradient(135deg,#1d4ed8 0%,#06b6d4 100%);
  color:#fff;font-family:inherit;font-weight:700;font-size:15px;padding:13px;border-radius:8px;transition:all .18s}
button:hover{transform:translateY(-2px);box-shadow:0 0 28px rgba(34,211,238,.18)}
.erro{background:rgba(239,68,68,.1);color:#f87171;border:1px solid rgba(239,68,68,.35);padding:12px 15px;
  border-radius:8px;font-size:13.5px;margin-bottom:6px}
.regras{background:rgba(34,211,238,.07);border:1px solid rgba(34,211,238,.25);border-left:4px solid #22d3ee;
  border-radius:8px;padding:14px 18px;margin-top:10px}
.regras strong{color:#22d3ee;font-size:11px;font-weight:800;letter-spacing:.08em;text-transform:uppercase}
.regras ul{margin:8px 0 0 18px;color:#c9d8e6;font-size:13px;line-height:1.8}
.rodape{margin-top:20px;text-align:center;font-size:12.5px;color:#3d6080}
.rodape a{color:#22d3ee;font-weight:600}
.rodape a:hover{text-decoration:underline}
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
  <h1>🔐 Defina a sua senha</h1>
  <p class="sub">Olá, <strong><?= e($usuario['nome']) ?></strong>! Por segurança, você precisa criar uma senha pessoal antes de acessar o painel.</p>
  <?php if ($erro): ?><div class="erro"><?= e($erro) ?></div><?php endif; ?>
  <?= csrf_campo() ?>

  <label for="senha_atual">Senha provisória (informada pelo administrador)</label>
  <input type="password" id="senha_atual" name="senha_atual" required autofocus autocomplete="current-password">

  <label for="nova_senha">Nova senha</label>
  <input type="password" id="nova_senha" name="nova_senha" required minlength="10" autocomplete="new-password">

  <label for="confirmar_senha">Confirmar nova senha</label>
  <input type="password" id="confirmar_senha" name="confirmar_senha" required minlength="10" autocomplete="new-password">

  <div class="regras">
    <strong>Regras da senha:</strong>
    <ul>
      <?php foreach (politica_senha_regras() as $regra): ?>
      <li><?= e($regra) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>

  <button type="submit">Salvar e entrar no painel</button>
  <div class="rodape"><a href="logout.php">Sair sem alterar</a></div>
</form>
</div>
<footer class="site-footer">CECAPE - Centro de Capacitação de Profissionais da Educação · Secretaria Municipal de Educação de São Caetano do Sul</footer>
</body>
</html>
