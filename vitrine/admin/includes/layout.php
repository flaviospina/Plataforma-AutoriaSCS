<?php
/**
 * Layout compartilhado do painel administrativo (cabeçalho e rodapé).
 * Visual: padrão CECAPE / AutoriaSCS (tema escuro, navbar com logos).
 */

const ADMIN_LOGOS = [
    'SEEDUC'     => 'https://cecapescs.com.br/logos/logo-seeduc.png',
    'AutoriaSCS' => 'https://cecapescs.com.br/logos/logo-autoriascs.png',
    'CECAPE'     => 'https://cecapescs.com.br/logos/logo-cecape-new.png',
];

function admin_cabecalho(string $tituloPagina, string $menuAtivo = ''): void
{
    $usuario = usuario_logado();
    $menu = [
        'inicio'    => ['index.php',    '📋 Configurações'],
        'parceiros' => ['parceiros.php', '🤝 Parceiros'],
        'cursos'    => ['cursos.php',    '🎓 Cursos'],
        'logs'      => ['logs.php',      '🕒 Logs'],
        'usuarios'  => ['usuarios.php',  '👥 Usuários'],
        'perfil'    => ['perfil.php',    '👤 Perfil'],
    ];
    ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($tituloPagina) ?> · Admin AutoriaSCS</title>
<style>
:root{
  /* Fundos */
  --bg:#0b1628; --bg-nav:#0b1628; --bg-card:#0f2044; --bg-card2:#112240;
  --bg-input:#0b1e3d; --bg-hover:#142952;
  /* Bordas */
  --border:rgba(30,80,160,.35); --border-light:rgba(30,80,160,.55);
  /* Texto */
  --text:#ffffff; --text-muted:#8baac8; --text-dim:#3d6080;
  /* Acentos */
  --orange:#f97316; --cyan:#22d3ee; --purple:#a855f7; --green:#10b981;
  --red:#ef4444; --yellow:#eab308; --pink:#ec4899; --blue:#3b82f6; --teal:#0d9488;
  /* Gradientes */
  --grad-btn:linear-gradient(135deg,#1d4ed8 0%,#06b6d4 100%);
  --grad-orange:linear-gradient(135deg,#c2410c 0%,#f97316 100%);
  --grad-bg:linear-gradient(160deg,#0b1628 0%,#0a1f3a 50%,#091628 100%);
  /* Sombras */
  --shadow-lg:0 10px 48px rgba(0,0,0,.65); --shadow-card:0 2px 12px rgba(0,0,0,.4);
  --glow-blue:0 0 28px rgba(34,211,238,.18); --glow-orange:0 0 28px rgba(249,115,22,.28);
  /* Medidas */
  --radius:12px; --radius-sm:8px; --radius-xs:6px;
  --font:'Inter',system-ui,-apple-system,'Segoe UI',sans-serif;
  /* Compatibilidade com estilos inline das páginas */
  --azul:#22d3ee; --azul2:#3b82f6; --verde:#f97316; --fundo:#0b1628;
  --texto:#ffffff; --cinza:#8baac8; --borda:rgba(30,80,160,.35);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{font-family:var(--font);font-size:14px;background:var(--bg);background-image:var(--grad-bg);
  color:var(--text);min-height:100vh;display:flex;flex-direction:column;-webkit-font-smoothing:antialiased}
a{text-decoration:none;color:inherit}
::-webkit-scrollbar{width:5px;height:5px}
::-webkit-scrollbar-track{background:var(--bg)}
::-webkit-scrollbar-thumb{background:#1e4080;border-radius:3px}

/* ── NAVBAR ── */
.navbar{background:var(--bg-nav);border-bottom:1px solid var(--border);display:flex;align-items:center;
  justify-content:space-between;padding:10px 28px;min-height:60px;position:sticky;top:0;z-index:200;gap:14px;flex-wrap:wrap}
.navbar-brand{display:flex;align-items:center;gap:12px;flex-shrink:0}
.brand-logos{display:flex;align-items:center;gap:12px}
.brand-img{height:34px;width:auto;max-width:132px;object-fit:contain;filter:brightness(1.05)}
.brand-divider{width:1px;height:26px;background:rgba(255,255,255,.15);flex-shrink:0}
.brand-title{font-size:13px;font-weight:600;color:rgba(255,255,255,.7);white-space:nowrap;
  padding-left:12px;border-left:1px solid var(--border)}
.navbar-actions{display:flex;align-items:center;gap:8px;flex-shrink:0;flex-wrap:wrap}
.nav-link{padding:8px 15px;background:rgba(255,255,255,.04);border:1px solid var(--border);
  border-radius:var(--radius-sm);color:var(--text-muted);font-size:12.5px;font-weight:600;
  cursor:pointer;transition:all .2s;white-space:nowrap}
.nav-link:hover,.nav-link.ativo{background:rgba(34,211,238,.12);color:var(--cyan);border-color:rgba(34,211,238,.35)}
.user-badge{font-size:12.5px;font-weight:600;color:rgba(255,255,255,.75);white-space:nowrap;padding:0 4px}
.btn-exit{padding:7px 15px;font-size:12px;font-weight:600;border-radius:8px;background:transparent;
  border:1px solid var(--border-light);color:rgba(255,255,255,.65);transition:all .18s}
.btn-exit:hover{border-color:var(--red);color:var(--red)}

/* ── CONTEÚDO ── */
.conteudo{flex:1;padding:30px 24px;width:100%;max-width:1200px;margin:0 auto}
.topo{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:10px}
.topo h1{font-size:26px;font-weight:800;color:#fff;letter-spacing:-.02em}
.cartao{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);
  box-shadow:var(--shadow-card);padding:24px 28px;margin-bottom:16px}
.cartao h2{letter-spacing:.06em;text-transform:uppercase;font-size:12.5px !important;font-weight:800}

/* ── FORMULÁRIOS ── */
label{display:block;font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;
  color:var(--text-muted);margin:16px 0 7px}
input[type=text],input[type=url],input[type=email],input[type=password],input[type=number],input[type=date],textarea,select{
  width:100%;padding:11px 14px;background:var(--bg-input);border:1px solid var(--border);
  border-radius:var(--radius-sm);font-family:var(--font);font-size:14px;color:var(--text);outline:none;
  transition:border-color .2s,box-shadow .2s}
input:focus,textarea:focus,select:focus{border-color:var(--cyan);box-shadow:0 0 0 3px rgba(34,211,238,.12)}
input:hover:not(:focus),textarea:hover:not(:focus),select:hover:not(:focus){border-color:rgba(34,211,238,.3)}
input::placeholder,textarea::placeholder{color:var(--text-dim)}
input::-webkit-calendar-picker-indicator{filter:invert(.7) sepia(1) saturate(3) hue-rotate(150deg);cursor:pointer}
input[type=checkbox]{width:15px;height:15px;accent-color:var(--cyan);cursor:pointer;vertical-align:-2px;margin-right:6px}
input[type=file]{color:var(--text-muted);font-size:13px}
input[type=file]::file-selector-button{background:rgba(34,211,238,.12);border:1px solid rgba(34,211,238,.3);
  color:var(--cyan);border-radius:7px;padding:6px 12px;font-family:var(--font);font-size:12.5px;
  font-weight:600;cursor:pointer;margin-right:12px}
textarea{min-height:100px;resize:vertical}
select option{background:var(--bg-card2);color:var(--text)}
.linha-campos{display:grid;grid-template-columns:1fr 1fr;gap:0 18px}
.ajuda{font-size:12.5px;color:var(--text-muted);margin-top:5px;line-height:1.6}

/* ── BOTÕES ── */
.botao{display:inline-flex;align-items:center;justify-content:center;gap:7px;border:0;cursor:pointer;
  text-decoration:none;font-family:var(--font);font-weight:700;font-size:14px;padding:11px 24px;
  border-radius:var(--radius-sm);transition:all .18s;white-space:nowrap}
.botao-primario{background:var(--grad-btn);color:#fff}
.botao-primario:hover{transform:translateY(-2px);box-shadow:var(--glow-blue)}
.botao-verde{background:var(--grad-orange);color:#fff}
.botao-verde:hover{transform:translateY(-2px);box-shadow:var(--glow-orange)}
.botao-claro{background:transparent;color:var(--text-muted);border:1px solid var(--border);font-weight:600}
.botao-claro:hover{background:rgba(255,255,255,.04);color:var(--text);border-color:var(--border-light)}
.botao-perigo{background:rgba(239,68,68,.13);color:#f87171;border:1px solid rgba(239,68,68,.28);font-weight:600}
.botao-perigo:hover{filter:brightness(1.25)}
.botao-mini{padding:6px 13px;font-size:12px;border-radius:var(--radius-xs)}

/* ── TABELAS ── */
table{width:100%;border-collapse:collapse;font-size:13px}
th{background:var(--bg-card2);padding:12px 14px;font-size:11px;font-weight:800;letter-spacing:.1em;
  text-transform:uppercase;color:var(--text-muted);text-align:left;border-bottom:1px solid var(--border);white-space:nowrap}
td{padding:12px 14px;border-bottom:1px solid rgba(30,80,160,.2);vertical-align:middle;color:var(--text)}
tr:last-child td{border-bottom:none}
tbody tr,table tr{transition:background .15s}
tr:hover td{background:var(--bg-hover)}
.miniatura{width:70px;height:44px;object-fit:cover;border-radius:var(--radius-xs);border:1px solid var(--border)}

/* ── SELOS / CHIPS ── */
.selo{display:inline-flex;align-items:center;gap:4px;padding:3px 11px;border-radius:20px;
  font-size:11px;font-weight:700;letter-spacing:.03em;white-space:nowrap}
.selo-ativo{background:rgba(16,185,129,.12);color:#34d399;border:1px solid rgba(16,185,129,.25)}
.selo-inativo{background:rgba(255,255,255,.05);color:var(--text-muted);border:1px solid var(--border)}
.selo-acao-inserir{background:rgba(16,185,129,.12);color:#34d399;border:1px solid rgba(16,185,129,.25)}
.selo-acao-atualizar{background:rgba(59,130,246,.12);color:#60a5fa;border:1px solid rgba(59,130,246,.25)}
.selo-acao-excluir{background:rgba(239,68,68,.12);color:#f87171;border:1px solid rgba(239,68,68,.25)}
.selo-acao-login{background:rgba(16,185,129,.12);color:#34d399;border:1px solid rgba(16,185,129,.25)}
.selo-acao-login_falha{background:rgba(234,179,8,.12);color:#fde047;border:1px solid rgba(234,179,8,.25)}
.selo-acao-logout{background:rgba(255,255,255,.05);color:var(--text-muted);border:1px solid var(--border)}

/* ── AVISOS ── */
.aviso{padding:13px 18px;border-radius:var(--radius-sm);margin-bottom:18px;font-size:13.5px;line-height:1.6}
.aviso-ok{background:rgba(16,185,129,.1);color:#34d399;border:1px solid rgba(16,185,129,.35)}
.aviso-erro{background:rgba(239,68,68,.1);color:#f87171;border:1px solid rgba(239,68,68,.35)}

/* ── PAGINAÇÃO ── */
.paginacao{display:flex;gap:6px;margin-top:16px;flex-wrap:wrap}
.paginacao a,.paginacao span{padding:7px 13px;border-radius:var(--radius-xs);text-decoration:none;
  font-size:12.5px;font-weight:600;border:1px solid var(--border);background:rgba(255,255,255,.04);color:var(--text-muted)}
.paginacao a:hover{color:var(--cyan);border-color:rgba(34,211,238,.35);background:rgba(34,211,238,.08)}
.paginacao .atual{background:rgba(34,211,238,.14);color:var(--cyan);border-color:rgba(34,211,238,.45);font-weight:700}

/* ── LOGS (antes/depois) ── */
details.diff summary{cursor:pointer;color:var(--cyan);font-size:12.5px;font-weight:600}
details.diff pre{background:#091220;border:1px solid var(--border);color:#c9d8e6;padding:12px;
  border-radius:var(--radius-xs);font-size:12px;overflow-x:auto;margin-top:8px;max-width:520px;
  white-space:pre-wrap;word-break:break-word}

/* ── RODAPÉ ── */
.site-footer{background:var(--teal);padding:16px 24px;margin-top:auto}
.footer-inner{display:flex;align-items:center;justify-content:center;gap:10px;flex-wrap:wrap;
  font-size:12px;color:rgba(255,255,255,.75);text-transform:uppercase;letter-spacing:.04em;text-align:center}
.footer-inner .sep{color:rgba(255,255,255,.35)}

/* ── RESPONSIVO ── */
@media(max-width:900px){
  .navbar{padding:10px 14px}
  .brand-title{display:none}
  .brand-img{height:26px}
  .conteudo{padding:22px 14px}
  .cartao{padding:18px 16px}
  .linha-campos{grid-template-columns:1fr}
}
</style>
</head>
<body>
<nav class="navbar">
  <div class="navbar-brand">
    <div class="brand-logos">
      <?php $i = 0; foreach (ADMIN_LOGOS as $alt => $src): ?>
        <?php if ($i++ > 0): ?><div class="brand-divider"></div><?php endif; ?>
        <img class="brand-img" src="<?= e($src) ?>" alt="<?= e($alt) ?>">
      <?php endforeach; ?>
    </div>
    <span class="brand-title">Painel da Vitrine</span>
  </div>
  <div class="navbar-actions">
    <?php foreach ($menu as $chave => [$url, $rotulo]): ?>
    <a class="nav-link <?= $menuAtivo === $chave ? 'ativo' : '' ?>" href="<?= e($url) ?>"><?= $rotulo ?></a>
    <?php endforeach; ?>
    <a class="nav-link" href="../home.php" target="_blank" rel="noopener">👁️ Ver vitrine</a>
    <span class="user-badge">👤 <?= e($usuario['nome'] ?? '') ?></span>
    <a class="btn-exit" href="logout.php">Sair</a>
  </div>
</nav>
<main class="conteudo">
<?php
}

function admin_rodape(): void
{
    ?>
</main>
<footer class="site-footer">
  <div class="footer-inner">
    CECAPE - Centro de Capacitação de Profissionais da Educação
    <span class="sep">•</span>
    Secretaria Municipal de Educação de São Caetano do Sul
  </div>
</footer>
</body>
</html>
<?php
}

/** Exibe mensagem flash (sucesso/erro) armazenada na sessão. */
function mostrar_flash(): void
{
    if (!empty($_SESSION['flash_ok'])) {
        echo '<div class="aviso aviso-ok">' . e($_SESSION['flash_ok']) . '</div>';
        unset($_SESSION['flash_ok']);
    }
    if (!empty($_SESSION['flash_erro'])) {
        echo '<div class="aviso aviso-erro">' . e($_SESSION['flash_erro']) . '</div>';
        unset($_SESSION['flash_erro']);
    }
}
