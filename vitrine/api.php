<?php
/**
 * API pública da vitrine (somente leitura).
 * Retorna em JSON os parceiros, cursos e configurações ativos,
 * consumidos pelo embed.js dentro da página inicial do Moodle.
 */

declare(strict_types=1);

$configPath = __DIR__ . '/config.php';
if (!is_file($configPath)) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    exit(json_encode(['erro' => 'Configuração ausente no servidor.']));
}
$CONFIG = require $configPath;

/* ---- CORS: libera o acesso a partir do Moodle ---- */
$origem = $_SERVER['HTTP_ORIGIN'] ?? '';
$permitidas = $CONFIG['cors_origens'] ?? ['*'];
if (in_array('*', $permitidas, true)) {
    header('Access-Control-Allow-Origin: *');
} elseif ($origem !== '' && in_array($origem, $permitidas, true)) {
    header('Access-Control-Allow-Origin: ' . $origem);
    header('Vary: Origin');
}
header('Access-Control-Allow-Methods: GET, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=120'); // cache leve de 2 minutos

try {
    $c = $CONFIG['db'];
    $pdo = new PDO(
        "mysql:host={$c['host']};dbname={$c['nome']};charset={$c['charset']}",
        $c['usuario'],
        $c['senha'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );

    $config = [];
    foreach ($pdo->query('SELECT chave, valor FROM configuracoes') as $linha) {
        $config[$linha['chave']] = $linha['valor'];
    }

    // Na página inicial entram apenas parceiros dentro do prazo de exibição;
    // os demais continuam visíveis na página de parceiros (parceiros.php)
    $parceiros = $pdo->query(
        'SELECT nome, titulo, descricao, imagem_url, link_url, texto_botao, destaques, observacao
           FROM parceiros
          WHERE ativo = 1 AND (expira_em IS NULL OR expira_em > NOW())
          ORDER BY ordem, nome'
    )->fetchAll();

    foreach ($parceiros as &$p) {
        $itens = preg_split('/\r\n|\r|\n/', (string) $p['destaques'], -1, PREG_SPLIT_NO_EMPTY);
        $p['destaques'] = array_map('trim', $itens ?: []);
    }
    unset($p);

    $cursos = $pdo->query(
        'SELECT titulo, descricao, imagem_url, link_url, carga_horaria, texto_botao, novo
           FROM cursos WHERE ativo = 1 ORDER BY ordem, titulo'
    )->fetchAll();

    echo json_encode([
        'config'    => [
            'titulo_parceiros'    => $config['titulo_parceiros'] ?? 'Nossos parceiros',
            'subtitulo_parceiros' => $config['subtitulo_parceiros'] ?? '',
            'titulo_cursos'       => $config['titulo_cursos'] ?? 'Novos cursos no ar!',
            'subtitulo_cursos'    => $config['subtitulo_cursos'] ?? '',
            'exibir_parceiros'    => ($config['exibir_parceiros'] ?? '1') === '1',
            'exibir_cursos'       => ($config['exibir_cursos'] ?? '1') === '1',
        ],
        'parceiros' => $parceiros,
        'cursos'    => $cursos,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $ex) {
    http_response_code(500);
    echo json_encode(['erro' => 'Não foi possível carregar o conteúdo.']);
}
