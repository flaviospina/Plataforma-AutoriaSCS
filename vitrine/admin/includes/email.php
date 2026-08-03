<?php
/**
 * Envio de avisos por e-mail sobre ações nas contas dos usuários do painel.
 * O modelo visual segue o padrão institucional do CECAPE.
 */

declare(strict_types=1);

/** Configuração de e-mail com valores padrão (funciona mesmo se o config.php antigo não tiver a seção). */
function email_config(): array
{
    global $CONFIG;
    $padrao = [
        'ativar'         => true,
        'remetente'      => 'nao-responda@cecapescs.com.br',
        'nome_remetente' => 'CECAPE - Plataforma AutoriaSCS',
        'logo_esquerda'  => 'https://cecapescs.com.br/logos/logo-seeduc.png',
        'logo_centro'    => 'https://cecapescs.com.br/logos/logo-autoriascs.png',
        'logo_direita'   => 'https://cecapescs.com.br/logos/logo-cecape.png',
        // Inclui a senha provisória no e-mail de criação de conta / redefinição
        'enviar_senha_provisoria' => true,
    ];
    return array_merge($padrao, $CONFIG['email'] ?? []);
}

/**
 * Envia um aviso de ação na conta para o e-mail do usuário.
 *
 * @param string $paraEmail  E-mail do destinatário
 * @param string $paraNome   Nome do destinatário
 * @param string $titulo     Título do aviso (ex.: "Senha redefinida")
 * @param string $mensagem   Frase explicando o que aconteceu
 * @param array  $dados      Pares rótulo => valor exibidos no card (somente o necessário)
 * @return bool  true se o e-mail foi aceito para envio
 */
function enviar_email_conta(string $paraEmail, string $paraNome, string $titulo, string $mensagem, array $dados = []): bool
{
    $cfg = email_config();
    if (empty($cfg['ativar']) || !filter_var($paraEmail, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $linhas = '';
    foreach ($dados as $rotulo => $valor) {
        if ($valor === null || $valor === '') {
            continue;
        }
        $linhas .= '<p class="info-row"><span class="info-label">' . e((string) $rotulo) . ':</span> '
                 . '<span class="info-value">' . e((string) $valor) . '</span></p>' . "\n";
    }

    $logos = '';
    $imagens = array_filter([
        'SEEDUC'    => $cfg['logo_esquerda'] ?? '',
        'AutoriaSCS' => $cfg['logo_centro'] ?? '',
        'CECAPE'    => $cfg['logo_direita'] ?? '',
    ]);
    if ($imagens) {
        $largura = number_format(100 / count($imagens), 2, '.', '');
        $celulas = '';
        foreach ($imagens as $alt => $url) {
            $celulas .= '<td style="width:' . $largura . '%"><img src="' . e($url) . '" alt="' . e($alt) . '"></td>';
        }
        $logos = '<table class="header-table"><tr>' . $celulas . '</tr></table>';
    }

    $html = '<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif; color: #333; line-height: 1.6; background-color: #28b1d4; margin: 0; padding: 20px; }
    .container { width: 100%; max-width: 600px; margin: 0 auto; border: 1px solid #e0e0e0; border-radius: 12px; overflow: hidden; background-color: #ffffff; }
    .header { background-color: #ffffff; padding: 25px; text-align: center; border-bottom: 4px solid #00accf; }
    .header-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    .header-table td { text-align: center; vertical-align: middle; padding: 10px; }
    .header-table img { width: 100%; max-width: 160px; height: auto; display: inline-block; }
    .content { padding: 35px; background-color: #ffffff; }
    .card { background: #f8f9fa; padding: 25px; border-radius: 8px; border-left: 5px solid #00accf; margin: 20px 0; }
    .info-row { margin-bottom: 12px; font-size: 15px; border-bottom: 1px solid #eee; padding-bottom: 5px; }
    .info-label { font-weight: bold; color: #00accf; text-transform: uppercase; font-size: 12px; display: block; }
    .info-value { color: #333; font-size: 16px; }
    h2 { color: #00accf; margin-top: 0; font-size: 22px; text-align: center; }
    .footer { background-color: #00accf; color: #ffffff; text-align: center; padding: 20px; font-size: 13px; font-weight: 300; }
    .no-reply { margin-top: 20px; padding: 15px; background-color: #fff3cd; border: 1px solid #ffeeba; border-radius: 8px; color: #856404; font-size: 13px; text-align: center; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">' . $logos . '</div>

    <div class="content">
      <h2>Olá, <strong>' . e($paraNome) . '</strong>!</h2>
      <p>' . e($mensagem) . '</p>

      <div class="card">
' . $linhas . '      </div>

      <p style="margin-top: 20px;">
        Se você não reconhece esta ação, entre em contato com o administrador da plataforma imediatamente.
      </p>

      <!-- Aviso de mensagem automática -->
      <div class="no-reply">
        ⚠️ Esta é uma mensagem automática enviada pelo sistema do CECAPE.<br>
        Por favor, não responda este e-mail, pois esta caixa de entrada não é monitorada.
      </div>
    </div>

    <div class="footer">
      CECAPE - Centro de Capacitação de Profissionais da Educação<br>
      Secretaria Municipal de Educação de São Caetano do Sul
    </div>
  </div>
</body>
</html>';

    $assunto = mb_encode_mimeheader('[AutoriaSCS] ' . $titulo, 'UTF-8', 'B');
    $remetente = $cfg['remetente'];
    $nomeRemetente = mb_encode_mimeheader($cfg['nome_remetente'], 'UTF-8', 'B');

    $cabecalhos = implode("\r\n", [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $nomeRemetente . ' <' . $remetente . '>',
        'Reply-To: ' . $remetente,
        'X-Mailer: AutoriaSCS-Vitrine',
    ]);

    // Falha de envio nunca deve interromper a operação do painel
    try {
        return @mail($paraEmail, $assunto, $html, $cabecalhos);
    } catch (Throwable $ex) {
        return false;
    }
}
