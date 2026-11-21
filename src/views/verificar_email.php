<?php
session_start();

if (!isset($_SESSION['email_pendente_verificacao'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../public/css/verificar_email_style.css">
    <link rel="icon" type="image/svg+xml" href="../../public/img/logo.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <title>Verificar e-mail - NutrIA</title>
</head>

<body>
    <div class="background-glow glow-1"></div>
    <div class="background-glow glow-2"></div>

    <main class="verify-container">
        <section class="verify-intro">
            <div class="intro-content">
                <span class="intro-label">SEGURANÇA DA CONTA</span>
                <h1>confirme seu <span>e-mail.</span></h1>
                <p>Estamos protegendo sua conta NutrIA. Digite o código de verificação enviado para seu e-mail para
                    continuar e acompanhar sua alimentação, suas metas e sua evolução.</p>

            </div>
        </section>

        <section class="verify-area">
            <div class="verify-card">
                <div class="form-header">
                    <span class="form-label">VERIFICAÇÃO</span>
                    <h2>Informe o codigo enviado</h2>
                    <p>Enviamos um código de 6 dígitos para o seu e-mail. Digite-o abaixo.</p>
                </div>

                <?php if (isset($_GET['erro'])): ?>
                    <div class="error-message">
                        <?php if ($_GET['erro'] === 'envio'): ?>
                            Não foi possível enviar o código. Tente novamente em instantes.
                        <?php else: ?>
                            Código inválido ou expirado. Tente novamente.
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['reenviado'])): ?>
                    <div class="success-message">
                        Se sua conta estiver aguardando verificação, um novo código foi solicitado.
                    </div>
                <?php endif; ?>

                <form action="../controllers/verificar_codigo_controller.php" method="POST" autocomplete="off">
                    <div class="form-section">
                        <div class="field">
                            <label for="icodigo">Código de verificação</label>
                            <input type="text" id="icodigo" name="codigo" maxlength="6" inputmode="numeric"
                                pattern="[0-9]{6}" placeholder="000000" autocomplete="one-time-code" required autofocus>
                        </div>
                    </div>

                    <button type="submit" class="submit-btn">
                        <span>Confirmar e-mail</span><b>→</b>
                    </button>
                </form>

                <form action="../controllers/reenviar_codigo_controller.php" method="POST" class="resend-form">
                    <button type="submit" class="resend-btn">Reenviar código</button>
                </form>

                <div class="verify-links">
                    <a href="login.php">Voltar para o login</a>
                </div>
            </div>
        </section>
    </main>
</body>

</html>