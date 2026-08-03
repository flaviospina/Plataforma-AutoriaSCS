<?php
/**
 * Diagnóstico da instalação da vitrine.
 * Envie este arquivo para a pasta vitrine/ do servidor e abra no navegador:
 *   https://SEU-DOMINIO/.../vitrine/diagnostico.php
 *
 * IMPORTANTE: apague este arquivo do servidor depois de resolver o problema.
 * (Escrito em PHP compatível com versões antigas de propósito.)
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');
header('Content-Type: text/html; charset=utf-8');

function linha($rotulo, $ok, $detalhe)
{
    $cor = $ok ? '#166534' : '#b91c1c';
    $icone = $ok ? 'OK' : 'PROBLEMA';
    echo '<tr><td style="padding:8px 12px;border-bottom:1px solid #eee">' . $rotulo . '</td>';
    echo '<td style="padding:8px 12px;border-bottom:1px solid #eee;color:' . $cor . ';font-weight:bold">' . $icone . '</td>';
    echo '<td style="padding:8px 12px;border-bottom:1px solid #eee">' . $detalhe . '</td></tr>';
}

echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><title>Diagnóstico da Vitrine</title></head>';
echo '<body style="font-family:Arial,sans-serif;max-width:900px;margin:30px auto;padding:0 16px;color:#1f2937">';
echo '<h1 style="color:#071f8f">Diagnóstico da Vitrine AutoriaSCS</h1>';
echo '<table style="width:100%;border-collapse:collapse;background:#fff;border:1px solid #eee">';
echo '<tr style="background:#f6f8fd"><th style="padding:8px 12px;text-align:left">Verificação</th><th style="padding:8px 12px;text-align:left">Resultado</th><th style="padding:8px 12px;text-align:left">Detalhes</th></tr>';

/* 1. Versão do PHP */
$versao = phpversion();
$phpOk = version_compare($versao, '7.4.0', '>=');
linha(
    'Versão do PHP',
    $phpOk,
    'Instalada: <strong>' . $versao . '</strong>' .
    ($phpOk ? '' : ' — o sistema precisa de PHP 7.4 ou superior. Altere a versão do PHP no painel da hospedagem (cPanel &gt; "Select PHP Version" ou similar).')
);

/* 2. Arquivos da pasta */
$arquivos = array('embed.js', 'api.php', 'home.php', 'config.php', 'config.example.php');
foreach ($arquivos as $nome) {
    $caminho = dirname(__FILE__) . '/' . $nome;
    if (!file_exists($caminho)) {
        $obrigatorio = ($nome !== 'config.example.php');
        linha('Arquivo ' . $nome, !$obrigatorio, $nome === 'config.php'
            ? 'NÃO EXISTE — copie config.example.php para config.php e preencha os dados do banco.'
            : 'Não encontrado nesta pasta.');
        continue;
    }
    $tamanho = filesize($caminho);
    linha(
        'Arquivo ' . $nome,
        $tamanho > 100,
        'Tamanho: ' . $tamanho . ' bytes' .
        ($tamanho <= 100 ? ' — <strong>arquivo vazio ou corrompido!</strong> Envie novamente por FTP/Gerenciador de arquivos.' : '')
    );
}

/* 3. Pasta admin */
$temAdmin = is_dir(dirname(__FILE__) . '/admin');
linha('Pasta admin/', $temAdmin, $temAdmin ? 'Encontrada.' : 'Não encontrada — envie a pasta admin completa.');

/* 4. Configuração + banco */
$caminhoConfig = dirname(__FILE__) . '/config.php';
if (file_exists($caminhoConfig) && filesize($caminhoConfig) > 100) {
    $CONFIG = include $caminhoConfig;
    if (!is_array($CONFIG) || !isset($CONFIG['db'])) {
        linha('Conteúdo do config.php', false, 'O arquivo não retornou a configuração esperada. Confira se ele começa com &lt;?php e termina com return [...];');
    } else {
        if (!extension_loaded('pdo_mysql')) {
            linha('Extensão PDO MySQL', false, 'A extensão pdo_mysql não está habilitada. Ative-a no painel da hospedagem (seção de extensões do PHP).');
        } else {
            $c = $CONFIG['db'];
            try {
                $pdo = new PDO(
                    'mysql:host=' . $c['host'] . ';dbname=' . $c['nome'] . ';charset=' . $c['charset'],
                    $c['usuario'],
                    $c['senha']
                );
                linha('Conexão com o banco', true, 'Conectado a "' . $c['nome'] . '" em "' . $c['host'] . '".');

                $tabelas = array('admin_usuarios', 'parceiros', 'cursos', 'configuracoes', 'logs_atividade', 'tentativas_login');
                foreach ($tabelas as $t) {
                    try {
                        $qtd = $pdo->query('SELECT COUNT(*) FROM ' . $t)->fetchColumn();
                        linha('Tabela ' . $t, true, $qtd . ' registro(s).');
                    } catch (Exception $ex) {
                        linha('Tabela ' . $t, false, 'Não existe — execute o script sql/schema.sql no phpMyAdmin.');
                    }
                }
            } catch (Exception $ex) {
                linha('Conexão com o banco', false, 'Falhou: ' . htmlspecialchars($ex->getMessage()) . ' — confira host, nome do banco, usuário e senha no config.php.');
            }
        }
    }
} else {
    linha('Configuração', false, 'Sem config.php válido — os testes de banco foram pulados.');
}

echo '</table>';
echo '<p style="margin-top:20px;background:#fef3c7;padding:14px;border-radius:8px"><strong>⚠️ Apague este arquivo (diagnostico.php) do servidor após o uso.</strong></p>';
echo '</body></html>';
