<?php
/**
 * Página pública de parceiros da Plataforma AutoriaSCS.
 * Lista TODOS os parceiros ativos em ordem cronológica de inserção,
 * inclusive os que já saíram da página inicial por fim do prazo de exibição.
 */

declare(strict_types=1);

$configPath = __DIR__ . '/config.php';
if (!is_file($configPath)) {
    http_response_code(500);
    exit('Configuração ausente no servidor.');
}
$CONFIG = require $configPath;
date_default_timezone_set('America/Sao_Paulo');

/* ------------------------------------------------------------------
 * MENU DA PLATAFORMA (réplica do cabeçalho do tema Almondb)
 * Ajuste as URLs abaixo copiando cada link do menu da página inicial
 * da plataforma (botão direito no item → "Copiar link").
 * O item marcado com 'ativo' => true fica destacado em âmbar.
 * ------------------------------------------------------------------ */
$PLATAFORMA_URL = 'https://eadcecape.com.br/ava';
$MENU = [
    ['rotulo' => 'Sobre',                  'url' => $PLATAFORMA_URL . '/#sobre'],
    ['rotulo' => 'Galeria de imagens',     'url' => $PLATAFORMA_URL . '/#galeria'],
    ['rotulo' => 'Cursos',                 'url' => $PLATAFORMA_URL . '/#cursos'],
    ['rotulo' => 'Games 🎮',               'url' => $PLATAFORMA_URL . '/#games'],
    ['rotulo' => 'Podcast',                'url' => $PLATAFORMA_URL . '/#podcast'],
    ['rotulo' => 'Depoimentos',            'url' => $PLATAFORMA_URL . '/#depoimentos'],
    ['rotulo' => 'Fale com o CECAPE',      'url' => $PLATAFORMA_URL . '/#contato'],
    ['rotulo' => 'Meus cursos',            'url' => $PLATAFORMA_URL . '/my/courses.php'],
    ['rotulo' => 'Catálogo de Cursos',     'url' => $PLATAFORMA_URL . '/course/index.php'],
    ['rotulo' => 'Biblioteca de Recursos', 'url' => $PLATAFORMA_URL . '/'],
    ['rotulo' => 'Nossos Parceiros',       'url' => 'parceiros.php', 'ativo' => true],
];
$LOGO_MENU  = 'https://cecapescs.com.br/logos/logo-cecape-new.png';
$LOGIN_URL  = $PLATAFORMA_URL . '/login/index.php';

function ep(?string $texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

$erro = null;
$parceiros = [];
try {
    $c = $CONFIG['db'];
    $pdo = new PDO(
        "mysql:host={$c['host']};dbname={$c['nome']};charset={$c['charset']}",
        $c['usuario'],
        $c['senha'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
    $parceiros = $pdo->query(
        'SELECT nome, titulo, descricao, imagem_url, link_url, texto_botao, destaques, observacao, expira_em, criado_em
           FROM parceiros WHERE ativo = 1 ORDER BY criado_em, id'
    )->fetchAll();
} catch (Throwable $ex) {
    $erro = 'Não foi possível carregar os parceiros no momento. Tente novamente mais tarde.';
}

$meses = [1 => 'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho',
    'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
function data_extenso(string $data): string
{
    global $meses;
    $ts = strtotime($data);
    return date('d', $ts) . ' de ' . $meses[(int) date('n', $ts)] . ' de ' . date('Y', $ts);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Parceiros · Plataforma AutoriaSCS</title>
<meta name="description" content="Conheça todos os parceiros da Plataforma AutoriaSCS, em ordem cronológica de chegada.">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',system-ui,-apple-system,Arial,sans-serif;background:#f2f5fb;color:#1f2937;
  min-height:100vh;display:flex;flex-direction:column}
img{max-width:100%}
a{text-decoration:none}
/* ── Barra de navegação (réplica do cabeçalho da plataforma / tema Almondb) ── */
.navbar{background:#fff;position:sticky;top:0;z-index:300;box-shadow:0 1px 2px rgba(0,0,0,.08);
  font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif}
.navbar-inner{max-width:1560px;margin:0 auto;display:flex;align-items:center;gap:26px;
  padding:0 28px;min-height:64px}
.navbar-logo{display:flex;align-items:center;flex-shrink:0}
.navbar-logo img{height:42px;width:auto;object-fit:contain;display:block}
.navbar-links{display:flex;align-items:center;gap:26px;flex:1;flex-wrap:nowrap;overflow-x:auto;
  scrollbar-width:none}
.navbar-links::-webkit-scrollbar{display:none}
.navbar-links a{color:#1d2125;font-size:16.5px;font-weight:700;white-space:nowrap;padding:20px 0;
  transition:color .15s ease}
.navbar-links a:hover,.navbar-links a.ativo{color:#eba52d}
.navbar-user{margin-left:auto;flex-shrink:0;display:flex;align-items:center;gap:5px;color:#6b7280}
.navbar-user .avatar{width:36px;height:36px;border-radius:50%;background:#e9ecef;display:flex;
  align-items:center;justify-content:center;font-size:19px;color:#8a92a6}
.navbar-user .seta{font-size:11px}
.navbar-burger{display:none;margin-left:auto;background:none;border:1px solid #dde1e6;border-radius:8px;
  font-size:20px;line-height:1;padding:7px 12px;cursor:pointer;color:#1d2125}
@media(max-width:1080px){
  .navbar-links{position:absolute;top:64px;left:0;right:0;background:#fff;flex-direction:column;
    align-items:stretch;gap:0;padding:8px 0;box-shadow:0 12px 24px rgba(0,0,0,.12);display:none;overflow:visible}
  .navbar-links.aberto{display:flex}
  .navbar-links a{padding:13px 24px;border-bottom:1px solid #f0f2f5}
  .navbar-burger{display:block}
  .navbar-user{margin-left:8px}
}
/* cabeçalho */
.cabecalho{text-align:center;padding:40px 20px 8px}
.cabecalho h1{font-size:clamp(26px,3.6vw,36px);font-weight:800;color:#071f8f;margin-bottom:8px}
.cabecalho p{color:#5b6475;font-size:16px;max-width:640px;margin:0 auto;line-height:1.6}
.traco{width:74px;height:5px;border-radius:99px;margin:16px auto 0;background:linear-gradient(90deg,#2898a4,#9dff00)}
/* conteúdo */
.conteudo{flex:1;max-width:1100px;margin:0 auto;padding:26px 20px 50px;width:100%}
.parceiro{background:#fff;border:1px solid rgba(7,31,143,.08);border-radius:24px;overflow:hidden;
  box-shadow:0 14px 38px rgba(8,28,105,.12);margin-bottom:30px;animation:surgir .55s ease both}
@keyframes surgir{from{opacity:0;transform:translateY(18px)}to{opacity:1;transform:none}}
.parceiro-capa{width:100%;max-height:340px;object-fit:cover;display:block}
.parceiro-corpo{padding:28px 30px}
.selo-linha{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:14px}
.selo{display:inline-flex;align-items:center;gap:6px;padding:5px 14px;border-radius:999px;font-size:12.5px;font-weight:700}
.selo-desde{background:#eef2fb;color:#071f8f}
.selo-no-ar{background:#e7ffcc;color:#3f6212}
.selo-encerrado{background:#f1f2f6;color:#6b7280}
.parceiro h2{color:#071f8f;font-size:24px;margin:0 0 6px}
.parceiro h3{color:#2898a4;font-size:16px;font-weight:700;margin:0 0 12px}
.parceiro p.desc{line-height:1.8;font-size:15.5px;color:#374151;margin:0 0 18px}
.destaques{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:12px;margin-bottom:18px}
.destaque{display:flex;gap:12px;align-items:flex-start;padding:13px 15px;border-radius:14px;background:#fbfcff;
  border:1px solid rgba(7,31,143,.08)}
.ponto{width:12px;height:12px;border-radius:50%;background:#9dff00;margin-top:5px;
  box-shadow:0 0 0 5px rgba(157,255,0,.16);flex:0 0 auto}
.destaque strong{display:block;color:#04145d;margin-bottom:2px;font-size:14px}
.destaque span{color:#5b6475;line-height:1.55;font-size:13.5px}
.botao{display:inline-block;background:linear-gradient(135deg,#071f8f,#0d37d0);color:#fff;font-weight:700;
  font-size:15px;padding:13px 26px;border-radius:999px;transition:transform .18s ease,box-shadow .18s ease;
  box-shadow:0 10px 24px rgba(13,55,208,.28)}
.botao:hover{transform:translateY(-2px)}
.obs{margin-top:14px;color:#5b6475;font-size:13px;line-height:1.6}
.vazio{background:#fff;border:1px solid rgba(7,31,143,.08);border-radius:20px;text-align:center;
  color:#5b6475;padding:50px 20px}
.erro{background:#fde8e8;color:#b91c1c;border:1px solid #f5c2c2;border-radius:14px;padding:16px 20px;text-align:center}
.voltar{display:block;text-align:center;margin-top:8px}
.voltar a{color:#2898a4;font-weight:700;font-size:14.5px}
.voltar a:hover{text-decoration:underline}
/* rodapé */
.rodape{background:#0d9488;padding:16px 24px;text-align:center;font-size:12px;color:rgba(255,255,255,.85);
  text-transform:uppercase;letter-spacing:.04em;line-height:1.8}
@media(max-width:640px){.parceiro-corpo{padding:20px 18px}.navbar-logo img{height:34px}}
</style>
</head>
<body>
<header class="navbar">
  <div class="navbar-inner">
    <a class="navbar-logo" href="<?= ep($PLATAFORMA_URL) ?>/">
      <img src="<?= ep($LOGO_MENU) ?>" alt="CECAPE">
    </a>
    <button class="navbar-burger" type="button" aria-label="Abrir menu"
            onclick="document.getElementById('menuLinks').classList.toggle('aberto')">☰</button>
    <nav class="navbar-links" id="menuLinks">
      <?php foreach ($MENU as $item): ?>
      <a href="<?= ep($item['url']) ?>" class="<?= !empty($item['ativo']) ? 'ativo' : '' ?>"><?= ep($item['rotulo']) ?></a>
      <?php endforeach; ?>
    </nav>
    <a class="navbar-user" href="<?= ep($LOGIN_URL) ?>" title="Entrar na plataforma">
      <span class="avatar">👤</span><span class="seta">▾</span>
    </a>
  </div>
</header>

<section class="cabecalho">
  <h1>🤝 Nossos parceiros</h1>
  <p>Instituições que caminham conosco por uma educação mais inclusiva e transformadora, em ordem de chegada à plataforma.</p>
  <div class="traco"></div>
</section>

<main class="conteudo">
<?php if ($erro): ?>
  <div class="erro"><?= ep($erro) ?></div>
<?php elseif (!$parceiros): ?>
  <div class="vazio">Ainda não há parceiros cadastrados.</div>
<?php else: ?>
  <?php foreach ($parceiros as $i => $p):
      $noAr = $p['expira_em'] === null || strtotime($p['expira_em']) > time();
      $itens = preg_split('/\r\n|\r|\n/', (string) $p['destaques'], -1, PREG_SPLIT_NO_EMPTY) ?: [];
  ?>
  <article class="parceiro" style="animation-delay:<?= $i * 0.12 ?>s">
    <?php if ($p['imagem_url']): ?>
    <img class="parceiro-capa" src="<?= ep($p['imagem_url']) ?>" alt="<?= ep($p['nome']) ?>" loading="lazy"
         onerror="this.style.display='none'">
    <?php endif; ?>
    <div class="parceiro-corpo">
      <div class="selo-linha">
        <span class="selo selo-desde">📅 Parceiro desde <?= ep(data_extenso($p['criado_em'])) ?></span>
        <?php if ($noAr): ?>
        <span class="selo selo-no-ar">⭐ Em destaque na página inicial</span>
        <?php else: ?>
        <span class="selo selo-encerrado">Divulgação na página inicial encerrada</span>
        <?php endif; ?>
      </div>
      <h2><?= ep($p['nome']) ?></h2>
      <?php if ($p['titulo']): ?><h3><?= ep($p['titulo']) ?></h3><?php endif; ?>
      <?php if ($p['descricao']): ?><p class="desc"><?= ep($p['descricao']) ?></p><?php endif; ?>

      <?php if ($itens): ?>
      <div class="destaques">
        <?php foreach ($itens as $item):
            $item = trim($item);
            $sep = strpos($item, ' - ');
        ?>
        <div class="destaque">
          <div class="ponto"></div>
          <div>
            <?php if ($sep > 0): ?>
            <strong><?= ep(substr($item, 0, $sep)) ?></strong>
            <span><?= ep(substr($item, $sep + 3)) ?></span>
            <?php else: ?>
            <span><?= ep($item) ?></span>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <?php if ($p['link_url']): ?>
      <a class="botao" href="<?= ep($p['link_url']) ?>" target="_blank" rel="noopener noreferrer">
        <?= ep($p['texto_botao'] ?: 'Acessar plataforma') ?></a>
      <?php endif; ?>
      <?php if ($p['observacao']): ?><p class="obs"><?= ep($p['observacao']) ?></p><?php endif; ?>
    </div>
  </article>
  <?php endforeach; ?>
<?php endif; ?>
  <div class="voltar"><a href="javascript:history.back()">← Voltar</a></div>
</main>

<footer class="rodape">
  CECAPE - Centro de Capacitação de Profissionais da Educação<br>
  Secretaria Municipal de Educação de São Caetano do Sul
</footer>
</body>
</html>
