/**
 * Vitrine AutoriaSCS - widget de incorporação (solução sem iframe).
 *
 * Como usar no Moodle:
 *   1) No conteúdo da página inicial (ou no bloco do tema), digite apenas o
 *      texto:  Carregando novidades...
 *      (Opcionalmente dentro de <div id="autoriascs-vitrine"> — mas não é
 *      necessário: alguns editores do Moodle removem id/class das divs, então
 *      o widget também localiza o contêiner pelo próprio texto, que sobrevive
 *      a qualquer editor.)
 *
 *   2) Em "Administração do site > Aparência > HTML adicional > Antes do fechamento do BODY", insira:
 *      <script src="https://SEU-DOMINIO/vitrine/embed.js" defer></script>
 *
 * Ordem de busca do contêiner: id "autoriascs-vitrine" > class
 * "autoriascs-vitrine" > elemento cujo texto seja "Carregando novidades..."
 * ou "[vitrine-autoriascs]".
 *
 * O script busca o conteúdo em api.php (mesma pasta deste arquivo) e monta
 * o HTML diretamente na página — sem iframe, sem problema de altura,
 * totalmente responsivo e imune a bloqueios de conteúdo incorporado.
 */
(function () {
  'use strict';

  var ID_ALVO = 'autoriascs-vitrine';

  // Descobre a URL base a partir do endereço deste próprio script
  function baseUrl() {
    var script = document.currentScript;
    if (!script) {
      var lista = document.querySelectorAll('script[src*="embed.js"]');
      script = lista[lista.length - 1];
    }
    if (!script || !script.src) return '';
    return script.src.replace(/\/embed\.js.*$/, '');
  }

  var BASE = baseUrl();

  var CSS = "\n" +
    ".avx{font-family:'Segoe UI',system-ui,-apple-system,Arial,sans-serif;color:#1f2937;max-width:1160px;margin:0 auto;padding:8px 14px 40px;box-sizing:border-box}\n" +
    ".avx *,.avx *:before,.avx *:after{box-sizing:border-box}\n" +
    ".avx img{max-width:100%;display:block}\n" +
    ".avx a{text-decoration:none}\n" +
    "@keyframes avxSurgir{from{opacity:0;transform:translateY(18px)}to{opacity:1;transform:none}}\n" +
    ".avx .avx-anim{animation:avxSurgir .55s ease both}\n" +
    /* títulos de seção */
    ".avx .avx-cabecalho{text-align:center;margin:34px 0 26px}\n" +
    ".avx .avx-cabecalho h2{font-size:clamp(24px,3.4vw,34px);font-weight:800;color:#071f8f;margin:0 0 8px}\n" +
    ".avx .avx-cabecalho p{color:#5b6475;font-size:16px;margin:0 auto;max-width:640px;line-height:1.6}\n" +
    ".avx .avx-traco{width:74px;height:5px;border-radius:99px;margin:14px auto 0;background:linear-gradient(90deg,#2898a4,#9dff00)}\n" +
    /* ---- parceiros ---- */
    ".avx .avx-parceiro{background:#fff;border:1px solid rgba(7,31,143,.08);border-radius:24px;overflow:hidden;box-shadow:0 18px 45px rgba(8,28,105,.14);margin-bottom:30px}\n" +
    ".avx .avx-parceiro-capa{width:100%;max-height:360px;object-fit:cover}\n" +
    ".avx .avx-parceiro-corpo{padding:30px}\n" +
    ".avx .avx-parceiro-corpo h3{color:#071f8f;font-size:24px;margin:0 0 12px}\n" +
    ".avx .avx-parceiro-corpo p.avx-desc{line-height:1.8;font-size:16.5px;margin:0 0 22px}\n" +
    ".avx .avx-grade-parceiro{display:grid;grid-template-columns:1.15fr .85fr;gap:24px;align-items:start}\n" +
    ".avx .avx-destaques{display:grid;gap:12px}\n" +
    ".avx .avx-destaque{display:flex;gap:12px;align-items:flex-start;padding:14px 16px;border-radius:16px;background:#fff;border:1px solid rgba(7,31,143,.08)}\n" +
    ".avx .avx-ponto{width:13px;height:13px;border-radius:50%;background:#9dff00;margin-top:5px;box-shadow:0 0 0 6px rgba(157,255,0,.16);flex:0 0 auto}\n" +
    ".avx .avx-destaque strong{display:block;color:#04145d;margin-bottom:3px;font-size:15px}\n" +
    ".avx .avx-destaque span{color:#5b6475;line-height:1.55;font-size:14.5px}\n" +
    ".avx .avx-cta{margin-top:26px;padding:24px 28px;border-radius:20px;background:linear-gradient(135deg,#071f8f 0%,#0d37d0 100%);color:#fff;display:flex;align-items:center;justify-content:space-between;gap:20px;flex-wrap:wrap}\n" +
    ".avx .avx-cta h4{margin:0 0 5px;font-size:22px}\n" +
    ".avx .avx-cta p{margin:0;color:rgba(255,255,255,.9);line-height:1.6}\n" +
    ".avx .avx-botao-verde{display:inline-block;background:#9dff00;color:#08215d;font-weight:800;padding:14px 24px;border-radius:999px;box-shadow:0 12px 28px rgba(157,255,0,.28);white-space:nowrap;transition:transform .18s ease}\n" +
    ".avx .avx-botao-verde:hover{transform:translateY(-2px)}\n" +
    ".avx .avx-obs{margin:16px 0 0;color:#5b6475;font-size:13.5px;line-height:1.6}\n" +
    /* ---- cursos ---- */
    ".avx .avx-grade-cursos{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:26px}\n" +
    ".avx .avx-curso{position:relative;background:#fff;border:1px solid rgba(7,31,143,.08);border-radius:20px;overflow:hidden;box-shadow:0 10px 30px rgba(8,28,105,.10);display:flex;flex-direction:column;transition:transform .22s ease,box-shadow .22s ease}\n" +
    ".avx .avx-curso:hover{transform:translateY(-6px);box-shadow:0 22px 48px rgba(8,28,105,.18)}\n" +
    ".avx .avx-curso-media{position:relative;height:190px;background:linear-gradient(135deg,#071f8f 0%,#2898a4 100%);overflow:hidden}\n" +
    ".avx .avx-curso-capa{width:100%;height:100%;object-fit:cover}\n" +
    ".avx .avx-selo-novo{position:absolute;top:14px;left:14px;background:#9dff00;color:#08215d;font-size:12px;font-weight:800;padding:5px 13px;border-radius:999px;letter-spacing:.6px;box-shadow:0 6px 16px rgba(0,0,0,.18)}\n" +
    ".avx .avx-selo-carga{position:absolute;top:14px;right:14px;background:rgba(7,31,143,.88);color:#fff;font-size:12px;font-weight:700;padding:5px 13px;border-radius:999px}\n" +
    ".avx .avx-curso-corpo{padding:22px 22px 26px;display:flex;flex-direction:column;flex:1}\n" +
    ".avx .avx-curso-corpo h3{color:#2898a4;font-size:19px;line-height:1.35;margin:0 0 10px}\n" +
    ".avx .avx-curso-corpo p{color:#555;font-size:14.5px;line-height:1.65;margin:0 0 20px;flex:1}\n" +
    ".avx .avx-botao-curso{align-self:center;background:linear-gradient(135deg,#2898a4,#1f7e88);color:#fff;font-weight:700;font-size:15px;padding:12px 26px;border-radius:999px;transition:transform .18s ease,box-shadow .18s ease;box-shadow:0 8px 20px rgba(40,152,164,.35)}\n" +
    ".avx .avx-botao-curso:hover{transform:translateY(-2px);box-shadow:0 14px 28px rgba(40,152,164,.45)}\n" +
    "@media(max-width:820px){.avx .avx-grade-parceiro{grid-template-columns:1fr}.avx .avx-parceiro-corpo{padding:22px}}\n";

  function el(tag, classe, texto) {
    var node = document.createElement(tag);
    if (classe) node.className = classe;
    if (texto !== undefined && texto !== null && texto !== '') node.textContent = texto;
    return node;
  }

  function montarParceiro(p, indice) {
    var card = el('article', 'avx-parceiro avx-anim');
    card.style.animationDelay = (indice * 0.12) + 's';

    if (p.imagem_url) {
      var capa = el('img', 'avx-parceiro-capa');
      capa.src = p.imagem_url;
      capa.alt = p.nome || '';
      capa.loading = 'lazy';
      capa.onerror = function () { this.style.display = 'none'; };
      card.appendChild(capa);
    }

    var corpo = el('div', 'avx-parceiro-corpo');
    var grade = el('div', 'avx-grade-parceiro');

    var colTexto = el('div');
    if (p.titulo) colTexto.appendChild(el('h3', null, p.titulo));
    if (p.descricao) colTexto.appendChild(el('p', 'avx-desc', p.descricao));
    grade.appendChild(colTexto);

    if (p.destaques && p.destaques.length) {
      var lista = el('div', 'avx-destaques');
      p.destaques.forEach(function (item) {
        var destaque = el('div', 'avx-destaque');
        destaque.appendChild(el('div', 'avx-ponto'));
        var textoDiv = el('div');
        var separador = item.indexOf(' - ');
        if (separador > 0) {
          textoDiv.appendChild(el('strong', null, item.slice(0, separador)));
          textoDiv.appendChild(el('span', null, item.slice(separador + 3)));
        } else {
          textoDiv.appendChild(el('span', null, item));
        }
        destaque.appendChild(textoDiv);
        lista.appendChild(destaque);
      });
      grade.appendChild(lista);
    }
    corpo.appendChild(grade);

    if (p.link_url) {
      var cta = el('div', 'avx-cta');
      var ctaTexto = el('div');
      ctaTexto.appendChild(el('h4', null, 'Inscrições abertas'));
      ctaTexto.appendChild(el('p', null, 'Participe desta formação e contribua para uma educação mais inclusiva, acessível e transformadora.'));
      cta.appendChild(ctaTexto);
      var botao = el('a', 'avx-botao-verde', p.texto_botao || 'Acessar plataforma');
      botao.href = p.link_url;
      botao.target = '_blank';
      botao.rel = 'noopener noreferrer';
      cta.appendChild(botao);
      corpo.appendChild(cta);
    }

    if (p.observacao) corpo.appendChild(el('p', 'avx-obs', p.observacao));
    card.appendChild(corpo);
    return card;
  }

  function montarCurso(c, indice) {
    var card = el('article', 'avx-curso avx-anim');
    card.style.animationDelay = (indice * 0.09) + 's';

    var media = el('div', 'avx-curso-media');
    if (c.imagem_url) {
      var capa = el('img', 'avx-curso-capa');
      capa.src = c.imagem_url;
      capa.alt = c.titulo || '';
      capa.loading = 'lazy';
      capa.onerror = function () { this.style.display = 'none'; }; // mantém o fundo gradiente
      media.appendChild(capa);
    }
    if (c.novo && Number(c.novo) === 1) media.appendChild(el('span', 'avx-selo-novo', 'NOVO'));
    if (c.carga_horaria) media.appendChild(el('span', 'avx-selo-carga', c.carga_horaria));
    card.appendChild(media);

    var corpo = el('div', 'avx-curso-corpo');
    corpo.appendChild(el('h3', null, c.titulo));
    if (c.descricao) corpo.appendChild(el('p', null, c.descricao));

    var botao = el('a', 'avx-botao-curso', c.texto_botao || '👉 Inscreva-se agora!');
    botao.href = c.link_url;
    corpo.appendChild(botao);

    card.appendChild(corpo);
    return card;
  }

  function cabecalhoSecao(titulo, subtitulo) {
    var wrap = el('header', 'avx-cabecalho avx-anim');
    wrap.appendChild(el('h2', null, titulo));
    if (subtitulo) wrap.appendChild(el('p', null, subtitulo));
    wrap.appendChild(el('div', 'avx-traco'));
    return wrap;
  }

  function renderizar(alvo, dados) {
    var raiz = el('div', 'avx');
    var cfg = dados.config || {};

    if (cfg.exibir_parceiros && dados.parceiros && dados.parceiros.length) {
      raiz.appendChild(cabecalhoSecao(cfg.titulo_parceiros || 'Nossos parceiros', cfg.subtitulo_parceiros));
      dados.parceiros.forEach(function (p, i) { raiz.appendChild(montarParceiro(p, i)); });
    }

    if (cfg.exibir_cursos && dados.cursos && dados.cursos.length) {
      raiz.appendChild(cabecalhoSecao(cfg.titulo_cursos || 'Novos cursos no ar!', cfg.subtitulo_cursos));
      var grade = el('div', 'avx-grade-cursos');
      dados.cursos.forEach(function (c, i) { grade.appendChild(montarCurso(c, i)); });
      raiz.appendChild(grade);
    }

    alvo.innerHTML = '';
    alvo.appendChild(raiz);
  }

  // Textos que marcam o local da vitrine quando o editor do Moodle
  // remove os atributos id/class da div (texto puro sempre sobrevive)
  var MARCADORES = ['carregando novidades...', 'carregando novidades…', '[vitrine-autoriascs]'];

  function encontrarAlvo() {
    var alvo = document.getElementById(ID_ALVO) || document.querySelector('.' + ID_ALVO);
    if (alvo) return alvo;

    // Procura o elemento mais interno cujo texto seja exatamente um dos marcadores
    var candidatos = document.querySelectorAll('p, div, span, h1, h2, h3, h4, h5, td');
    for (var i = 0; i < candidatos.length; i++) {
      var elem = candidatos[i];
      if (elem.childElementCount !== 0) continue;
      var texto = (elem.textContent || '').trim().toLowerCase();
      if (MARCADORES.indexOf(texto) === -1) continue;
      // Substitui o marcador por um contêiner novo e limpo
      var novo = document.createElement('div');
      novo.id = ID_ALVO;
      elem.parentNode.replaceChild(novo, elem);
      return novo;
    }
    return null;
  }

  function iniciar() {
    var alvo = encontrarAlvo();
    if (!alvo || !BASE) return;

    if (!document.getElementById('avx-estilos')) {
      var estilo = document.createElement('style');
      estilo.id = 'avx-estilos';
      estilo.textContent = CSS;
      document.head.appendChild(estilo);
    }

    fetch(BASE + '/api.php', { mode: 'cors' })
      .then(function (resposta) {
        if (!resposta.ok) throw new Error('HTTP ' + resposta.status);
        return resposta.json();
      })
      .then(function (dados) { renderizar(alvo, dados); })
      .catch(function () {
        // Falha silenciosa: a página inicial do Moodle continua funcionando normalmente
        alvo.innerHTML = '';
      });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', iniciar);
  } else {
    iniciar();
  }
})();
