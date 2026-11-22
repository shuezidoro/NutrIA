<?php
session_start();

// Trava de segurança: Se não estiver logado, expulsa para o login
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/../config/conexao.php';

$usuario_id   = $_SESSION['usuario_id'];
$usuario_nome = $_SESSION['usuario_nome'] ?? 'Usuário';
$inicial      = mb_strtoupper(mb_substr(trim($usuario_nome), 0, 1));

// Busca os dados biométricos do usuário que acabaram de ser cadastrados
$stmt = $pdo->prepare("SELECT * FROM biometria WHERE usuario_id = ?");
$stmt->execute([$usuario_id]);
$biometria = $stmt->fetch();

if (!$biometria) {
    header("Location: cadastro.php");
    exit;
}

//ESTIMATIVA DAS METAS:
$peso = (float) ($biometria['peso'] ?? 0);
$altura_cm = (float) ($biometria['altura'] ?? 0) * 100;
$idade = (int) ($biometria['idade'] ?? 0);
$genero = trim((string) ($biometria['genero'] ?? ''));
$nivel_atividade = trim((string) ($biometria['nivel_atividade'] ?? 'Sedentario'));
$objetivo = trim((string) ($biometria['objetivo'] ?? ''));

if ($peso <= 0 || $altura_cm <= 0 || $idade <= 0) {
    http_response_code(400);
    exit('Peso, altura ou idade inválidos. Atualize seu perfil antes de calcular as metas.');
}

// Mifflin-St Jeor --> MÉTODO DO CALCULO
$tmb_masculina = (10 * $peso) + (6.25 * $altura_cm) - (5 * $idade) + 5;
$tmb_feminina = (10 * $peso) + (6.25 * $altura_cm) - (5 * $idade) - 161;
if ($genero === 'Masculino') {
    $tmb = $tmb_masculina;
} elseif ($genero === 'Feminino') {
    $tmb = $tmb_feminina;
} else {
    $tmb = ($tmb_masculina + $tmb_feminina) / 2;
}

$fatores = [
    'Sedentario' => 1.2,
    'Leve' => 1.375,
    'Moderado' => 1.55,
    'Ativo' => 1.725,
    'Muito_Ativo' => 1.9
];
$fator = $fatores[$nivel_atividade] ?? 1.2;
$get_estimado = $tmb * $fator;

// Ajustes padrão moderados
$ajuste = 0;
if ($objetivo === 'Perda_Peso') {
    $ajuste = -400;
} elseif ($objetivo === 'Ganho_Massa') {
    $ajuste = 300;
}
$meta_final_auto = (int) round($get_estimado + $ajuste);
$aviso_calorico = $meta_final_auto < 1200;
if ($aviso_calorico) {
    $meta_final_auto = 1200;
}

// Macros estimados pelo peso
$auto_prot = (int) round($peso * 1.6);
$auto_gord = (int) round($peso * 0.8);
$calorias_restantes = $meta_final_auto - ($auto_prot * 4) - ($auto_gord * 9);
if ($calorias_restantes < 0) {
    $auto_gord = max(0, (int) floor(($meta_final_auto - ($auto_prot * 4)) / 9));
    $calorias_restantes = $meta_final_auto - ($auto_prot * 4) - ($auto_gord * 9);
}
$auto_carbo = max(0, (int) round($calorias_restantes / 4));

// ============================================
// SALVAMENTO AUTOMÁTICO DAS METAS
// ============================================

try {
    // Verifica se o usuário já possui metas cadastradas
    $stmtMeta = $pdo->prepare("
        SELECT origem_meta
        FROM metas_diarias
        WHERE usuario_id = ?
        LIMIT 1
    ");

    $stmtMeta->execute([$usuario_id]);
    $meta_existente = $stmtMeta->fetch(PDO::FETCH_ASSOC);

    if (!$meta_existente) {

        // Primeiro acesso: salva as metas calculadas automaticamente
        $stmtSalvar = $pdo->prepare("
            INSERT INTO metas_diarias
            (usuario_id, kcal_meta, proteina_meta, carbo_meta, gordura_meta, origem_meta)
            VALUES (?, ?, ?, ?, ?, 'Sistema')
        ");

        $stmtSalvar->execute([
            $usuario_id,
            $meta_final_auto,
            $auto_prot,
            $auto_carbo,
            $auto_gord
        ]);

    } elseif ($meta_existente['origem_meta'] === 'Sistema') {

        // Atualiza metas automáticas quando o perfil biométrico mudar
        $stmtAtualizar = $pdo->prepare("
            UPDATE metas_diarias
            SET kcal_meta = ?,
                proteina_meta = ?,
                carbo_meta = ?,
                gordura_meta = ?,
                data_atualizacao = CURRENT_TIMESTAMP
            WHERE usuario_id = ?
        ");

        $stmtAtualizar->execute([
            $meta_final_auto,
            $auto_prot,
            $auto_carbo,
            $auto_gord,
            $usuario_id
        ]);
    }

} catch (PDOException $e) {
    error_log("Erro ao salvar metas automaticamente: " . $e->getMessage());

    http_response_code(500);
    exit("Não foi possível salvar suas metas. Tente novamente.");
}

$objetivos_legiveis = [
    'Perda_Peso' => 'Perda de peso',
    'Manutencao' => 'Manutenção',
    'Manutenção' => 'Manutenção',
    'Ganho_Massa' => 'Ganho de massa',
    'Ganho_Massa_Muscular' => 'Ganho de massa muscular'
];
$objetivo_legivel = $objetivos_legiveis[$objetivo] ?? str_replace('_', ' ', $objetivo);

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../public/css/metas_style.css">
<<<<<<< HEAD
    <link rel="icon" type="image/svg+xml" href="../../public/img/logo.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Rajdhani:wght@600;700&display=swap" rel="stylesheet">
=======
>>>>>>> ed52c55af782febf7e37094038ec6d7994096129
    <title>Minhas Metas - NutrIA</title>
    <script>
        (function () {
            var tema = 'light';
            try { if (localStorage.getItem('nutria-theme') === 'dark') tema = 'dark'; } catch (e) {}
            document.documentElement.setAttribute('data-theme', tema);
        })();
    </script>
</head>

<body>

    <div class="menu-backdrop" id="menuBackdrop"></div>

    <!-- ============================= -->
    <!-- HEADER                        -->
    <!-- ============================= -->
    <header class="main-header">
        <div class="logo-area">
            <div class="logo">
                <img class="logo-claro" src="../../public/img/NutrIA_claro.svg" alt="NutrIA">
                <img class="logo-escuro" src="../../public/img/NutrIA.svg" alt="NutrIA">
            </div>
        </div>

        <button type="button" class="menu-toggle" id="menuToggle" aria-label="Abrir menu" aria-expanded="false"
            aria-controls="navLinks">
            <span class="bars"></span>
        </button>

        <nav class="nav-links" id="navLinks">
            <a href="home.php" class="nav-item">
                <svg viewBox="0 0 24 24">
                    <path
                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                Home
            </a>
            <a href="dashboard.php" class="nav-item">
                <svg viewBox="0 0 24 24">
                    <rect x="3" y="4" width="18" height="18" rx="2" />
                    <line x1="16" y1="2" x2="16" y2="6" />
                    <line x1="8" y1="2" x2="8" y2="6" />
                    <line x1="3" y1="10" x2="21" y2="10" />
                </svg>
                Calendário
            </a>
            <a href="metas.php" class="nav-item active">
                <svg viewBox="0 0 24 24">
                    <path
                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                Metas
            </a>
            <a href="nutrientes.php" class="nav-item">
                <svg viewBox="0 0 24 24">
                    <path d="M2 3h6a4 4 0 014 4v14a3 3 0 00-3-3H2z" />
                    <path d="M22 3h-6a4 4 0 00-4 4v14a3 3 0 013-3h7z" />
                </svg>
                Nutrientes
            </a>
            <a href="perfil.php" class="nav-item">
                <svg viewBox="0 0 24 24">
                    <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" />
                    <circle cx="12" cy="7" r="4" />
                </svg>
                Meu Perfil
            </a>
        </nav>

        <div class="user-area">
            <span class="user-name"><strong><?php echo htmlspecialchars($usuario_nome); ?></strong></span>
            <button type="button" class="theme-toggle" id="themeToggle"
                    aria-label="Mudar para o tema escuro" title="Mudar para o tema escuro">
                <svg class="icon-moon" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12.8A9 9 0 1111.2 3a7 7 0 009.8 9.8z"/></svg>
                <svg class="icon-sun" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
            </button>
            <a href="../controllers/logout_controller.php" class="btn-logout">Sair</a>
        </div>
    </header>

    <!-- ============================= -->
    <!-- EFEITOS DE FUNDO              -->
    <!-- ============================= -->
    <div class="background-glow glow-1"></div>
    <div class="background-glow glow-2"></div>

    <!-- ============================= -->
    <!-- CONTAINER PRINCIPAL            -->
    <!-- ============================= -->
    <main class="metas-container">

        <!-- ============================= -->
        <!-- LADO ESQUERDO                  -->
        <!-- ============================= -->
        <section class="metas-intro">
            <div class="intro-content">
                <span class="intro-label">DEFINA SEUS OBJETIVOS</span>
                <h1>Suas metas<span>personalizadas.</span></h1>
                <p>NutrIA calcula automaticamente suas metas calóricas e de macronutrientes com base no seu
                    objetivo e perfil biométrico.</p>
            </div>
        </section>

        <!-- ============================= -->
        <!-- ÁREA DO FORMULÁRIO             -->
        <!-- ============================= -->
        <section class="metas-area">
            <div class="metas-card">

                <!-- CABEÇALHO -->
                <div class="form-header">
                    <span class="form-label">META CALÓRICA</span>
                    <h2>Sua meta diária.</h2>
                </div>

                <!-- DISPLAY DE CALORIAS -->
                <div class="calorie-display">
                    <div><?php echo number_format($meta_final_auto); ?></div>
                    <div>kcal por dia</div>
                </div>

                <!-- DESCRIÇÃO -->
                <p class="meta-description">
                    Calculado para seu objetivo de:
                    <strong><?php echo htmlspecialchars($objetivo_legivel, ENT_QUOTES, 'UTF-8'); ?></strong>
                </p>

                <?php if ($aviso_calorico): ?>
                    <p class="meta-description">A estimativa ficou abaixo de 1.200 kcal e foi limitada para evitar uma meta automática muito baixa. Esse limite não substitui avaliação profissional.</p>
                <?php endif; ?>

                <!-- MACROS SUGERIDOS -->
                <div class="macros-info">
                    <div class="macro-item">
                        <div>
                            <strong>Carboidratos</strong>
                            <span><?php echo number_format($auto_carbo); ?>g</span>
                        </div>
                    </div>
                    <div class="macro-item">
                        <div>
                            <strong>Proteínas</strong>
                            <span><?php echo number_format($auto_prot); ?>g</span>
                        </div>
                    </div>
                    <div class="macro-item">
                        <div>
                            <strong>Gorduras</strong>
                            <span><?php echo number_format($auto_gord); ?>g</span>
                        </div>
                    </div>
                </div>

                <!-- BOTÃO DE ACEITAR -->
                

                <!-- LINK PARA FORMULÁRIO MANUAL -->
                <a href="#" id="btnManual" class="btn-manual">Definir minhas próprias metas</a>

                <!-- ============================= -->
                <!-- SEÇÃO DE META MANUAL           -->
                <!-- ============================= -->
                <div id="sectionManual" style="display: none;" class="manual-section">

                    <form action="../controllers/metas_controller.php" method="POST" class="manual-form">
                        <input type="hidden" name="tipo_meta" value="manual">

                        <div class="fields-grid">
                            <!-- META CALÓRICA -->
                            <div class="field">
                                <label for="icalorias_man">Meta Calórica (kcal)</label>
                                <input type="number" id="icalorias_man" name="calorias" placeholder="Ex: 2200"
                                    min="1200" max="5000" required>
                            </div>

                            <!-- PROTEÍNAS -->
                            <div class="field">
                                <label for="iprot_man">Proteínas (g)</label>
                                <input type="number" id="iprot_man" name="proteina" placeholder="Ex: 150" min="0"
                                    max="500" required>
                            </div>

                            <!-- CARBOIDRATOS -->
                            <div class="field">
                                <label for="icarbo_man">Carboidratos (g)</label>
                                <input type="number" id="icarbo_man" name="carbo" placeholder="Ex: 200" min="0"
                                    max="500" required>
                            </div>

                            <!-- GORDURAS -->
                            <div class="field">
                                <label for="igord_man">Gorduras (g)</label>
                                <input type="number" id="igord_man" name="gordura" placeholder="Ex: 70" min="0"
                                    max="300" required>
                            </div>
                        </div>

                        <!-- BOTÃO ENVIAR -->
                        <button type="submit" class="submit-btn">
                            <span>Salvar Meta</span><b>→</b>
                        </button>
                    </form>

                </div>

            </div>
        </section>
    </main>

    <!-- ============================= -->
    <!-- SCRIPT                         -->
    <!-- ============================= -->
    <script>
        /* ---------- Tema claro / escuro ---------- */
        (function () {
            const raiz = document.documentElement;
            const botao = document.getElementById('themeToggle');

            function atualizarBotao() {
                const escuro = raiz.getAttribute('data-theme') === 'dark';
                const texto = escuro ? 'Mudar para o tema claro' : 'Mudar para o tema escuro';
                botao.setAttribute('aria-label', texto);
                botao.setAttribute('title', texto);
            }

            botao.addEventListener('click', function () {
                const novo = raiz.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                raiz.setAttribute('data-theme', novo);
                try { localStorage.setItem('nutria-theme', novo); } catch (e) {}
                atualizarBotao();
            });

            atualizarBotao();
        })();

        /* ---------- Menu hambúrguer ---------- */
        const header = document.querySelector('.main-header');
        const menuToggle = document.getElementById('menuToggle');
        const menuBackdrop = document.getElementById('menuBackdrop');

        function setMenu(aberto) {
            header.classList.toggle('open', aberto);
            menuBackdrop.classList.toggle('show', aberto);
            menuToggle.setAttribute('aria-expanded', String(aberto));
            menuToggle.setAttribute('aria-label', aberto ? 'Fechar menu' : 'Abrir menu');
        }

        menuToggle.addEventListener('click', () => setMenu(!header.classList.contains('open')));
        header.querySelectorAll('.nav-item').forEach(a => a.addEventListener('click', () => setMenu(false)));
        document.addEventListener('click', e => { if (!header.contains(e.target) && e.target !== menuToggle) setMenu(false); });
        document.addEventListener('keydown', e => { if (e.key === 'Escape') setMenu(false); });
        window.matchMedia('(min-width: 1025px)').addEventListener('change', e => { if (e.matches) setMenu(false); });
    </script>

    <script>
        const btnManual = document.getElementById('btnManual');
        const sectionManual = document.getElementById('sectionManual');

        btnManual.addEventListener('click', function (e) {
            e.preventDefault();
            if (sectionManual.style.display === 'block') {
                sectionManual.style.display = 'none';
            } else {
                sectionManual.style.display = 'block';
                setTimeout(() => {
                    sectionManual.scrollIntoView({behavior: 'smooth', block: 'start'});
                }, 100);
            }
        });
    </script>

</body>

</html>