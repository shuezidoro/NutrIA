<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function gerarToken() {
    return bin2hex(random_bytes(32));
}

function gerarCodigoVerificacao() {
    return strval(random_int(100000, 999999)); // 6 dígitos
}

function enviarEmail($destino, $assunto, $corpo, $imagens = []) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'suportenutria18@gmail.com';
        $mail->Password   = 'wsbmjjaaycvdlglo';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom('suportenutria18@gmail.com', 'NutrIA');
        $mail->addAddress($destino);
        $mail->Subject = $assunto;
        $mail->Body    = $corpo;
        $mail->isHTML(true);

        // Versão em texto puro 
        $semEstilo = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $corpo);
        $mail->AltBody = trim(html_entity_decode(strip_tags($semEstilo), ENT_QUOTES, 'UTF-8'));

        // Imagens embutidas 
        foreach ($imagens as $img) {
            if (!empty($img['caminho']) && is_file($img['caminho'])) {
                $mail->addEmbeddedImage($img['caminho'], $img['cid']);
            } else {
                error_log("Imagem do e-mail não encontrada: " . ($img['caminho'] ?? ''));
            }
        }

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("Erro ao enviar e-mail: " . $mail->ErrorInfo);
        return false;
    }
}


/* =====================================================
   MODELO PADRÃO DE E-MAIL DO NUTRIA
===================================================== */

/** Caminho do mascote no disco (raiz do projeto / public/img). */
function caminhoMascoteEmail() {
    return __DIR__ . '/../../public/img/Nutri_carteiro.png';
}

/** Envia usando o modelo padrão: embute o mascote (cid:mascote). */
function enviarEmailNutria($destino, $assunto, $corpo) {
    return enviarEmail($destino, $assunto, $corpo, [
        ['caminho' => caminhoMascoteEmail(), 'cid' => 'mascote']
    ]);
}

/** Caixa cinza com o código de verificação. */
function blocoCodigoEmail($codigo) {
    $codigo = htmlspecialchars((string)$codigo, ENT_QUOTES, 'UTF-8');
    return "
      <table role='presentation' width='100%' cellpadding='0' cellspacing='0' border='0'>
        <tr>
          <td align='center' style='background-color:#bcbcbc; border:1px solid #1b3d2b; border-radius:24px; padding:22px 12px;'>
            <span class='codigo' style='font-size:42px; font-weight:800; letter-spacing:8px; color:#1b3d2b;'>{$codigo}</span>
          </td>
        </tr>
      </table>";
}

/** Botão verde-escuro com link. */
function botaoEmail($link, $texto) {
    $link  = htmlspecialchars((string)$link, ENT_QUOTES, 'UTF-8');
    $texto = htmlspecialchars((string)$texto, ENT_QUOTES, 'UTF-8');
    return "
      <table role='presentation' width='100%' cellpadding='0' cellspacing='0' border='0'>
        <tr>
          <td align='center' style='background-color:#1b3d2b; border-radius:24px; padding:20px 12px;'>
            <a href='{$link}' style='display:block; font-size:18px; font-weight:800; color:#ffffff; text-decoration:none;'>{$texto}</a>
          </td>
        </tr>
      </table>";
}


function corpoEmailNutria($tituloHtml, $nome, $introHtml, $destaqueHtml, $notaHtml = '', $avisoHtml = '') {
    $saudacao = trim((string)$nome) !== ''
        ? "Olá, <span style='color:#1b3d2b;'>" . htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') . "</span>!"
        : "Olá!";

    $nota  = $notaHtml  !== '' ? "<p style='margin:14px 0 0 0; font-size:13px; line-height:1.5; color:#555555;'>{$notaHtml}</p>" : '';
    $aviso = $avisoHtml !== '' ? "<tr><td colspan='2' align='center' style='padding:28px 0 0 0; font-size:14px; line-height:1.5; color:#222222;'>{$avisoHtml}</td></tr>" : '';

    return "
<!DOCTYPE html>
<html lang='pt-BR'>
<head>
<meta charset='UTF-8'>
<meta name='viewport' content='width=device-width, initial-scale=1'>
<style>
  @media only screen and (max-width: 520px) {
    .col       { display: block !important; width: 100% !important; }
    .col-img   { padding-bottom: 8px !important; }
    .mascote   { width: 170px !important; }
    .titulo    { font-size: 28px !important; }
    .codigo    { font-size: 32px !important; letter-spacing: 5px !important; }
  }
</style>
</head>
<body style='margin:0; padding:0; background-color:#ffffff;'>
  <table role='presentation' width='100%' cellpadding='0' cellspacing='0' border='0' style='background-color:#ffffff;'>
    <tr>
      <td align='center' style='padding:28px 12px;'>

        <table role='presentation' width='600' cellpadding='0' cellspacing='0' border='0' style='width:100%; max-width:600px; font-family:Arial, Helvetica, sans-serif; color:#111111;'>

          <tr>
            <td colspan='2' align='center' style='padding:0 0 24px 0;'>
              <h1 class='titulo' style='margin:0; font-size:36px; line-height:1.2; font-weight:800; color:#111111;'>{$tituloHtml}</h1>
            </td>
          </tr>

          <tr>
            <td class='col col-img' width='40%' valign='middle' align='center' style='padding:0 8px 0 0;'>
              <img class='mascote' src='cid:mascote' alt='Mascote do NutrIA' width='220'
                   style='display:block; width:220px; max-width:100%; height:auto; border:0; margin:0 auto;'>
            </td>

            <td class='col' width='60%' valign='middle' align='center' style='padding:0 0 0 8px;'>
              <h2 style='margin:0 0 10px 0; font-size:34px; line-height:1.2; font-weight:800; color:#111111;'>{$saudacao}</h2>
              <p style='margin:0 0 16px 0; font-size:15px; line-height:1.5; color:#333333;'>{$introHtml}</p>
              {$destaqueHtml}
              {$nota}
            </td>
          </tr>

          {$aviso}
          <tr>
            <td colspan='2' align='center' style='padding:14px 0 0 0; font-size:11px; color:#888888;'>
              NutrIA - Sistema de acompanhamento nutricional
            </td>
          </tr>

        </table>

      </td>
    </tr>
  </table>
</body>
</html>";
}