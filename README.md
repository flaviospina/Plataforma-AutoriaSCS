# Plataforma AutoriaSCS — Vitrine da Página Inicial

Sistema de gerenciamento do conteúdo exibido na página inicial da plataforma
AutoriaSCS (Moodle + tema Almond), composto por:

| Módulo | Caminho | Descrição |
|---|---|---|
| Banco de dados | `sql/schema.sql` | Criação das tabelas + dados iniciais |
| Painel admin | `vitrine/admin/` | Acesso restrito com login e senha |
| API pública | `vitrine/api.php` | Entrega o conteúdo em JSON (com CORS) |
| Widget de incorporação | `vitrine/embed.js` | Monta a vitrine dentro do Moodle **sem iframe** |
| Pré-visualização | `vitrine/home.php` | Mostra a vitrine exatamente como aparecerá |
| Logs | `vitrine/admin/logs.php` | Auditoria: quem alterou, o quê, quando e de qual IP |

A vitrine tem **duas seções**, na ordem solicitada:

1. **Parceiros** — cards de destaque com banner, descrição, itens de destaque
   (ex.: "Acesso gratuito", "Certificação pela UFU") e botão de chamada para ação;
2. **Novos cursos** — grade responsiva de cards modernos com capa, selo "NOVO",
   selo de carga horária, descrição e botão "Inscreva-se agora!", com animação
   de entrada e efeito de elevação ao passar o mouse.

---

## 1. Instalação

### 1.1 Banco de dados

Execute o script `sql/schema.sql` no phpMyAdmin (ou via linha de comando):

```bash
mysql -u SEU_USUARIO -p < sql/schema.sql
```

O script cria o banco `autoriascs_vitrine` com as tabelas:

- `admin_usuarios` — usuários do painel (senha com hash bcrypt);
- `parceiros` — seção 1 da vitrine;
- `cursos` — seção 2 da vitrine;
- `configuracoes` — títulos das seções e chaves de exibição;
- `logs_atividade` — auditoria completa (antes/depois em JSON);
- `tentativas_login` — proteção contra força bruta.

Ele também insere os **dados atuais da página** (Educação Paralímpica + 5 cursos),
prontos para serem editados pelo painel.

> Se o seu servidor não permitir `CREATE DATABASE`, crie o banco pelo painel da
> hospedagem, remova as 3 primeiras linhas do script e execute o restante.

### 1.2 Arquivos

1. Envie a pasta `vitrine/` para o seu servidor web (ex.: `https://cecapescs.com.br/vitrine/`).
   Pode ser o mesmo servidor do Moodle ou outro — o widget funciona nos dois casos.
2. Copie `vitrine/config.example.php` para `vitrine/config.php` e preencha:
   - dados de conexão do MySQL;
   - `base_url` com a URL pública da pasta (ex.: `https://cecapescs.com.br/vitrine`);
   - em ambiente local sem HTTPS, mude `'apenas_https' => false`.
3. Garanta permissão de escrita na pasta `vitrine/uploads/` (para envio de imagens).

### 1.3 Primeiro acesso ao painel

- URL: `https://SEU-DOMINIO/vitrine/admin/login.php`
- E-mail: `prof.flavio.spina@gmail.com`
- Senha inicial: `Cecape@2026`

⚠️ **Troque a senha no primeiro acesso** (menu "Meu perfil").

---

## 2. Incorporação no Moodle — solução sem iframe

O iframe falha porque depende de altura fixa, é bloqueado por cabeçalhos
`X-Frame-Options`/CSP e não se adapta ao layout responsivo do tema. A solução
adotada é um **widget JavaScript**: o `embed.js` busca o conteúdo na API e
constrói o HTML **dentro da própria página** do Moodle, herdando toda a
responsividade — sem nenhuma das limitações do iframe.

### Passo 1 — o contêiner

Edite o conteúdo da página inicial (Resumo da página inicial ou um bloco HTML)
e, no modo de edição de código-fonte (`<>`), insira apenas:

```html
<div id="autoriascs-vitrine"></div>
```

Uma `div` vazia nunca é removida pelos filtros do editor do Moodle.

### Passo 2 — o script

Vá em **Administração do site → Aparência → HTML adicional →
"Antes do fechamento da tag BODY"** e insira:

```html
<script src="https://SEU-DOMINIO/vitrine/embed.js?v=1" defer></script>
```

Salve e visite a página inicial. Pronto: a vitrine aparece no lugar da `div`.

> **Importante — cache do navegador:** sempre que substituir o `embed.js` no
> servidor, incremente o número da versão na URL (`?v=2`, `?v=3`...). Sem
> isso, navegadores e CDNs continuam servindo a cópia antiga do arquivo,
> mesmo com o novo já no servidor.

> Por que "HTML adicional"? Porque o editor de texto do Moodle **remove tags
> `<script>`** do conteúdo (era isso que também atrapalhava o iframe em alguns
> temas). O campo "HTML adicional" é inserido diretamente no template da página,
> sem passar por filtro nenhum — funciona em 100% dos casos, inclusive com o
> tema Almond. O script só age quando encontra a `div`, então não interfere nas
> demais páginas da plataforma.

Qualquer alteração feita no painel admin aparece na página inicial em até
2 minutos (cache leve da API) — sem tocar no Moodle novamente.

---

## 2.1 Avisos por e-mail e primeiro acesso

- **Toda ação em uma conta de usuário do painel** (criação, alteração de dados,
  redefinição de senha, desativação/reativação e exclusão) dispara um **aviso
  por e-mail** ao usuário, no modelo institucional do CECAPE, contendo apenas:
  ação, dados alterados, quem executou, data/horário e IP. Configure remetente
  e logos na seção `email` do `config.php`.
- **Primeiro acesso**: contas criadas pelo administrador nascem com **senha
  provisória** — no primeiro login o usuário é obrigado a definir a própria
  senha antes de acessar qualquer página do painel. O mesmo vale quando um
  administrador redefine a senha de outro usuário.
- **Política de senha**: mínimo de 10 caracteres, com letra maiúscula,
  minúscula, número e caractere especial; não pode conter o nome nem o e-mail
  do usuário; deve ser diferente da senha anterior.
- Instalações antigas: execute uma vez o script
  `sql/atualizacao-senha-provisoria.sql` para criar a coluna nova.

## 3. Segurança implementada

- Senhas com `password_hash()` (bcrypt) e rehash automático;
- Bloqueio de força bruta: 5 tentativas falhas por IP + e-mail a cada 15 min;
- Sessão com cookie `HttpOnly`, `SameSite=Lax`, `Secure` e expiração por inatividade;
- `session_regenerate_id()` no login (contra fixação de sessão);
- Token CSRF em todos os formulários e nas exclusões;
- Consultas 100% com *prepared statements* (contra SQL Injection);
- Saída sempre escapada com `htmlspecialchars` (contra XSS); o widget monta o
  DOM com `textContent`, nunca com HTML bruto;
- Upload validado por `getimagesize` + extensões permitidas, nome aleatório e
  `.htaccess` que impede execução de scripts na pasta `uploads/`;
- `config.php` e `admin/includes/` bloqueados por `.htaccess`;
- Todas as ações registradas em log com IP, data/hora e dados antes/depois.

## 4. Logs de atividade

Acesse **Painel → Logs de atividade** para ver, com filtros por ação, item e
período:

- data e hora de cada operação;
- usuário responsável e IP;
- o que foi inserido, atualizado ou excluído;
- detalhes "Antes / Depois" de cada alteração (expansíveis);
- logins realizados e tentativas de login que falharam.
