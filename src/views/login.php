<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../public/css/login_style.css">
<<<<<<< HEAD
    <link rel="icon" type="image/svg+xml" href="../../public/img/logo.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
=======
>>>>>>> ed52c55af782febf7e37094038ec6d7994096129
    <title>Login - NutrIA</title>
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
                <h1>Bem-vindo<span>de volta.</span></h1>
                <p>Acesse sua conta e continue acompanhando sua alimentação, suas metas e sua evolução com NutrIA.</p>


<<<<<<< HEAD
            </div>
        </section>
=======
    <p><a href="esqueci_senha.php">Esqueci minha senha</a></p>
    <p>Não tem uma conta? <a href="cadastro.php">Cadastre-se aqui</a></p>
>>>>>>> ed52c55af782febf7e37094038ec6d7994096129


        <!-- ============================= -->
        <!-- ÁREA DO FORMULÁRIO             -->
        <!-- ============================= -->
        <section class="login-area">
            <div class="login-card">
                <a href="../../index.php" class="back-home">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M19 12H5M12 19l-7-7 7-7" />
                    </svg>
                    <span>Voltar ao início</span>
                </a>
                <div class="form-header">
                    <span class="form-label">LOGIN</span>
                    <h2>Fazer login.<h2>
                </div>

                <!-- ============================= -->
                <!-- MENSAGENS DE ERRO              -->
                <!-- ============================= -->

                <?php if (isset($_GET['erro'])): ?>
                    <div class="error-message">
                        <?php if ($_GET['erro'] === 'credenciais_invalidas'): ?>
                            E-mail ou senha incorretos. Verifique e tente novamente.
                        <?php elseif ($_GET['erro'] === 'email_nao_verificado'): ?>
                            Por favor, verifique seu e-mail antes de fazer login.
                        <?php elseif ($_GET['erro'] === 'conta_inativa'): ?>
                            Sua conta foi desativada. Entre em contato com o suporte.
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['sucesso'])): ?>
                    <div class="success-message">
                        <?php if ($_GET['sucesso'] === 'senha_resetada'): ?>
                            Sua senha foi redefinida com sucesso. Agora faça login com a nova senha.
                        <?php elseif ($_GET['sucesso'] === 'email_verificado'): ?>
                            E-mail verificado com sucesso! Agora você pode fazer login.
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- ============================= -->
                <!-- FORMULÁRIO                     -->
                <!-- ============================= -->

                <form action="../controllers/auth_controller.php" method="POST" autocomplete="off">

                    <input type="hidden" name="acao" value="login">

                    <!-- ============================= -->
                    <!-- CAMPOS                         -->
                    <!-- ============================= -->

                    <div class="form-section">
                        <div class="fields-grid">

                            <!-- E-MAIL -->
                            <div class="field">
                                <label for="iemail">Seu e-mail</label>
                                <input type="email" id="iemail" name="email" placeholder="seu@email.com" required>
                            </div>

                            <!-- SENHA -->
                            <div class="field">
                                <label for="isenha">Sua senha</label>
                                <input type="password" id="isenha" name="senha" placeholder="Sua senha" required>
                            </div>
                        </div>
                    </div>

                    <!-- ============================= -->
                    <!-- BOTÃO                         -->
                    <!-- ============================= -->
                    <button type="submit" class="submit-btn">
                        <span>Fazer login</span><b>→</b>
                    </button>

                    <!-- LINKS -->
                    <div class="login-links">
                        <a href="esqueci_senha.php">
                            Esqueci minha senha
                        </a>

                        <div class="divider">
                            Não tem uma conta?
                            <a href="cadastro.php">
                                Cadastre-se aqui
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </section>
    </main>
</body>
</html>