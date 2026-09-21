<?php
/* Live & Learn Brazil — formulário Full advisory
   Configuração compartilhada por index.php (carimbo de hora assinado) e send.php (envio).
   Nada aqui é servido ao navegador: o PHP executa o arquivo e não devolve o texto,
   e o .htaccess ainda nega pedidos diretos a config.php. */

declare(strict_types=1);

/* Para onde vai a mensagem e de onde saem os dois e-mails.
   O remetente TEM de ser um endereço do próprio domínio, senão a Hostinger descarta. */
const FA_DESTINO        = 'reception@liveandlearnbrazil.com';
const FA_REMETENTE      = 'reception@liveandlearnbrazil.com';
const FA_NOME_REMETENTE = 'Live & Learn Brazil';

/* Segredo que assina o carimbo de hora do formulário (anti-robô).
   O repositório é público, por isso o segredo NÃO fica aqui: é gerado na primeira
   abertura da página e guardado num arquivo fora do public_html (a pasta acima da
   raiz do site, que a Hostinger dá ao mesmo usuário do PHP). Para trocar, apague
   o arquivo .fa-segredo: o próximo pedido gera outro. */
function fa_segredo(): string
{
    static $segredo = null;
    if ($segredo !== null) return $segredo;

    $raiz = rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
    $candidatos = array_values(array_unique(array_filter([
        $raiz !== '' ? dirname($raiz) . '/.fa-segredo' : '',
        dirname(__DIR__, 2) . '/.fa-segredo',
    ])));

    foreach ($candidatos as $caminho) {
        if (is_readable($caminho)) {
            $lido = trim((string) @file_get_contents($caminho));
            if (strlen($lido) >= 32) return $segredo = $lido;
        }
    }
    $novo = bin2hex(random_bytes(32));
    foreach ($candidatos as $caminho) {
        if (@file_put_contents($caminho, $novo . "\n", LOCK_EX) !== false) {
            @chmod($caminho, 0600);
            return $segredo = $novo;
        }
    }
    /* Sem pasta gravável: valor ligado à máquina, não ao repositório. Estável
       entre pedidos, e ainda assim impossível de deduzir só do código. */
    return $segredo = hash('sha256', php_uname() . '|' . $raiz . '|' . (fileinode(__FILE__) ?: '') . '|' . (filectime(__FILE__) ?: ''));
}

/* Envios mais rápidos que isto (segundos após abrir a página) são descartados;
   carimbos mais velhos que isto pedem uma nova abertura da página. */
const FA_ATRASO_MINIMO = 3;
const FA_IDADE_MAXIMA  = 86400;

/* Para onde send.php redireciona quando o formulário é enviado sem JavaScript. */
const FA_PAGINA = '/full-advisory/';
