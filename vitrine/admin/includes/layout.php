<?php
/**
 * Layout compartilhado do painel administrativo (cabeçalho e rodapé).
 */

function admin_cabecalho(string $tituloPagina, string $menuAtivo = ''): void
{
    $usuario = usuario_logado();
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
  --azul:#071f8f; --azul2:#0d37d0; --teal:#2898a4; --verde:#9dff00;
  --fundo:#f2f5fb; --texto:#1f2937; --cinza:#5b6475; --borda:#e3e8f2;
}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',system-ui,-apple-system,Arial,sans-serif;background:var(--fundo);color:var(--texto);min-height:100vh;display:flex}
a{color:var(--teal)}
/* ---- barra lateral ---- */
.lateral{width:240px;background:linear-gradient(180deg,var(--azul) 0%,var(--azul2) 130%);color:#fff;padding:24px 16px;display:flex;flex-direction:column;gap:6px;position:sticky;top:0;height:100vh;flex-shrink:0}
.lateral .marca{font-size:19px;font-weight:800;margin-bottom:22px;line-height:1.3}
.lateral .marca small{display:block;font-weight:400;font-size:12px;color:rgba(255,255,255,.7)}
.lateral a.item{display:flex;align-items:center;gap:10px;color:rgba(255,255,255,.85);text-decoration:none;padding:11px 14px;border-radius:12px;font-size:14.5px;transition:background .15s}
.lateral a.item:hover{background:rgba(255,255,255,.12)}
.lateral a.item.ativo{background:var(--verde);color:var(--azul);font-weight:700}
.lateral .rodape-lateral{margin-top:auto;font-size:12.5px;color:rgba(255,255,255,.75);border-top:1px solid rgba(255,255,255,.18);padding-top:14px}
.lateral .rodape-lateral a{color:#fff}
/* ---- conteúdo ---- */
.conteudo{flex:1;padding:30px 36px;max-width:1200px}
.topo{display:flex;justify-content:space-between;align-items:center;margin-bottom:26px;flex-wrap:wrap;gap:10px}
.topo h1{font-size:24px;color:var(--azul)}
.cartao{background:#fff;border:1px solid var(--borda);border-radius:16px;padding:24px;box-shadow:0 6px 18px rgba(8,28,105,.06);margin-bottom:22px}
/* ---- formulários ---- */
label{display:block;font-weight:600;font-size:13.5px;margin:14px 0 6px;color:var(--azul)}
input[type=text],input[type=url],input[type=email],input[type=password],input[type=number],textarea,select{
  width:100%;padding:11px 13px;border:1px solid var(--borda);border-radius:10px;font-size:14.5px;font-family:inherit;background:#fbfcff}
textarea{min-height:110px;resize:vertical}
input:focus,textarea:focus,select:focus{outline:2px solid var(--teal);border-color:var(--teal)}
.linha-campos{display:grid;grid-template-columns:1fr 1fr;gap:0 18px}
.ajuda{font-size:12.5px;color:var(--cinza);margin-top:4px}
/* ---- botões ---- */
.botao{display:inline-block;border:0;cursor:pointer;text-decoration:none;font-weight:700;font-size:14.5px;padding:11px 22px;border-radius:999px;transition:transform .12s, box-shadow .12s}
.botao:hover{transform:translateY(-1px)}
.botao-primario{background:linear-gradient(135deg,var(--azul),var(--azul2));color:#fff}
.botao-verde{background:var(--verde);color:var(--azul)}
.botao-claro{background:#eef2fb;color:var(--azul)}
.botao-perigo{background:#fde8e8;color:#b91c1c}
.botao-mini{padding:7px 14px;font-size:13px}
/* ---- tabelas ---- */
table{width:100%;border-collapse:collapse;font-size:14px}
th{background:#f6f8fd;color:var(--azul);text-align:left;padding:11px 12px;border-bottom:2px solid var(--borda);font-size:12.5px;text-transform:uppercase;letter-spacing:.4px}
td{padding:11px 12px;border-bottom:1px solid var(--borda);vertical-align:middle}
tr:hover td{background:#fafbff}
.miniatura{width:70px;height:44px;object-fit:cover;border-radius:8px;border:1px solid var(--borda)}
/* ---- selos ---- */
.selo{display:inline-block;padding:3px 11px;border-radius:999px;font-size:12px;font-weight:700}
.selo-ativo{background:#e7ffcc;color:#3f6212}
.selo-inativo{background:#f1f2f6;color:#6b7280}
.selo-acao-inserir{background:#dcfce7;color:#166534}
.selo-acao-atualizar{background:#dbeafe;color:#1e40af}
.selo-acao-excluir{background:#fee2e2;color:#991b1b}
.selo-acao-login{background:#e7ffcc;color:#3f6212}
.selo-acao-login_falha{background:#fef3c7;color:#92400e}
.selo-acao-logout{background:#f1f2f6;color:#374151}
/* ---- avisos ---- */
.aviso{padding:14px 18px;border-radius:12px;margin-bottom:18px;font-size:14.5px}
.aviso-ok{background:#e7ffcc;color:#3f6212;border:1px solid #c7f59b}
.aviso-erro{background:#fde8e8;color:#b91c1c;border:1px solid #f5c2c2}
.paginacao{display:flex;gap:6px;margin-top:16px;flex-wrap:wrap}
.paginacao a,.paginacao span{padding:7px 13px;border-radius:9px;text-decoration:none;font-size:13.5px;border:1px solid var(--borda);background:#fff;color:var(--azul)}
.paginacao .atual{background:var(--azul);color:#fff;font-weight:700}
details.diff summary{cursor:pointer;color:var(--teal);font-size:13px;font-weight:600}
details.diff pre{background:#0f172a;color:#d7e3ff;padding:12px;border-radius:10px;font-size:12px;overflow-x:auto;margin-top:8px;max-width:520px;white-space:pre-wrap;word-break:break-word}
@media(max-width:860px){
  body{flex-direction:column}
  .lateral{width:100%;height:auto;position:static;flex-direction:row;flex-wrap:wrap;align-items:center}
  .lateral .marca{margin:0 14px 0 0}
  .lateral .rodape-lateral{margin:0 0 0 auto;border:0;padding:0}
  .conteudo{padding:20px}
  .linha-campos{grid-template-columns:1fr}
}
</style>
</head>
<body>
<aside class="lateral">
  <div class="marca">AutoriaSCS<small>Painel da Vitrine · CECAPE</small></div>
  <a class="item <?= $menuAtivo === 'inicio' ? 'ativo' : '' ?>" href="index.php">📋 Configurações</a>
  <a class="item <?= $menuAtivo === 'parceiros' ? 'ativo' : '' ?>" href="parceiros.php">🤝 Parceiros</a>
  <a class="item <?= $menuAtivo === 'cursos' ? 'ativo' : '' ?>" href="cursos.php">🎓 Cursos</a>
  <a class="item <?= $menuAtivo === 'logs' ? 'ativo' : '' ?>" href="logs.php">🕒 Logs de atividade</a>
  <a class="item <?= $menuAtivo === 'perfil' ? 'ativo' : '' ?>" href="perfil.php">👤 Meu perfil</a>
  <a class="item" href="../home.php" target="_blank" rel="noopener">👁️ Ver vitrine</a>
  <div class="rodape-lateral">
    Olá, <strong><?= e($usuario['nome'] ?? '') ?></strong><br>
    <a href="logout.php">Sair com segurança</a>
  </div>
</aside>
<main class="conteudo">
<?php
}

function admin_rodape(): void
{
    ?>
</main>
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
