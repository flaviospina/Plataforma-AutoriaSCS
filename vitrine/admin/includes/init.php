<?php
/**
 * Inicialização do painel administrativo:
 * carrega configuração, abre conexão PDO, inicia sessão segura
 * e define funções utilitárias (autenticação, CSRF, logs, upload).
 */

declare(strict_types=1);

$configPath = dirname(__DIR__, 2) . '/config.php';
if (!is_file($configPath)) {
    http_response_code(500);
    exit('Arquivo config.php não encontrado. Copie config.example.php para config.php e configure o banco de dados.');
}
$CONFIG = require $configPath;

date_default_timezone_set('America/Sao_Paulo');

require __DIR__ . '/email.php';

/* ---------- Conexão PDO ---------- */
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        global $CONFIG;
        $c = $CONFIG['db'];
        $dsn = "mysql:host={$c['host']};dbname={$c['nome']};charset={$c['charset']}";
        $pdo = new PDO($dsn, $c['usuario'], $c['senha'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}

/* ---------- Sessão segura ---------- */
session_name($CONFIG['sessao']['nome']);
session_set_cookie_params([
    'lifetime' => $CONFIG['sessao']['tempo_vida'],
    'path'     => '/',
    'secure'   => (bool) $CONFIG['sessao']['apenas_https'],
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

// Expiração por inatividade
if (isset($_SESSION['ultimo_acesso']) && (time() - $_SESSION['ultimo_acesso']) > $CONFIG['sessao']['tempo_vida']) {
    session_unset();
    session_destroy();
    session_start();
}
$_SESSION['ultimo_acesso'] = time();

/* ---------- Helpers ---------- */
function e(?string $texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

function ip_cliente(): string
{
    return substr($_SERVER['REMOTE_ADDR'] ?? 'desconhecido', 0, 45);
}

function usuario_logado(): ?array
{
    return $_SESSION['admin'] ?? null;
}

function exigir_login(bool $permitirSenhaProvisoria = false): void
{
    if (!usuario_logado()) {
        header('Location: login.php');
        exit;
    }
    // Senha provisória: bloqueia todo o painel até o usuário definir a própria senha
    if (!$permitirSenhaProvisoria && !empty($_SESSION['admin']['senha_provisoria'])) {
        header('Location: trocar_senha.php');
        exit;
    }
}

/* ---------- Política de senha ---------- */
function politica_senha_regras(): array
{
    return [
        'Mínimo de 10 caracteres',
        'Pelo menos 1 letra MAIÚSCULA',
        'Pelo menos 1 letra minúscula',
        'Pelo menos 1 número',
        'Pelo menos 1 caractere especial (ex.: @ # $ % ! ? * -)',
        'Não pode conter seu nome ou seu e-mail',
    ];
}

/**
 * Valida a senha contra a política de segurança.
 * Devolve a mensagem do primeiro problema encontrado, ou null se estiver ok.
 */
function validar_politica_senha(string $senha, string $nome = '', string $email = ''): ?string
{
    if (mb_strlen($senha) < 10) {
        return 'A senha deve ter pelo menos 10 caracteres.';
    }
    if (!preg_match('/[A-Z]/', $senha)) {
        return 'A senha deve conter pelo menos uma letra maiúscula.';
    }
    if (!preg_match('/[a-z]/', $senha)) {
        return 'A senha deve conter pelo menos uma letra minúscula.';
    }
    if (!preg_match('/\d/', $senha)) {
        return 'A senha deve conter pelo menos um número.';
    }
    if (!preg_match('/[^A-Za-z0-9]/', $senha)) {
        return 'A senha deve conter pelo menos um caractere especial (ex.: @ # $ % ! ? * -).';
    }

    $senhaMinuscula = mb_strtolower($senha);
    $trechos = [];
    foreach (preg_split('/\s+/', mb_strtolower(trim($nome))) ?: [] as $palavra) {
        if (mb_strlen($palavra) >= 4) {
            $trechos[] = $palavra;
        }
    }
    $parteEmail = mb_strtolower(explode('@', $email)[0] ?? '');
    if (mb_strlen($parteEmail) >= 4) {
        $trechos[] = $parteEmail;
    }
    foreach ($trechos as $trecho) {
        if (mb_strpos($senhaMinuscula, $trecho) !== false) {
            return 'A senha não pode conter o seu nome ou o seu e-mail.';
        }
    }
    return null;
}

/* ---------- CSRF ---------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_campo(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_validar(): void
{
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(403);
        exit('Falha na validação de segurança (CSRF). Volte e tente novamente.');
    }
}

/* ---------- Logs de atividade ---------- */
function registrar_log(
    string $acao,
    string $entidade,
    ?int $entidadeId,
    string $descricao,
    ?array $dadosAnteriores = null,
    ?array $dadosNovos = null
): void {
    $usuario = usuario_logado();
    $st = db()->prepare(
        'INSERT INTO logs_atividade
            (usuario_id, usuario_nome, acao, entidade, entidade_id, descricao, dados_anteriores, dados_novos, ip)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $st->execute([
        $usuario['id'] ?? null,
        $usuario['nome'] ?? null,
        $acao,
        $entidade,
        $entidadeId,
        mb_substr($descricao, 0, 500),
        $dadosAnteriores !== null ? json_encode($dadosAnteriores, JSON_UNESCAPED_UNICODE) : null,
        $dadosNovos !== null ? json_encode($dadosNovos, JSON_UNESCAPED_UNICODE) : null,
        ip_cliente(),
    ]);
}

/* ---------- Proteção contra força bruta ---------- */
function login_bloqueado(string $email): bool
{
    global $CONFIG;
    $st = db()->prepare(
        'SELECT COUNT(*) AS total FROM tentativas_login
          WHERE ip = ? AND email = ? AND sucesso = 0
            AND criado_em > (NOW() - INTERVAL ? MINUTE)'
    );
    $st->execute([ip_cliente(), $email, $CONFIG['login']['janela_minutos']]);
    return (int) $st->fetchColumn() >= $CONFIG['login']['max_tentativas'];
}

function registrar_tentativa(string $email, bool $sucesso): void
{
    $st = db()->prepare('INSERT INTO tentativas_login (ip, email, sucesso) VALUES (?, ?, ?)');
    $st->execute([ip_cliente(), $email, $sucesso ? 1 : 0]);
}

/* ---------- Upload de imagens ---------- */
function salvar_imagem(array $arquivo): ?string
{
    global $CONFIG;
    if (($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($arquivo['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Falha no envio da imagem (código ' . $arquivo['error'] . ').');
    }
    if ($arquivo['size'] > $CONFIG['upload']['tamanho_max']) {
        throw new RuntimeException('Imagem maior que o limite permitido (4 MB).');
    }

    $info = @getimagesize($arquivo['tmp_name']);
    $permitidos = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG  => 'png',
        IMAGETYPE_GIF  => 'gif',
        IMAGETYPE_WEBP => 'webp',
    ];
    if ($info === false || !isset($permitidos[$info[2]])) {
        throw new RuntimeException('Arquivo inválido. Envie uma imagem JPG, PNG, GIF ou WEBP.');
    }

    $pasta = $CONFIG['upload']['pasta'];
    if (!is_dir($pasta) && !mkdir($pasta, 0755, true)) {
        throw new RuntimeException('Não foi possível criar a pasta de uploads.');
    }

    $nome = date('Ymd-His') . '-' . bin2hex(random_bytes(6)) . '.' . $permitidos[$info[2]];
    if (!move_uploaded_file($arquivo['tmp_name'], $pasta . '/' . $nome)) {
        throw new RuntimeException('Não foi possível salvar a imagem no servidor.');
    }

    $urlBase = $CONFIG['upload']['url'] ?? ($CONFIG['base_url'] . '/uploads');
    return rtrim($urlBase, '/') . '/' . $nome;
}

/* ---------- Configurações da vitrine ---------- */
function obter_configuracoes(): array
{
    $itens = [];
    foreach (db()->query('SELECT chave, valor FROM configuracoes') as $linha) {
        $itens[$linha['chave']] = $linha['valor'];
    }
    return $itens;
}
