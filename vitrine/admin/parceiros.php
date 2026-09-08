<?php
require __DIR__ . '/includes/init.php';
require __DIR__ . '/includes/layout.php';
exigir_login();

$acao = $_GET['acao'] ?? 'listar';
$id   = isset($_GET['id']) ? (int) $_GET['id'] : 0;

/** Campos persistidos de um parceiro (para logs de antes/depois). */
function parceiro_dados(array $origem): array
{
    $valor   = ($origem['exibir_valor'] ?? '') !== '' && $origem['exibir_valor'] !== null
        ? max(1, (int) $origem['exibir_valor']) : null;
    $unidade = in_array($origem['exibir_unidade'] ?? '', ['dias', 'meses', 'anos'], true)
        ? $origem['exibir_unidade'] : null;
    if ($valor === null || $unidade === null) {
        $valor = $unidade = null; // prazo só vale com quantidade E unidade
    }
    return [
        'nome'           => trim($origem['nome'] ?? ''),
        'titulo'         => trim($origem['titulo'] ?? ''),
        'descricao'      => trim($origem['descricao'] ?? ''),
        'imagem_url'     => trim($origem['imagem_url'] ?? ''),
        'link_url'       => trim($origem['link_url'] ?? ''),
        'texto_botao'    => trim($origem['texto_botao'] ?? '') ?: 'Acessar plataforma',
        'destaques'      => trim($origem['destaques'] ?? ''),
        'observacao'     => trim($origem['observacao'] ?? ''),
        'ordem'          => (int) ($origem['ordem'] ?? 0),
        'ativo'          => (int) ($origem['ativo'] ?? 1) === 1 ? 1 : 0,
        'exibir_valor'   => $valor,
        'exibir_unidade' => $unidade,
    ];
}

/** Calcula a data em que o parceiro sai da página inicial, a partir de agora. */
function calcular_expiracao(?int $valor, ?string $unidade): ?string
{
    if (!$valor || !$unidade) {
        return null;
    }
    $mapa = ['dias' => 'D', 'meses' => 'M', 'anos' => 'Y'];
    $data = new DateTime();
    $data->add(new DateInterval('P' . $valor . $mapa[$unidade]));
    return $data->format('Y-m-d H:i:s');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    try {
        $dados = parceiro_dados($_POST);
        if (isset($_POST['ativo_checkbox'])) {
            $dados['ativo'] = isset($_POST['ativo']) ? 1 : 0;
        }
        if ($dados['nome'] === '') {
            throw new RuntimeException('O nome do parceiro é obrigatório.');
        }
        foreach (['imagem_url' => 'URL da imagem', 'link_url' => 'link do botão'] as $campo => $rotulo) {
            if ($dados[$campo] !== '' && substr_count($dados[$campo], 'http') > 1) {
                throw new RuntimeException('O campo "' . $rotulo . '" contém dois endereços colados. Deixe apenas um.');
            }
        }
        if ($urlUpload = salvar_imagem($_FILES['imagem_arquivo'] ?? [])) {
            $dados['imagem_url'] = $urlUpload;
        }

        if ($acao === 'novo') {
            $dados['expira_em'] = calcular_expiracao($dados['exibir_valor'], $dados['exibir_unidade']);
            $st = db()->prepare(
                'INSERT INTO parceiros (nome, titulo, descricao, imagem_url, link_url, texto_botao, destaques, observacao, ordem, ativo, exibir_valor, exibir_unidade, expira_em)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)'
            );
            $st->execute(array_values($dados));
            $novoId = (int) db()->lastInsertId();
            registrar_log('inserir', 'parceiro', $novoId, 'Parceiro inserido: ' . $dados['nome'], null, $dados);
            $_SESSION['flash_ok'] = 'Parceiro cadastrado com sucesso.';
        } elseif ($acao === 'editar' && $id > 0) {
            $st = db()->prepare('SELECT * FROM parceiros WHERE id = ?');
            $st->execute([$id]);
            $anterior = $st->fetch();
            if (!$anterior) {
                throw new RuntimeException('Parceiro não encontrado.');
            }
            // Só recalcula o prazo (a partir de agora) se ele foi alterado no formulário
            $dados['expira_em'] = $anterior['expira_em'];
            if ((int) ($anterior['exibir_valor'] ?? 0) !== (int) ($dados['exibir_valor'] ?? 0)
                || ($anterior['exibir_unidade'] ?? null) !== $dados['exibir_unidade']) {
                $dados['expira_em'] = calcular_expiracao($dados['exibir_valor'], $dados['exibir_unidade']);
            }
            $st = db()->prepare(
                'UPDATE parceiros SET nome=?, titulo=?, descricao=?, imagem_url=?, link_url=?, texto_botao=?, destaques=?, observacao=?, ordem=?, ativo=?, exibir_valor=?, exibir_unidade=?, expira_em=?
                  WHERE id = ?'
            );
            $st->execute([...array_values($dados), $id]);
            $logAnterior = parceiro_dados($anterior);
            $logAnterior['expira_em'] = $anterior['expira_em'];
            registrar_log('atualizar', 'parceiro', $id, 'Parceiro atualizado: ' . $dados['nome'], $logAnterior, $dados);
            $_SESSION['flash_ok'] = 'Parceiro atualizado com sucesso.';
        }
    } catch (RuntimeException $ex) {
        $_SESSION['flash_erro'] = $ex->getMessage();
    }
    header('Location: parceiros.php');
    exit;
}

if ($acao === 'excluir' && $id > 0 && hash_equals(csrf_token(), $_GET['csrf'] ?? '')) {
    $st = db()->prepare('SELECT * FROM parceiros WHERE id = ?');
    $st->execute([$id]);
    if ($anterior = $st->fetch()) {
        db()->prepare('DELETE FROM parceiros WHERE id = ?')->execute([$id]);
        registrar_log('excluir', 'parceiro', $id, 'Parceiro excluído: ' . $anterior['nome'], parceiro_dados($anterior), null);
        $_SESSION['flash_ok'] = 'Parceiro excluído.';
    }
    header('Location: parceiros.php');
    exit;
}

$editando = null;
if ($acao === 'editar' && $id > 0) {
    $st = db()->prepare('SELECT * FROM parceiros WHERE id = ?');
    $st->execute([$id]);
    $editando = $st->fetch();
}

admin_cabecalho('Parceiros', 'parceiros');
?>
<div class="topo">
  <h1>Parceiros</h1>
  <a class="botao botao-verde" href="parceiros.php?acao=novo#formulario">+ Novo parceiro</a>
</div>
<?php mostrar_flash(); ?>

<div class="cartao" style="overflow-x:auto">
<table>
  <tr><th>Imagem</th><th>Nome</th><th>Ordem</th><th>Situação</th><th>Página inicial</th><th>Atualizado em</th><th style="width:180px">Ações</th></tr>
  <?php foreach (db()->query('SELECT * FROM parceiros ORDER BY ordem, nome') as $p): ?>
  <tr>
    <td><?php if ($p['imagem_url']): ?><img class="miniatura" src="<?= e($p['imagem_url']) ?>" alt=""><?php endif; ?></td>
    <td><strong><?= e($p['nome']) ?></strong></td>
    <td><?= (int) $p['ordem'] ?></td>
    <td><span class="selo <?= $p['ativo'] ? 'selo-ativo' : 'selo-inativo' ?>"><?= $p['ativo'] ? 'Ativo' : 'Inativo' ?></span></td>
    <td>
      <?php if (!$p['ativo']): ?>—
      <?php elseif ($p['expira_em'] === null): ?><span class="selo selo-ativo">Sem prazo</span>
      <?php elseif (strtotime($p['expira_em']) <= time()): ?>
        <span class="selo selo-acao-login_falha">Encerrou em <?= e(date('d/m/Y', strtotime($p['expira_em']))) ?></span>
      <?php else: ?>
        <span class="selo selo-acao-atualizar">No ar até <?= e(date('d/m/Y', strtotime($p['expira_em']))) ?></span>
      <?php endif; ?>
    </td>
    <td><?= e(date('d/m/Y H:i', strtotime($p['atualizado_em']))) ?></td>
    <td>
      <a class="botao botao-claro botao-mini" href="parceiros.php?acao=editar&id=<?= $p['id'] ?>#formulario">Editar</a>
      <a class="botao botao-perigo botao-mini" href="parceiros.php?acao=excluir&id=<?= $p['id'] ?>&csrf=<?= e(csrf_token()) ?>"
         onclick="return confirm('Excluir o parceiro &quot;<?= e($p['nome']) ?>&quot;? Esta ação não pode ser desfeita.')">Excluir</a>
    </td>
  </tr>
  <?php endforeach; ?>
</table>
</div>

<?php if ($acao === 'novo' || $editando): ?>
<form class="cartao" id="formulario" method="post" enctype="multipart/form-data"
      action="parceiros.php?acao=<?= $editando ? 'editar&id=' . (int) $editando['id'] : 'novo' ?>">
  <?= csrf_campo() ?>
  <input type="hidden" name="ativo_checkbox" value="1">
  <h2 style="color:var(--azul);font-size:18px;margin-bottom:8px"><?= $editando ? 'Editar parceiro' : 'Novo parceiro' ?></h2>

  <label for="p_nome">Nome do parceiro *</label>
  <input type="text" id="p_nome" name="nome" required value="<?= e($editando['nome'] ?? '') ?>">

  <label for="p_titulo">Título de destaque no card</label>
  <input type="text" id="p_titulo" name="titulo" value="<?= e($editando['titulo'] ?? '') ?>" placeholder="Ex.: Formação com impacto real">

  <label for="p_desc">Descrição</label>
  <textarea id="p_desc" name="descricao"><?= e($editando['descricao'] ?? '') ?></textarea>

  <div class="linha-campos">
    <div>
      <label for="p_img">URL da imagem/banner</label>
      <input type="url" id="p_img" name="imagem_url" value="<?= e($editando['imagem_url'] ?? '') ?>" placeholder="https://...">
      <div class="ajuda">Ou envie um arquivo abaixo — o envio substitui a URL.</div>
      <label for="p_arq">Enviar imagem (JPG, PNG, GIF ou WEBP, até 4 MB)</label>
      <input type="file" id="p_arq" name="imagem_arquivo" accept="image/*">
    </div>
    <div>
      <label for="p_link">Link do botão</label>
      <input type="url" id="p_link" name="link_url" value="<?= e($editando['link_url'] ?? '') ?>" placeholder="https://...">
      <label for="p_btn">Texto do botão</label>
      <input type="text" id="p_btn" name="texto_botao" value="<?= e($editando['texto_botao'] ?? 'Acessar plataforma') ?>">
    </div>
  </div>

  <label for="p_dest">Itens de destaque (um por linha, no formato "Título - descrição")</label>
  <textarea id="p_dest" name="destaques" placeholder="Acesso gratuito - Cursos sem custo, abertos ao público."><?= e($editando['destaques'] ?? '') ?></textarea>

  <label for="p_obs">Observação (texto pequeno no rodapé do card)</label>
  <input type="text" id="p_obs" name="observacao" value="<?= e($editando['observacao'] ?? '') ?>">

  <div class="linha-campos">
    <div>
      <label for="p_ordem">Ordem de exibição</label>
      <input type="number" id="p_ordem" name="ordem" value="<?= (int) ($editando['ordem'] ?? 0) ?>">
    </div>
    <div>
      <label style="margin-top:38px">
        <input type="checkbox" name="ativo" <?= ($editando['ativo'] ?? 1) ? 'checked' : '' ?>> Parceiro ativo (visível na vitrine)
      </label>
    </div>
  </div>

  <div class="linha-campos">
    <div>
      <label for="p_exibir_valor">Tempo de exibição na página inicial</label>
      <input type="number" id="p_exibir_valor" name="exibir_valor" min="1"
             value="<?= e((string) ($editando['exibir_valor'] ?? '')) ?>" placeholder="Ex.: 30">
    </div>
    <div>
      <label for="p_exibir_unidade">Unidade</label>
      <select id="p_exibir_unidade" name="exibir_unidade">
        <option value="">— Sem prazo (sempre no ar) —</option>
        <?php foreach (['dias' => 'Dias', 'meses' => 'Meses', 'anos' => 'Anos'] as $u => $rotuloU): ?>
        <option value="<?= $u ?>" <?= ($editando['exibir_unidade'] ?? '') === $u ? 'selected' : '' ?>><?= $rotuloU ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <div class="ajuda">
    O prazo conta <strong>a partir de agora</strong> (do salvamento). Ao vencer, o parceiro sai da página inicial
    automaticamente, mas continua na <a href="../parceiros.php" target="_blank" rel="noopener" style="color:var(--cyan)">página de parceiros</a>.
    Deixe em branco para exibir sem prazo.
    <?php if (!empty($editando['expira_em'])): ?>
      <br>Prazo atual: <strong>no ar até <?= e(date('d/m/Y H:i', strtotime($editando['expira_em']))) ?></strong>
      (alterar o tempo acima reinicia a contagem).
    <?php endif; ?>
  </div>

  <div style="margin-top:22px">
    <button class="botao botao-primario" type="submit">Salvar parceiro</button>
    <a class="botao botao-claro" href="parceiros.php">Cancelar</a>
  </div>
</form>
<?php endif; ?>
<?php admin_rodape(); ?>
