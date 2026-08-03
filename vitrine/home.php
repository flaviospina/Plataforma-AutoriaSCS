<?php
/**
 * Página de visualização da vitrine (pré-visualização fora do Moodle).
 * Usa exatamente o mesmo widget (embed.js) incorporado na página inicial,
 * garantindo que o que você vê aqui é o que aparecerá no Moodle.
 */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Vitrine · Plataforma AutoriaSCS</title>
<style>
  body{margin:0;background:#f5f8fd;font-family:'Segoe UI',system-ui,Arial,sans-serif}
  .faixa-topo{background:linear-gradient(135deg,#071f8f,#0d37d0);color:#fff;text-align:center;padding:14px;font-size:14px}
  .faixa-topo strong{color:#9dff00}
</style>
</head>
<body>
<div class="faixa-topo">Pré-visualização da vitrine — este é <strong>exatamente</strong> o conteúdo exibido na página inicial da plataforma.</div>
<div id="autoriascs-vitrine"></div>
<script src="embed.js" defer></script>
</body>
</html>
