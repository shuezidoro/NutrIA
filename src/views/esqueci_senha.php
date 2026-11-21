<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../public/css/esqueci_senha_style.css">
    <link rel="icon" type="image/svg+xml" href="../../public/img/logo.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <title>Esqueci minha senha - NutrIA</title>
</head>

<body>

    <!-- ============================= -->
    <!-- EFEITOS DE FUNDO              -->
    <!-- ============================= -->
    <div class="background-glow glow-1"></div>
    <div class="background-glow glow-2"></div>

    <!-- ============================= -->
    <!-- CONTAINER PRINCIPAL            -->
    <!-- ============================= -->
    <main class="login-container">

        <!-- ============================= -->
        <!-- LADO ESQUERDO                  -->
        <!-- ============================= -->
        <section class="login-intro">

            <!-- CONTEÚDO -->
            <div class="intro-content">
                <span class="intro-label">ACESSAR SISTEMA</span>
                <h1>Recupere<span>seu acesso.</span></h1>
                <p>Recupere o acesso à sua conta e continue acompanhando sua alimentação, suas metas e sua evolução com
                    NutrIA.</p>
            </div>

        </section>


        <!-- ============================= -->
        <!-- ÁREA DO FORMULÁRIO             -->
        <!-- ============================= -->
        <section class="login-area">
            <div class="login-card">

                <!-- CABEÇALHO -->
                <div class="form-header">
                    <span class="form-label">RECUPERAÇÃO DE CONTA</span>
                    <h2>Esqueci minha senha.</h2>
                </div>

                <!-- ============================= -->
                <!-- MENSAGEM DE SUCESSO            -->
                <!-- ============================= -->
                <?php if (isset($_GET['enviado'])): ?>
                    <div class="success-message">
                        Se o e-mail existir na nossa base, enviamos um link de redefinição.
                        Confira também a caixa de spam.
                    </div>
                <?php endif; ?>

                <!-- ============================= -->
                <!-- FORMULÁRIO                     -->
                <!-- ============================= -->
                <form action="../controllers/esqueci_senha_controller.php" method="POST" autocomplete="off">

                    <div class="form-section">
                        <div class="fields-grid">

                            <!-- E-MAIL -->
                            <div class="field">
                                <label for="iemail">Digite seu e-mail cadastrado</label>
                                <input type="email" id="iemail" name="email" placeholder="seu@email.com" required>
                            </div>
                        </div>
                    </div>

                    <!-- ============================= -->
                    <!-- BOTÃO                         -->
                    <!-- ============================= -->
                    <button type="submit" class="submit-btn">
                        <span>Enviar link de recuperação</span><b>→</b>
                    </button>

                    <!-- LINKS -->
                    <div class="login-links">
                        <a href="login.php">
                            Voltar ao login
                        </a>
                    </div>
                </form>
            </div>
        </section>
    </main>

</body>

</html>