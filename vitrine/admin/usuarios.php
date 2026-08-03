<?php
require __DIR__ . '/includes/init.php';
require __DIR__ . '/includes/layout.php';
exigir_login();

$acao = $_GET['acao'] ?? 'listar';
$id   = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$eu   = usuario_logado();

/** Campos de um usuário para registro em log (nunca inclui a senha). */
function usuario_dados_log(array $origem): array
{
    return [
        'nome'  => $origem['nome'] ?? '',
        'email' => $origem['email'] ?? '',
        'ativo' => (int) ($origem['ativo'] ?? 1),
    ];
}

/** Valida a política de senha do painel. Devolve mensagem de erro ou null. */
function validar_senha(string $senha, string $confirmar): ?string
{
    if (strlen($senha) < 10) {
        return 'A senha deve ter pelo menos 10 caracteres.';
    }
    if (!preg_match('/[A-Za-z]/', $senha) || !preg_match('/\d/', $senha)) {
        return 'A senha deve conter letras e números.';
    }
    if ($senha !== $confirmar) {
        return 'A confirmação não confere com a senha.';
    }
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    try {
        $nome  = trim($_POST['nome'] ?? '');
        $email = mb_strtolower(trim($_POST['email'] ?? ''));
        $ativo = isset($_POST['ativo']) ? 1 : 0;

        if ($nome === '') {
            throw new RuntimeException('O nome é obrigatório.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Informe um e-mail válido.');
        }

        // E-mail não pode repetir (exceto o do próprio registro em edição)
        $st = db()->prepare('SELECT id FROM admin_usuarios WHERE email = ? AND id <> ? LIMIT 1');
        $st->execute([$email, $acao === 'editar' ? $id : 0]);
        if ($st->fetch()) {
            throw new RuntimeException('Já existe um usuário com este e-mail.');
        }

        if ($acao === 'novo') {
            $senha     = (string) ($_POST['senha'] ?? '');
            $confirmar = (string) ($_POST['confirmar_senha'] ?? '');
            if ($erroSenha = validar_senha($senha, $confirmar)) {
                throw new RuntimeException($erroSenha);
            }

            $st = db()->prepare('INSERT INTO admin_usuarios (nome, email, senha_hash, ativo) VALUES (?,?,?,?)');
            $st->execute([$nome, $email, password_hash($senha, PASSWORD_DEFAULT), $ativo]);
            $novoId = (int) db()->lastInsertId();
            registrar_log('inserir', 'usuario', $novoId, 'Usuário do painel criado: ' . $nome . ' (' . $email . ')',
                null, ['nome' => $nome, 'email' => $email, 'ativo' => $ativo]);
            $_SESSION['flash_ok'] = 'Usuário criado com sucesso.';
        } elseif ($acao === 'editar' && $id > 0) {
            $st = db()->prepare('SELECT * FROM admin_usuarios WHERE id = ?');
            $st->execute([$id]);
            $anterior = $st->fetch();
            if (!$anterior) {
                throw new RuntimeException('Usuário não encontrado.');
            }
            if ($id === (int) $eu['id'] && $ativo === 0) {
                throw new RuntimeException('Você não pode desativar o seu próprio usuário.');
            }

            db()->prepare('UPDATE admin_usuarios SET nome = ?, email = ?, ativo = ? WHERE id = ?')
                ->execute([$nome, $email, $ativo, $id]);

            // Redefinição de senha é opcional na edição
            $senha = (string) ($_POST['senha'] ?? '');
            if ($senha !== '') {
                $confirmar = (string) ($_POST['confirmar_senha'] ?? '');
                if ($erroSenha = validar_senha($senha, $confirmar)) {
                    throw new RuntimeException($erroSenha);
                }
                db()->prepare('UPDATE admin_usuarios SET senha_hash = ? WHERE id = ?')
                    ->execute([password_hash($senha, PASSWORD_DEFAULT), $id]);
            }

            registrar_log('atualizar', 'usuario', $id,
                'Usuário do painel atualizado: ' . $nome . ($senha !== '' ? ' (senha redefinida)' : ''),
                usuario_dados_log($anterior), ['nome' => $nome, 'email' => $email, 'ativo' => $ativo]);

            // Mantém os dados da sessão em dia se editou a si mesmo
            if ($id === (int) $eu['id']) {
                $_SESSION['admin']['nome']  = $nome;
                $_SESSION['admin']['email'] = $email;
            }
            $_SESSION['flash_ok'] = 'Usuário atualizado com sucesso.';
        }
    } catch (RuntimeException $ex) {
        $_SESSION['flash_erro'] = $ex->getMessage();
    }
    header('Location: usuarios.php');
    exit;
}

if ($acao === 'excluir' && $id > 0 && hash_equals(csrf_token(), $_GET['csrf'] ?? '')) {
    if ($id === (int) $eu['id']) {
        $_SESSION['flash_erro'] = 'Você não pode excluir o seu próprio usuário.';
    } else {
        $st = db()->prepare('SELECT * FROM admin_usuarios WHERE id = ?');
        $st->execute([$id]);
        if ($anterior = $st->fetch()) {
            db()->prepare('DELETE FROM admin_usuarios WHERE id = ?')->execute([$id]);
            registrar_log('excluir', 'usuario', $id,
                'Usuário do painel excluído: ' . $anterior['nome'] . ' (' . $anterior['email'] . ')',
                usuario_dados_log($anterior), null);
            $_SESSION['flash_ok'] = 'Usuário excluído.';
        }
    }
    header('Location: usuarios.php');
    exit;
}

$editando = null;
if ($acao === 'editar' && $id > 0) {
    $st = db()->prepare('SELECT * FROM admin_usuarios WHERE id = ?');
    $st->execute([$id]);
    $editando = $st->fetch();
}

admin_cabecalho('Usuários', 'usuarios');
?>
<div class="topo">
  <h1>Usuários do painel</h1>
  <a class="botao botao-verde" href="usuarios.php?acao=novo#formulario">+ Novo usuário</a>
</div>
<?php mostrar_flash(); ?>

<div class="cartao" style="overflow-x:auto">
<table>
  <tr><th>Nome</th><th>E-mail</th><th>Situação</th><th>Último login</th><th>Criado em</th><th style="width:180px">Ações</th></tr>
  <?php foreach (db()->query('SELECT * FROM admin_usuarios ORDER BY nome') as $u): ?>
  <tr>
    <td><strong><?= e($u['nome']) ?></strong><?= (int) $u['id'] === (int) $eu['id'] ? ' <span class="selo selo-ativo">você</span>' : '' ?></td>
    <td><?= e($u['email']) ?></td>
    <td><span class="selo <?= $u['ativo'] ? 'selo-ativo' : 'selo-inativo' ?>"><?= $u['ativo'] ? 'Ativo' : 'Inativo' ?></span></td>
    <td><?= $u['ultimo_login'] ? e(date('d/m/Y H:i', strtotime($u['ultimo_login']))) : '—' ?></td>
    <td><?= e(date('d/m/Y', strtotime($u['criado_em']))) ?></td>
    <td>
      <a class="botao botao-claro botao-mini" href="usuarios.php?acao=editar&id=<?= $u['id'] ?>#formulario">Editar</a>
      <?php if ((int) $u['id'] !== (int) $eu['id']): ?>
      <a class="botao botao-perigo botao-mini" href="usuarios.php?acao=excluir&id=<?= $u['id'] ?>&csrf=<?= e(csrf_token()) ?>"
         onclick="return confirm('Excluir o usuário &quot;<?= e($u['nome']) ?>&quot;? Esta ação não pode ser desfeita.')">Excluir</a>
      <?php endif; ?>
    </td>
  </tr>
  <?php endforeach; ?>
</table>
</div>

<?php if ($acao === 'novo' || $editando): ?>
<form class="cartao" id="formulario" method="post" style="max-width:560px"
      action="usuarios.php?acao=<?= $editando ? 'editar&id=' . (int) $editando['id'] : 'novo' ?>" autocomplete="off">
  <?= csrf_campo() ?>
  <h2 style="color:var(--azul);font-size:18px;margin-bottom:8px"><?= $editando ? 'Editar usuário' : 'Novo usuário' ?></h2>

  <label for="u_nome">Nome *</label>
  <input type="text" id="u_nome" name="nome" required value="<?= e($editando['nome'] ?? '') ?>">

  <label for="u_email">E-mail (usado no login) *</label>
  <input type="email" id="u_email" name="email" required value="<?= e($editando['email'] ?? '') ?>">

  <label for="u_senha"><?= $editando ? 'Nova senha (deixe em branco para não alterar)' : 'Senha *' ?></label>
  <input type="password" id="u_senha" name="senha" <?= $editando ? '' : 'required' ?> minlength="10" autocomplete="new-password">
  <div class="ajuda">Mínimo de 10 caracteres, contendo letras e números.</div>

  <label for="u_conf"><?= $editando ? 'Confirmar nova senha' : 'Confirmar senha *' ?></label>
  <input type="password" id="u_conf" name="confirmar_senha" <?= $editando ? '' : 'required' ?> minlength="10" autocomplete="new-password">

  <label style="margin-top:18px">
    <input type="checkbox" name="ativo" <?= ($editando['ativo'] ?? 1) ? 'checked' : '' ?>
      <?= $editando && (int) $editando['id'] === (int) $eu['id'] ? 'disabled' : '' ?>>
    Usuário ativo (pode acessar o painel)
  </label>
  <?php if ($editando && (int) $editando['id'] === (int) $eu['id']): ?>
    <input type="hidden" name="ativo" value="1">
    <div class="ajuda">Não é possível desativar o próprio usuário.</div>
  <?php endif; ?>

  <div style="margin-top:22px">
    <button class="botao botao-primario" type="submit">Salvar usuário</button>
    <a class="botao botao-claro" href="usuarios.php">Cancelar</a>
  </div>
</form>
<?php endif; ?>
<?php admin_rodape(); ?>
