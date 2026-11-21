<?php
session_start();
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../config/funcoes.php';

if (!isset($_SESSION['email_pendente_verificacao'])) {
  header("Location: ../views/login.php");
  exit;
}

$email = $_SESSION['email_pendente_verificacao'];

$stmt = $pdo->prepare("SELECT id, nome FROM usuarios WHERE email = ?");
$stmt->execute([$email]);
$usuario = $stmt->fetch();

if ($usuario) {
  $novoCodigo = gerarCodigoVerificacao();
  $novaExpira = date('Y-m-d H:i:s', strtotime('+15 minutes'));

  $pdo->prepare("UPDATE usuarios SET token_verificacao = ?, codigo_expira_em = ? WHERE id = ?")
    ->execute([$novoCodigo, $novaExpira, $usuario['id']]);

  // Mascote embutido no e-mail (CID)
  $caminhoMascote = __DIR__ . '/../../public/img/Nutri_carteiro.png';

  // Evita HTML injetado pelo nome
  $nomeSeguro = htmlspecialchars($usuario['nome'], ENT_QUOTES, 'UTF-8');

  $corpo = "
<!DOCTYPE html>
<html lang='pt-BR'>
<head>
<meta charset='UTF-8'>
<meta name='viewport' content='width=device-width, initial-scale=1'>
<meta name='color-scheme' content='light only'>
<meta name='supported-color-schemes' content='light only'>
<style>
  :root { color-scheme: light only; supported-color-schemes: light only; }

  /* ---------- Celular: colunas empilhadas ---------- */
  @media only screen and (max-width: 520px) {
    .col       { display: block !important; width: 100% !important; }
    .col-img   { padding: 0 0 8px 0 !important; }
    .col-txt   { padding: 0 !important; }
    .mascote   { width: 170px !important; }
    .titulo    { font-size: 28px !important; }
    .saudacao  { font-size: 28px !important; }
    .codigo    { font-size: 32px !important; letter-spacing: 5px !important; }
  }

  /* ---------- Modo escuro: mantém o e-mail claro ---------- */
  @media (prefers-color-scheme: dark) {
    .fundo        { background-color: #ffffff !important; background-image: linear-gradient(#ffffff,#ffffff) !important; }
    .t-escuro     { color: #111111 !important; }
    .t-medio      { color: #333333 !important; }
    .t-nota       { color: #555555 !important; }
    .t-rodape     { color: #888888 !important; }
    .t-verde      { color: #1b3d2b !important; }
    .t-branco     { color: #ffffff !important; }
    .caixa-codigo { background-color: #bcbcbc !important; border-color: #1b3d2b !important; }
    .caixa-botao  { background-color: #1b3d2b !important; }
  }

  /* Outlook.com / app Outlook */
  [data-ogsb] .fundo        { background-color: #ffffff !important; }
  [data-ogsc] .t-escuro     { color: #111111 !important; }
  [data-ogsc] .t-medio      { color: #333333 !important; }
  [data-ogsc] .t-nota       { color: #555555 !important; }
  [data-ogsc] .t-rodape     { color: #888888 !important; }
  [data-ogsc] .t-verde      { color: #1b3d2b !important; }
  [data-ogsc] .t-branco     { color: #ffffff !important; }
  [data-ogsb] .caixa-codigo { background-color: #bcbcbc !important; }
  [data-ogsb] .caixa-botao  { background-color: #1b3d2b !important; }
</style>
</head>
<body class='fundo' bgcolor='#ffffff' style='margin:0; padding:0; -webkit-text-size-adjust:100%; background-color:#ffffff; background-image:linear-gradient(#ffffff,#ffffff);'>
  <table class='fundo' role='presentation' width='100%' cellpadding='0' cellspacing='0' border='0' bgcolor='#ffffff' style='background-color:#ffffff; background-image:linear-gradient(#ffffff,#ffffff);'>
    <tr>
      <td class='fundo' align='center' bgcolor='#ffffff' style='padding:28px 12px; background-color:#ffffff; background-image:linear-gradient(#ffffff,#ffffff);'>

        <table class='fundo' role='presentation' width='600' cellpadding='0' cellspacing='0' border='0' bgcolor='#ffffff' style='width:100%; max-width:600px; font-family:Arial, Helvetica, sans-serif; color:#111111; background-color:#ffffff; background-image:linear-gradient(#ffffff,#ffffff);'>

          <!-- Título -->
          <tr>
            <td colspan='2' align='center' style='padding:0 0 24px 0;'>
              <h1 class='titulo t-escuro' style='margin:0; font-size:36px; line-height:1.2; font-weight:800; color:#111111;'>
                Novo código de verificação
              </h1>
            </td>
          </tr>

          <!-- Mascote + conteúdo -->
          <tr>
            <td class='col col-img' width='40%' valign='middle' align='center' style='padding:0 8px 0 0;'>
              <img class='mascote' src='cid:mascote' alt='Mascote do NutrIA' width='220'
                   style='display:block; width:220px; max-width:100%; height:auto; border:0; margin:0 auto;'>
            </td>

            <td class='col col-txt' width='60%' valign='middle' align='center' style='padding:0 0 0 8px;'>
              <h2 class='saudacao t-escuro' style='margin:0 0 10px 0; font-size:34px; line-height:1.2; font-weight:800; color:#111111;'>
                Olá, <span class='t-verde' style='color:#1b3d2b;'>{$nomeSeguro}</span>!
              </h2>

              <p class='t-medio' style='margin:0 0 16px 0; font-size:15px; line-height:1.5; color:#333333;'>
                Use o novo código abaixo para confirmar seu e-mail:
              </p>

              <table role='presentation' width='100%' cellpadding='0' cellspacing='0' border='0'>
                <tr>
                  <td class='caixa-codigo' align='center' bgcolor='#bcbcbc' style='background-color:#bcbcbc; border:1px solid #1b3d2b; border-radius:24px; padding:22px 12px;'>
                    <span class='codigo t-verde' style='font-size:42px; font-weight:800; letter-spacing:8px; color:#1b3d2b;'>{$novoCodigo}</span>
                  </td>
                </tr>
              </table>

              <p class='t-nota' style='margin:14px 0 0 0; font-size:13px; line-height:1.5; color:#555555;'>
                Este código é válido por <strong>15 minutos</strong>.
              </p>
            </td>
          </tr>

          <!-- Rodapé -->
          <tr>
            <td class='t-escuro' colspan='2' align='center' style='padding:28px 0 0 0; font-size:14px; line-height:1.5; color:#222222;'>
              Se não foi você que solicitou o código, ignore este e-mail.
            </td>
          </tr>
          <tr>
            <td class='t-rodape' colspan='2' align='center' style='padding:14px 0 0 0; font-size:11px; color:#888888;'>
              NutrIA - Sistema de acompanhamento nutricional
            </td>
          </tr>

        </table>

      </td>
    </tr>
  </table>
</body>
</html>
    ";

  enviarEmail($email, "Novo código de verificação - NutrIA", $corpo, [
    ['caminho' => $caminhoMascote, 'cid' => 'mascote']
  ]);
}

header("Location: ../views/verificar_email.php?reenviado=1");
exit;