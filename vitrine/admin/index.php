<?php
require __DIR__ . '/includes/init.php';
require __DIR__ . '/includes/layout.php';
exigir_login();

$chavesEditaveis = [
    'titulo_parceiros'    => 'Título da seção de parceiros',
    'subtitulo_parceiros' => 'Subtítulo da seção de parceiros',
    'titulo_cursos'       => 'Título da seção de cursos',
    'subtitulo_cursos'    => 'Subtítulo da seção de cursos',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $anteriores = obter_configuracoes();
    $alteradas = [];

    $st = db()->prepare('UPDATE configuracoes SET valor = ? WHERE chave = ?');
    foreach ($chavesEditaveis as $chave => $rotulo) {
        $novo = trim($_POST[$chave] ?? '');
        if (($anteriores[$chave] ?? '') !== $novo) {
            $st->execute([$novo, $chave]);
            $alteradas[$chave] = ['antes' => $anteriores[$chave] ?? '', 'depois' => $novo];
        }
    }
    foreach (['exibir_parceiros', 'exibir_cursos'] as $chave) {
        $novo = isset($_POST[$chave]) ? '1' : '0';
        if (($anteriores[$chave] ?? '1') !== $novo) {
            $st->execute([$novo, $chave]);
            $alteradas[$chave] = ['antes' => $anteriores[$chave] ?? '1', 'depois' => $novo];
        }
    }

    if ($alteradas) {
        registrar_log(
            'atualizar',
            'configuracao',
            null,
            'Configurações da vitrine alteradas: ' . implode(', ', array_keys($alteradas)),
            array_map(fn ($a) => $a['antes'], $alteradas),
            array_map(fn ($a) => $a['depois'], $alteradas)
        );
        $_SESSION['flash_ok'] = 'Configurações salvas com sucesso.';
    } else {
        $_SESSION['flash_ok'] = 'Nenhuma alteração foi feita.';
    }
    header('Location: index.php');
    exit;
}

$config = obter_configuracoes();
$totais = [
    'parceiros' => (int) db()->query('SELECT COUNT(*) FROM parceiros WHERE ativo = 1')->fetchColumn(),
    'cursos'    => (int) db()->query('SELECT COUNT(*) FROM cursos WHERE ativo = 1')->fetchColumn(),
    'logs'      => (int) db()->query('SELECT COUNT(*) FROM logs_atividade')->fetchColumn(),
];

admin_cabecalho('Configurações', 'inicio');
?>
<div class="topo">
  <h1>Configurações da vitrine</h1>
</div>
<?php mostrar_flash(); ?>

<div class="cartao" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px;text-align:center">
  <div><div style="font-size:40px;font-weight:900;letter-spacing:-.03em;color:var(--orange)"><?= $totais['parceiros'] ?></div><div class="ajuda">Parceiros ativos</div></div>
  <div><div style="font-size:40px;font-weight:900;letter-spacing:-.03em;color:var(--cyan)"><?= $totais['cursos'] ?></div><div class="ajuda">Cursos ativos</div></div>
  <div><div style="font-size:40px;font-weight:900;letter-spacing:-.03em;color:var(--green)"><?= $totais['logs'] ?></div><div class="ajuda">Registros de log</div></div>
</div>

<form class="cartao" method="post">
  <?= csrf_campo() ?>
  <h2 style="color:var(--azul);font-size:18px;margin-bottom:4px">Seção 1 · Parceiros</h2>
  <label>
    <input type="checkbox" name="exibir_parceiros" <?= ($config['exibir_parceiros'] ?? '1') === '1' ? 'checked' : '' ?>>
    Exibir a seção de parceiros na página inicial
  </label>
  <label for="titulo_parceiros">Título da seção</label>
  <input type="text" id="titulo_parceiros" name="titulo_parceiros" value="<?= e($config['titulo_parceiros'] ?? '') ?>">
  <label for="subtitulo_parceiros">Subtítulo da seção</label>
  <input type="text" id="subtitulo_parceiros" name="subtitulo_parceiros" value="<?= e($config['subtitulo_parceiros'] ?? '') ?>">

  <h2 style="color:var(--azul);font-size:18px;margin:26px 0 4px">Seção 2 · Novos cursos</h2>
  <label>
    <input type="checkbox" name="exibir_cursos" <?= ($config['exibir_cursos'] ?? '1') === '1' ? 'checked' : '' ?>>
    Exibir a seção de cursos na página inicial
  </label>
  <label for="titulo_cursos">Título da seção</label>
  <input type="text" id="titulo_cursos" name="titulo_cursos" value="<?= e($config['titulo_cursos'] ?? '') ?>">
  <label for="subtitulo_cursos">Subtítulo da seção</label>
  <input type="text" id="subtitulo_cursos" name="subtitulo_cursos" value="<?= e($config['subtitulo_cursos'] ?? '') ?>">

  <div style="margin-top:24px">
    <button class="botao botao-primario" type="submit">Salvar configurações</button>
    <a class="botao botao-claro" href="../home.php" target="_blank" rel="noopener">Visualizar vitrine</a>
  </div>
</form>
<?php admin_rodape(); ?>
