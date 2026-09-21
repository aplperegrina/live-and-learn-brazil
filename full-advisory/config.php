<?php
/* Live & Learn Brazil — formulário Full advisory
   Configuração compartilhada por index.php (carimbo de hora assinado) e send.php (envio).
   Nada aqui é servido ao navegador: o PHP executa o arquivo e não devolve o texto. */

declare(strict_types=1);

/* Para onde vai a mensagem e de onde saem os dois e-mails.
   O remetente TEM de ser um endereço do próprio domínio, senão a Hostinger descarta. */
const FA_DESTINO        = 'reception@liveandlearnbrazil.com';
const FA_REMETENTE      = 'reception@liveandlearnbrazil.com';
const FA_NOME_REMETENTE = 'Live & Learn Brazil';

/* Segredo que assina o carimbo de hora do formulário (anti-robô).
   Qualquer texto longo serve. Troque se o repositório for público. */
const FA_SEGREDO = 'llb-fa-2026-09-21-9f1c4e7b2a6d8c3e5f0a1b2c3d4e5f60';

/* Envios mais rápidos que isto (segundos após abrir a página) são descartados;
   carimbos mais velhos que isto pedem uma nova abertura da página. */
const FA_ATRASO_MINIMO = 3;
const FA_IDADE_MAXIMA  = 86400;

/* Para onde send.php redireciona quando o formulário é enviado sem JavaScript. */
const FA_PAGINA = '/full-advisory/';
