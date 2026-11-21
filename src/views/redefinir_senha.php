<<<<<<< HEAD
<?php $token = $_GET['token'] ?? ''; ?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../public/css/redefinir_senha_style.css">
    <link rel="icon" type="image/svg+xml" href="../../public/img/logo.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <title>Redefinir senha - NutrIA</title>
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
                <h1>Crie uma<span>nova senha.</span></h1>
                <p>Defina uma nova senha para sua conta e volte a acompanhar sua alimentação, suas metas e sua evolução
                    com NutrIA.</p>
            </div>
        </section>


        <!-- ============================= -->
        <!-- ÁREA DO FORMULÁRIO             -->
        <!-- ============================= -->
        <section class="login-area">
            <div class="login-card">

                <!-- CABEÇALHO -->
                <div class="form-header">
                    <span class="form-label">REDEFINIR SENHA</span>
                    <h2>Definir nova senha.</h2>
                </div>

                <!-- ============================= -->
                <!-- AVISOS NA TELA                 -->
                <!-- ============================= -->
                <?php if ($token === ''): ?>
                    <div class="error-message">
                        Link de redefinição inválido ou incompleto.
                        <a href="esqueci_senha.php">Solicite um novo link</a>.
                    </div>
                <?php elseif (isset($_GET['erro'])): ?>
                    <div class="error-message">
                        Não foi possível redefinir a senha. O link pode ter expirado.
                        <a href="esqueci_senha.php">Solicite um novo link</a>.
                    </div>
                <?php endif; ?>

                <!-- ============================= -->
                <!-- FORMULÁRIO                     -->
                <!-- ============================= -->
                <form action="../controllers/redefinir_senha_controller.php" method="POST" autocomplete="off">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                    <div class="form-section">
                        <div class="fields-grid">

                            <!-- NOVA SENHA -->
                            <div class="field">
                                <label for="isenha">Nova senha</label>
                                <input type="password" id="isenha" name="senha" placeholder="Sua nova senha segura"
                                    required>
                            </div>
                        </div>
                    </div>

                    <!-- ============================= -->
                    <!-- BOTÃO                         -->
                    <!-- ============================= -->
                    <button type="submit" class="submit-btn">
                        <span>Redefinir senha</span><b>→</b>
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

=======
<?php
session_start();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../public/css/login_style.css">
    <title>Login - NutrIA</title>
</head>
<body>

    <h1>Acessar o Sistema</h1>

    <?php if (isset($_GET['erro_login'])): ?>
        <p style="color: red; font-weight: bold;">⚠️ E-mail ou senha incorretos!</p>
    <?php endif; ?>

    <?php if (isset($_GET['senha_alterada'])): ?>
        <p style="color: green; font-weight: bold;">✅ Senha atualizada com sucesso! Faça login com a nova senha.</p>
    <?php endif; ?>

    <form action="../controllers/auth_controller.php" method="POST" autocomplete="off">
        
        <input type="hidden" name="acao" value="login">

        <label for="iemail">E-mail:</label>
        <input type="email" id="iemail" name="email" required><br><br>

        <label for="isenha">Senha:</label>
        <input type="password" id="isenha" name="senha" required><br><br>

        <input type="submit" value="Entrar">
    </form>

    <p><a href="esqueci_senha.php">Esqueci minha senha</a></p>
    <p>Não tem uma conta? <a href="cadastro.php">Cadastre-se aqui</a></p>

</body>
>>>>>>> ed52c55af782febf7e37094038ec6d7994096129
</html>