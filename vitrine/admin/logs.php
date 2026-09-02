<?php
require __DIR__ . '/includes/init.php';
require __DIR__ . '/includes/layout.php';
exigir_login();

$rotulosAcao = [
    'inserir'     => 'Inserção',
    'atualizar'   => 'Atualização',
    'excluir'     => 'Exclusão',
    'login'       => 'Login',
    'login_falha' => 'Login (falha)',
    'logout'      => 'Logout',
];
$rotulosEntidade = [
    'parceiro'     => 'Parceiro',
    'curso'        => 'Curso',
    'configuracao' => 'Configuração',
    'usuario'      => 'Usuário',
    'sessao'       => 'Sessão',
];

/* ---- Filtros ---- */
$filtros = [];
$parametros = [];

if (!empty($_GET['acao_filtro']) && isset($rotulosAcao[$_GET['acao_filtro']])) {
    $filtros[] = 'acao = ?';
    $parametros[] = $_GET['acao_filtro'];
}
if (!empty($_GET['entidade']) && isset($rotulosEntidade[$_GET['entidade']])) {
    $filtros[] = 'entidade = ?';
    $parametros[] = $_GET['entidade'];
}
if (!empty($_GET['data_inicio'])) {
    $filtros[] = 'criado_em >= ?';
    $parametros[] = $_GET['data_inicio'] . ' 00:00:00';
}
if (!empty($_GET['data_fim'])) {
    $filtros[] = 'criado_em <= ?';
    $parametros[] = $_GET['data_fim'] . ' 23:59:59';
}
$where = $filtros ? 'WHERE ' . implode(' AND ', $filtros) : '';

/* ---- Paginação ---- */
$porPagina = 30;
$pagina = max(1, (int) ($_GET['pagina'] ?? 1));

$st = db()->prepare("SELECT COUNT(*) FROM logs_atividade $where");
$st->execute($parametros);
$totalRegistros = (int) $st->fetchColumn();
$totalPaginas = max(1, (int) ceil($totalRegistros / $porPagina));
$pagina = min($pagina, $totalPaginas);
$deslocamento = ($pagina - 1) * $porPagina;

$st = db()->prepare(
    "SELECT * FROM logs_atividade $where ORDER BY criado_em DESC, id DESC LIMIT $porPagina OFFSET $deslocamento"
);
$st->execute($parametros);
$registros = $st->fetchAll();

/** Formata o JSON de antes/depois para exibição amigável. */
function formatar_json(?string $json): string
{
    if ($json === null || $json === '') {
        return '—';
    }
    $dados = json_decode($json, true);
    return e(json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

function url_pagina(int $p): string
{
    $query = $_GET;
    $query['pagina'] = $p;
    return 'logs.php?' . http_build_query($query);
}

admin_cabecalho('Logs de atividade', 'logs');
?>
<div class="topo">
  <h1>Logs de atividade</h1>
  <span class="ajuda"><?= $totalRegistros ?> registro(s) encontrado(s)</span>
</div>
<?php mostrar_flash(); ?>

<form class="cartao" method="get" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:0 16px;align-items:end">
  <div>
    <label for="f_acao">Ação</label>
    <select id="f_acao" name="acao_filtro">
      <option value="">Todas</option>
      <?php foreach ($rotulosAcao as $valor => $rotulo): ?>
      <option value="<?= $valor ?>" <?= ($_GET['acao_filtro'] ?? '') === $valor ? 'selected' : '' ?>><?= $rotulo ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div>
    <label for="f_ent">Item</label>
    <select id="f_ent" name="entidade">
      <option value="">Todos</option>
      <?php foreach ($rotulosEntidade as $valor => $rotulo): ?>
      <option value="<?= $valor ?>" <?= ($_GET['entidade'] ?? '') === $valor ? 'selected' : '' ?>><?= $rotulo ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div>
    <label for="f_ini">De</label>
    <input type="date" id="f_ini" name="data_inicio" value="<?= e($_GET['data_inicio'] ?? '') ?>">
  </div>
  <div>
    <label for="f_fim">Até</label>
    <input type="date" id="f_fim" name="data_fim" value="<?= e($_GET['data_fim'] ?? '') ?>">
  </div>
  <div style="padding-bottom:2px">
    <button class="botao botao-primario botao-mini" type="submit">Filtrar</button>
    <a class="botao botao-claro botao-mini" href="logs.php">Limpar</a>
  </div>
</form>

<div class="cartao" style="overflow-x:auto">
<table>
  <tr><th>Data e hora</th><th>Usuário</th><th>Ação</th><th>Item</th><th>Descrição</th><th>Alterações</th><th>IP</th></tr>
  <?php if (!$registros): ?>
  <tr><td colspan="7" style="text-align:center;color:var(--cinza)">Nenhum registro encontrado para os filtros selecionados.</td></tr>
  <?php endif; ?>
  <?php foreach ($registros as $log): ?>
  <tr>
    <td style="white-space:nowrap"><?= e(date('d/m/Y H:i:s', strtotime($log['criado_em']))) ?></td>
    <td><?= e($log['usuario_nome'] ?? 'Sistema') ?></td>
    <td><span class="selo selo-acao-<?= e($log['acao']) ?>"><?= e($rotulosAcao[$log['acao']] ?? $log['acao']) ?></span></td>
    <td><?= e($rotulosEntidade[$log['entidade']] ?? $log['entidade']) ?><?= $log['entidade_id'] ? ' #' . (int) $log['entidade_id'] : '' ?></td>
    <td><?= e($log['descricao']) ?></td>
    <td>
      <?php if ($log['dados_anteriores'] || $log['dados_novos']): ?>
      <details class="diff">
        <summary>Ver detalhes</summary>
        <?php if ($log['dados_anteriores']): ?>
        <strong style="font-size:12px;color:#f87171">Antes:</strong>
        <pre><?= formatar_json($log['dados_anteriores']) ?></pre>
        <?php endif; ?>
        <?php if ($log['dados_novos']): ?>
        <strong style="font-size:12px;color:#34d399">Depois:</strong>
        <pre><?= formatar_json($log['dados_novos']) ?></pre>
        <?php endif; ?>
      </details>
      <?php else: ?>—<?php endif; ?>
    </td>
    <td style="white-space:nowrap"><?= e($log['ip']) ?></td>
  </tr>
  <?php endforeach; ?>
</table>

<?php if ($totalPaginas > 1): ?>
<div class="paginacao">
  <?php if ($pagina > 1): ?><a href="<?= e(url_pagina($pagina - 1)) ?>">« Anterior</a><?php endif; ?>
  <?php for ($p = max(1, $pagina - 3); $p <= min($totalPaginas, $pagina + 3); $p++): ?>
    <?php if ($p === $pagina): ?><span class="atual"><?= $p ?></span>
    <?php else: ?><a href="<?= e(url_pagina($p)) ?>"><?= $p ?></a><?php endif; ?>
  <?php endfor; ?>
  <?php if ($pagina < $totalPaginas): ?><a href="<?= e(url_pagina($pagina + 1)) ?>">Próxima »</a><?php endif; ?>
</div>
<?php endif; ?>
</div>
<?php admin_rodape(); ?>
