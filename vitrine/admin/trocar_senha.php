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
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',system-ui,Arial,sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;
  background:linear-gradient(135deg,#071f8f 0%,#0d37d0 55%,#2898a4 130%);padding:20px}
.caixa{background:#fff;border-radius:22px;box-shadow:0 24px 60px rgba(4,15,70,.35);width:100%;max-width:480px;padding:38px 34px}
.caixa h1{color:#071f8f;font-size:22px;margin-bottom:4px}
.caixa p.sub{color:#5b6475;font-size:14px;margin-bottom:18px}
label{display:block;font-weight:600;font-size:13.5px;margin:14px 0 6px;color:#071f8f}
input{width:100%;padding:12px 14px;border:1px solid #e3e8f2;border-radius:11px;font-size:15px}
input:focus{outline:2px solid #2898a4;border-color:#2898a4}
button{margin-top:22px;width:100%;border:0;cursor:pointer;background:#9dff00;color:#08215d;font-weight:800;font-size:16px;
  padding:14px;border-radius:999px;box-shadow:0 10px 24px rgba(157,255,0,.35);transition:transform .12s}
button:hover{transform:translateY(-2px)}
.erro{background:#fde8e8;color:#b91c1c;border:1px solid #f5c2c2;padding:12px 15px;border-radius:11px;font-size:14px;margin-bottom:6px}
.regras{background:#f2f7ff;border:1px solid #dbe7ff;border-radius:11px;padding:14px 16px;margin-top:8px}
.regras strong{color:#071f8f;font-size:13.5px}
.regras ul{margin:8px 0 0 18px;color:#5b6475;font-size:13px;line-height:1.7}
.rodape{margin-top:20px;text-align:center;font-size:12.5px;color:#8a92a6}
.rodape a{color:#2898a4}
</style>
</head>
<body>
<form class="caixa" method="post" autocomplete="off">
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
</body>
</html>
