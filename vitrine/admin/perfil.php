<?php
require __DIR__ . '/includes/init.php';
require __DIR__ . '/includes/layout.php';
exigir_login();

$usuario = usuario_logado();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $senhaAtual = (string) ($_POST['senha_atual'] ?? '');
    $novaSenha  = (string) ($_POST['nova_senha'] ?? '');
    $confirmar  = (string) ($_POST['confirmar_senha'] ?? '');

    $st = db()->prepare('SELECT senha_hash FROM admin_usuarios WHERE id = ?');
    $st->execute([$usuario['id']]);
    $hashAtual = $st->fetchColumn();

    if (!$hashAtual || !password_verify($senhaAtual, $hashAtual)) {
        $_SESSION['flash_erro'] = 'A senha atual está incorreta.';
    } elseif (strlen($novaSenha) < 10) {
        $_SESSION['flash_erro'] = 'A nova senha deve ter pelo menos 10 caracteres.';
    } elseif (!preg_match('/[A-Za-z]/', $novaSenha) || !preg_match('/\d/', $novaSenha)) {
        $_SESSION['flash_erro'] = 'A nova senha deve conter letras e números.';
    } elseif ($novaSenha !== $confirmar) {
        $_SESSION['flash_erro'] = 'A confirmação não confere com a nova senha.';
    } else {
        db()->prepare('UPDATE admin_usuarios SET senha_hash = ? WHERE id = ?')
            ->execute([password_hash($novaSenha, PASSWORD_DEFAULT), $usuario['id']]);
        registrar_log('atualizar', 'usuario', $usuario['id'], 'Senha alterada pelo próprio usuário.');
        $_SESSION['flash_ok'] = 'Senha alterada com sucesso.';
    }
    header('Location: perfil.php');
    exit;
}

admin_cabecalho('Meu perfil', 'perfil');
?>
<div class="topo"><h1>Meu perfil</h1></div>
<?php mostrar_flash(); ?>

<div class="cartao">
  <p><strong>Nome:</strong> <?= e($usuario['nome']) ?></p>
  <p style="margin-top:6px"><strong>E-mail:</strong> <?= e($usuario['email']) ?></p>
</div>

<form class="cartao" method="post" style="max-width:480px">
  <?= csrf_campo() ?>
  <h2 style="color:var(--azul);font-size:18px;margin-bottom:8px">Alterar senha</h2>
  <label for="senha_atual">Senha atual</label>
  <input type="password" id="senha_atual" name="senha_atual" required autocomplete="current-password">
  <label for="nova_senha">Nova senha (mínimo 10 caracteres, com letras e números)</label>
  <input type="password" id="nova_senha" name="nova_senha" required minlength="10" autocomplete="new-password">
  <label for="confirmar_senha">Confirmar nova senha</label>
  <input type="password" id="confirmar_senha" name="confirmar_senha" required minlength="10" autocomplete="new-password">
  <div style="margin-top:22px">
    <button class="botao botao-primario" type="submit">Alterar senha</button>
  </div>
</form>
<?php admin_rodape(); ?>
