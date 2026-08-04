<?php
require __DIR__ . '/includes/init.php';
require __DIR__ . '/includes/layout.php';
exigir_login();

$acao = $_GET['acao'] ?? 'listar';
$id   = isset($_GET['id']) ? (int) $_GET['id'] : 0;

/** Campos persistidos de um curso (para logs de antes/depois). */
function curso_dados(array $origem): array
{
    return [
        'titulo'        => trim($origem['titulo'] ?? ''),
        'descricao'     => trim($origem['descricao'] ?? ''),
        'imagem_url'    => trim($origem['imagem_url'] ?? ''),
        'link_url'      => trim($origem['link_url'] ?? ''),
        'carga_horaria' => trim($origem['carga_horaria'] ?? ''),
        'texto_botao'   => trim($origem['texto_botao'] ?? '') ?: '👉 Inscreva-se agora!',
        'novo'          => (int) ($origem['novo'] ?? 0) === 1 ? 1 : 0,
        'ordem'         => (int) ($origem['ordem'] ?? 0),
        'ativo'         => (int) ($origem['ativo'] ?? 1) === 1 ? 1 : 0,
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    try {
        $dados = curso_dados($_POST);
        $dados['novo']  = isset($_POST['novo']) ? 1 : 0;
        $dados['ativo'] = isset($_POST['ativo']) ? 1 : 0;
        if ($dados['titulo'] === '') {
            throw new RuntimeException('O título do curso é obrigatório.');
        }
        if ($dados['link_url'] === '') {
            throw new RuntimeException('O link de inscrição é obrigatório.');
        }
        foreach (['imagem_url' => 'URL da imagem', 'link_url' => 'link de inscrição'] as $campo => $rotulo) {
            if ($dados[$campo] !== '' && substr_count($dados[$campo], 'http') > 1) {
                throw new RuntimeException('O campo "' . $rotulo . '" contém dois endereços colados. Deixe apenas um.');
            }
        }
        if ($urlUpload = salvar_imagem($_FILES['imagem_arquivo'] ?? [])) {
            $dados['imagem_url'] = $urlUpload;
        }

        if ($acao === 'novo') {
            $st = db()->prepare(
                'INSERT INTO cursos (titulo, descricao, imagem_url, link_url, carga_horaria, texto_botao, novo, ordem, ativo)
                 VALUES (?,?,?,?,?,?,?,?,?)'
            );
            $st->execute(array_values($dados));
            $novoId = (int) db()->lastInsertId();
            registrar_log('inserir', 'curso', $novoId, 'Curso inserido: ' . $dados['titulo'], null, $dados);
            $_SESSION['flash_ok'] = 'Curso cadastrado com sucesso.';
        } elseif ($acao === 'editar' && $id > 0) {
            $st = db()->prepare('SELECT * FROM cursos WHERE id = ?');
            $st->execute([$id]);
            $anterior = $st->fetch();
            if (!$anterior) {
                throw new RuntimeException('Curso não encontrado.');
            }
            $st = db()->prepare(
                'UPDATE cursos SET titulo=?, descricao=?, imagem_url=?, link_url=?, carga_horaria=?, texto_botao=?, novo=?, ordem=?, ativo=?
                  WHERE id = ?'
            );
            $st->execute([...array_values($dados), $id]);
            registrar_log('atualizar', 'curso', $id, 'Curso atualizado: ' . $dados['titulo'], curso_dados($anterior), $dados);
            $_SESSION['flash_ok'] = 'Curso atualizado com sucesso.';
        }
    } catch (RuntimeException $ex) {
        $_SESSION['flash_erro'] = $ex->getMessage();
    }
    header('Location: cursos.php');
    exit;
}

if ($acao === 'excluir' && $id > 0 && hash_equals(csrf_token(), $_GET['csrf'] ?? '')) {
    $st = db()->prepare('SELECT * FROM cursos WHERE id = ?');
    $st->execute([$id]);
    if ($anterior = $st->fetch()) {
        db()->prepare('DELETE FROM cursos WHERE id = ?')->execute([$id]);
        registrar_log('excluir', 'curso', $id, 'Curso excluído: ' . $anterior['titulo'], curso_dados($anterior), null);
        $_SESSION['flash_ok'] = 'Curso excluído.';
    }
    header('Location: cursos.php');
    exit;
}

$editando = null;
if ($acao === 'editar' && $id > 0) {
    $st = db()->prepare('SELECT * FROM cursos WHERE id = ?');
    $st->execute([$id]);
    $editando = $st->fetch();
}

admin_cabecalho('Cursos', 'cursos');
?>
<div class="topo">
  <h1>Novos cursos</h1>
  <a class="botao botao-verde" href="cursos.php?acao=novo#formulario">+ Novo curso</a>
</div>
<?php mostrar_flash(); ?>

<div class="cartao" style="overflow-x:auto">
<table>
  <tr><th>Capa</th><th>Título</th><th>Carga</th><th>Ordem</th><th>Situação</th><th>Atualizado em</th><th style="width:180px">Ações</th></tr>
  <?php foreach (db()->query('SELECT * FROM cursos ORDER BY ordem, titulo') as $c): ?>
  <tr>
    <td><?php if ($c['imagem_url']): ?><img class="miniatura" src="<?= e($c['imagem_url']) ?>" alt=""><?php endif; ?></td>
    <td><strong><?= e($c['titulo']) ?></strong><?= $c['novo'] ? ' <span class="selo selo-ativo">NOVO</span>' : '' ?></td>
    <td><?= e($c['carga_horaria']) ?></td>
    <td><?= (int) $c['ordem'] ?></td>
    <td><span class="selo <?= $c['ativo'] ? 'selo-ativo' : 'selo-inativo' ?>"><?= $c['ativo'] ? 'Ativo' : 'Inativo' ?></span></td>
    <td><?= e(date('d/m/Y H:i', strtotime($c['atualizado_em']))) ?></td>
    <td>
      <a class="botao botao-claro botao-mini" href="cursos.php?acao=editar&id=<?= $c['id'] ?>#formulario">Editar</a>
      <a class="botao botao-perigo botao-mini" href="cursos.php?acao=excluir&id=<?= $c['id'] ?>&csrf=<?= e(csrf_token()) ?>"
         onclick="return confirm('Excluir este curso? Esta ação não pode ser desfeita.')">Excluir</a>
    </td>
  </tr>
  <?php endforeach; ?>
</table>
</div>

<?php if ($acao === 'novo' || $editando): ?>
<form class="cartao" id="formulario" method="post" enctype="multipart/form-data"
      action="cursos.php?acao=<?= $editando ? 'editar&id=' . (int) $editando['id'] : 'novo' ?>">
  <?= csrf_campo() ?>
  <h2 style="color:var(--azul);font-size:18px;margin-bottom:8px"><?= $editando ? 'Editar curso' : 'Novo curso' ?></h2>

  <label for="c_titulo">Título do curso *</label>
  <input type="text" id="c_titulo" name="titulo" required value="<?= e($editando['titulo'] ?? '') ?>">

  <label for="c_desc">Descrição</label>
  <textarea id="c_desc" name="descricao"><?= e($editando['descricao'] ?? '') ?></textarea>

  <div class="linha-campos">
    <div>
      <label for="c_img">URL da imagem de capa</label>
      <input type="url" id="c_img" name="imagem_url" value="<?= e($editando['imagem_url'] ?? '') ?>" placeholder="https://...">
      <div class="ajuda">Ou envie um arquivo abaixo — o envio substitui a URL.</div>
      <label for="c_arq">Enviar imagem (JPG, PNG, GIF ou WEBP, até 4 MB)</label>
      <input type="file" id="c_arq" name="imagem_arquivo" accept="image/*">
    </div>
    <div>
      <label for="c_link">Link de inscrição (curso no Moodle) *</label>
      <input type="url" id="c_link" name="link_url" required value="<?= e($editando['link_url'] ?? '') ?>"
             placeholder="https://eadcecape.com.br/ava/course/view.php?id=...">
      <label for="c_btn">Texto do botão</label>
      <input type="text" id="c_btn" name="texto_botao" value="<?= e($editando['texto_botao'] ?? '👉 Inscreva-se agora!') ?>">
    </div>
  </div>

  <div class="linha-campos">
    <div>
      <label for="c_carga">Carga horária (ex.: 30 horas)</label>
      <input type="text" id="c_carga" name="carga_horaria" value="<?= e($editando['carga_horaria'] ?? '') ?>">
    </div>
    <div>
      <label for="c_ordem">Ordem de exibição</label>
      <input type="number" id="c_ordem" name="ordem" value="<?= (int) ($editando['ordem'] ?? 0) ?>">
    </div>
  </div>

  <label><input type="checkbox" name="novo" <?= ($editando['novo'] ?? 1) ? 'checked' : '' ?>> Exibir selo “NOVO” no card</label>
  <label><input type="checkbox" name="ativo" <?= ($editando['ativo'] ?? 1) ? 'checked' : '' ?>> Curso ativo (visível na vitrine)</label>

  <div style="margin-top:22px">
    <button class="botao botao-primario" type="submit">Salvar curso</button>
    <a class="botao botao-claro" href="cursos.php">Cancelar</a>
  </div>
</form>
<?php endif; ?>
<?php admin_rodape(); ?>
