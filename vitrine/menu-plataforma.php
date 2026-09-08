<?php
/**
 * Menu da Plataforma AutoriaSCS / CECAPE — componente reutilizável.
 * Réplica fiel do cabeçalho do tema Almondb da plataforma.
 *
 * COMO USAR em qualquer página PHP do sistema (logo após a tag <body>):
 *
 *   <?php
 *   $MENU_ATIVO = 'Podcast'; // rótulo do item a destacar ('' = nenhum)
 *   include '/home1/itthri79/cecapescs.com.br/plataforma/vitrine/menu-plataforma.php';
 *   ?>
 *
 * Todas as classes têm o prefixo "mp-" para não conflitar com o CSS da
 * página que fizer o include. O componente é 100% autossuficiente
 * (estilos + HTML + script do menu mobile).
 */

if (!function_exists('mp_e')) {
    function mp_e($texto)
    {
        return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
    }
}

$MENU_ATIVO = $MENU_ATIVO ?? '';

$MP_PLATAFORMA = 'https://eadcecape.com.br/ava';
$MP_ITENS = [
    ['rotulo' => 'Sobre',                  'url' => $MP_PLATAFORMA . '/#block01'],
    ['rotulo' => 'Galeria de imagens',     'url' => $MP_PLATAFORMA . '/#block04'],
    ['rotulo' => 'Cursos',                 'url' => $MP_PLATAFORMA . '/#block07'],
    ['rotulo' => 'Games 🎮',               'url' => $MP_PLATAFORMA . '/mod/games/view.php?id=1542'],
    ['rotulo' => 'Podcast',                'url' => 'https://cecapescs.com.br/ferramenta_moodle/podcast.php'],
    ['rotulo' => 'Depoimentos',            'url' => $MP_PLATAFORMA . '/#block10'],
    ['rotulo' => 'Fale com o CECAPE',      'url' => $MP_PLATAFORMA . '/#block18'],
    ['rotulo' => 'Meus cursos',            'url' => $MP_PLATAFORMA . '/my/courses.php'],
    ['rotulo' => 'Catálogo de Cursos',     'url' => $MP_PLATAFORMA . '/course/index.php?categoryid=1&browse=courses&perpage=20&page=0'],
    ['rotulo' => 'Biblioteca de Recursos', 'url' => $MP_PLATAFORMA . '/course/index.php?categoryid=8'],
    ['rotulo' => 'Nossos Parceiros',       'url' => 'https://cecapescs.com.br/plataforma/vitrine/parceiros.php'],
];
// Logo oficial servido pelo Moodle, com reserva caso a URL mude
$MP_LOGO         = $MP_PLATAFORMA . '/pluginfile.php/1/core_admin/logo/0x200/1788873887/Logo-oficial-ambiente-cecape-2.png';
$MP_LOGO_RESERVA = 'https://cecapescs.com.br/logos/logo-cecape-new.png';
$MP_LOGIN        = $MP_PLATAFORMA . '/login/index.php';
?>
<style>
.mp-navbar{background:#fff;position:sticky;top:0;z-index:300;box-shadow:0 1px 2px rgba(0,0,0,.08);
  font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif}
.mp-navbar *{box-sizing:border-box;margin:0;padding:0}
.mp-inner{max-width:1560px;margin:0 auto;display:flex;align-items:center;gap:26px;padding:0 28px;min-height:64px}
.mp-logo{display:flex;align-items:center;flex-shrink:0;text-decoration:none}
.mp-logo img{height:42px;width:auto;object-fit:contain;display:block}
.mp-links{display:flex;align-items:center;gap:26px;flex:1;flex-wrap:nowrap;overflow-x:auto;scrollbar-width:none}
.mp-links::-webkit-scrollbar{display:none}
.mp-links a{color:#1d2125;font-size:16.5px;font-weight:700;white-space:nowrap;padding:20px 0;
  text-decoration:none;transition:color .15s ease}
.mp-links a:hover,.mp-links a.mp-ativo{color:#eba52d}
.mp-user{margin-left:auto;flex-shrink:0;display:flex;align-items:center;gap:5px;color:#6b7280;text-decoration:none}
.mp-user .mp-avatar{width:36px;height:36px;border-radius:50%;background:#e9ecef;display:flex;
  align-items:center;justify-content:center;font-size:19px;color:#8a92a6}
.mp-user .mp-seta{font-size:11px}
.mp-burger{display:none;margin-left:auto;background:none;border:1px solid #dde1e6;border-radius:8px;
  font-size:20px;line-height:1;padding:7px 12px;cursor:pointer;color:#1d2125}
@media(max-width:1080px){
  .mp-links{position:absolute;top:64px;left:0;right:0;background:#fff;flex-direction:column;
    align-items:stretch;gap:0;padding:8px 0;box-shadow:0 12px 24px rgba(0,0,0,.12);display:none;overflow:visible}
  .mp-links.mp-aberto{display:flex}
  .mp-links a{padding:13px 24px;border-bottom:1px solid #f0f2f5}
  .mp-burger{display:block}
  .mp-user{margin-left:8px}
}
@media(max-width:640px){.mp-logo img{height:34px}}
</style>
<header class="mp-navbar">
  <div class="mp-inner">
    <a class="mp-logo" href="<?= mp_e($MP_PLATAFORMA) ?>/">
      <img src="<?= mp_e($MP_LOGO) ?>" alt="CECAPE"
           onerror="this.onerror=null;this.src='<?= mp_e($MP_LOGO_RESERVA) ?>'">
    </a>
    <button class="mp-burger" type="button" aria-label="Abrir menu"
            onclick="document.getElementById('mpLinks').classList.toggle('mp-aberto')">☰</button>
    <nav class="mp-links" id="mpLinks" aria-label="Navegação no site">
      <?php foreach ($MP_ITENS as $mpItem): ?>
      <a href="<?= mp_e($mpItem['url']) ?>"
         class="<?= $mpItem['rotulo'] === $MENU_ATIVO ? 'mp-ativo' : '' ?>"><?= mp_e($mpItem['rotulo']) ?></a>
      <?php endforeach; ?>
    </nav>
    <a class="mp-user" href="<?= mp_e($MP_LOGIN) ?>" title="Entrar na plataforma">
      <span class="mp-avatar">👤</span><span class="mp-seta">▾</span>
    </a>
  </div>
</header>
