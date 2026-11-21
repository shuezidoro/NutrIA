<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NutrIA — Criar conta</title>
    <link rel="stylesheet" href="../../public/css/cadastro_style.css">
    <link rel="icon" type="image/svg+xml" href="../../public/img/logo.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>

<body>
    <div class="background-glow glow-1"></div>
    <div class="background-glow glow-2"></div>

    <main class="register-container">
        <section class="register-intro">

            <div class="intro-content">
                <span class="intro-label">COMECE SUA JORNADA</span>
                <h1> Sua alimentação.<span>Mais inteligente.</span></h1>
                <p>Crie sua conta no NutrIA e comece a acompanhar
                    sua alimentação, suas metas e sua evolução
                    de forma simples e inteligente.
                </p>

            </div>
        </section>

        <section class="register-area">
            <div class="register-card">
                <a href="../../index.php" class="back-home">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M19 12H5M12 19l-7-7 7-7" />
                    </svg>
                    <span>Voltar ao início</span>
                </a>
                <div class="form-header">
                    <span class="form-label">CRIAR CONTA</span>
                    <h2> Vamos começar.</h2>
                </div>

                <!-- ============================= -->
                <!-- MENSAGENS DE ERRO              -->
                <!-- ============================= -->
                <?php if (isset($_GET['erro'])): ?>
                    <div class="error-message">
                        <?php if ($_GET['erro'] === 'email_formato_invalido'): ?>
                            O e-mail digitado não está em um formato válido.
                        <?php elseif ($_GET['erro'] === 'email_dominio_invalido'): ?>
                            O domínio desse e-mail não existe. Verifique e tente novamente.
                        <?php elseif ($_GET['erro'] === 'email_existente'): ?>
                            Este e-mail já está cadastrado.
                            <a href="login.php">Faça login</a> ou use outro e-mail.
                        <?php else: ?>
                            Não foi possível concluir o cadastro. Tente novamente.
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- ============================= -->
                <!-- FORMULÁRIO                     -->
                <!-- ============================= -->

                <form action="../controllers/auth_controller.php" method="POST" autocomplete="off">
                    <input type="hidden" name="acao" value="cadastro">
                    <div class="form-section">
                        <div class="fields-grid">

                            <div class="field"> <!-- NOME -->
                                <label for="inome">Seu nome</label>
                                <input type="text" id="inome" name="nome" placeholder="Como podemos chamar você?" minlength="3" maxlength="50" required>
                            </div>

                            <div class="field"> <!--EMAIL-->
                                <label for="iemail">Seu e-mail</label>
                                <input type="email" id="iemail" name="email" placeholder="seu@email.com" required>
                            </div>

                            <div class="field"> <!--SENHA-->
                                <label for="isenha"> Sua senha</label>
                                <input type="password" id="isenha" minlength="8" maxlength="15" name="senha" placeholder="Mínimo de 8 caracteres" required>
                            </div>

                            <div class="field"> <!--IDADE-->
                                <label for="iidade">Idade </label>
                                <input type="number" id="iidade" name="idade" placeholder="Ex.: 18" min="15" max="100" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="fields-grid">

                            <div class="field"><!-- PESO -->
                                <label for="ipeso">Peso</label>
                                <div class="input-unit">
                                    <input type="number" id="ipeso" name="peso" step="0.10" placeholder="Ex.: 70" min="30" max="300" required>
                                    <span>kg</span>
                                </div>
                            </div>

                            <div class="field">  <!-- ALTURA -->
                                <label for="ialtura">Altura</label>
                                <div class="input-unit">
                                    <input type="number" id="ialtura" name="altura" step="1" placeholder="Ex.: 175" min="80" max="250" required>
                                    <span>cm</span>
                                </div>
                            </div>

                            <div class="field"> <!-- SEXO -->
                                <label for="isexo">Sexo</label>
                                <select id="isexo" name="sexo" required>
                                    <option value="Masculino">Masculino</option>
                                    <option value="Feminino">Feminino</option>
                                </select>
                            </div>
                            
                            <div class="field"> <!-- ATIVIDADE -->
                                <label for="iatv">Atividade física</label>
                                <select id="iatv" name="atividade" required>
                                    <option value="Sedentario">Sedentário</option>
                                    <option value="Leve">Leve</option>
                                    <option value="Moderado"> Moderado</option>
                                    <option value="Ativo">Ativo</option>
                                    <option value="Muito_Ativo">Muito Ativo</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-section objective-section">
                        <div class="field">  <!-- OBJETIVO-->
                            <label for="iobjetivo">Qual é o seu principal objetivo?</label>
                            <select id="iobjetivo" name="objetivo" required>
                                <option value="Perda_Peso">Perder peso</option>
                                <option value="Manter_Saude">Manter saúde</option>
                                <option value="Ganho_Massa"> Ganhar peso</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="submit-btn">
                        <span>Criar minha conta</span><b>→</b>
                    </button>

                    <div class="login-link"> Já possui uma conta? <a href="login.php"> Fazer login</a>
                    </div>
                </form>
            </div>
        </section>
    </main>
</body>
</html>