<?php
/**
 * Plataforma AutoriaSCS - Vitrine
 * Copie este arquivo para "config.php" e ajuste os dados do seu servidor.
 * NUNCA versione o config.php com senhas reais.
 */

return [
    // Conexão com o banco de dados MySQL
    'db' => [
        'host'    => 'localhost',
        'nome'    => 'autoriascs_vitrine',
        'usuario' => 'root',
        'senha'   => '',
        'charset' => 'utf8mb4',
    ],

    // URL pública da pasta "vitrine" (sem barra no final)
    // Ex.: https://cecapescs.com.br/vitrine
    'base_url' => 'https://cecapescs.com.br/vitrine',

    // Origens autorizadas a consumir a API (o endereço do seu Moodle).
    // Use ['*'] para liberar qualquer origem (conteúdo é público, sem risco).
    'cors_origens' => ['*'],

    // Segurança da sessão do painel admin
    'sessao' => [
        'nome'            => 'AUTORIASCS_ADMIN',
        'tempo_vida'      => 3600 * 4, // 4 horas
        'apenas_https'    => true,     // coloque false somente em ambiente local
    ],

    // Proteção contra força bruta no login
    'login' => [
        'max_tentativas'  => 5,   // tentativas com falha permitidas...
        'janela_minutos'  => 15,  // ...dentro desta janela de tempo (por IP + e-mail)
    ],

    // Upload de imagens
    'upload' => [
        'pasta'        => __DIR__ . '/uploads',
        'url'          => null, // null = base_url . '/uploads'
        'tamanho_max'  => 4 * 1024 * 1024, // 4 MB
    ],
];
