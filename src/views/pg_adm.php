<?php
session_start();

// Verificação de identidade
if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo_usuario'] ?? '') !== 'admin') {
    // Se não estiver logado ou não for admin, redireciona para o login
    header("Location: login.php?erro=acesso_negado");
    exit;
}

// Pega os dados do admin salvos na sessão
$emailAdmin = $_SESSION['usuario_email'] ?? 'suportenutria18@gmail.com';
$letraInicial = strtoupper(substr($emailAdmin, 0, 1));
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NutrIA ADM</title>
    <script>
        /* Tema: claro por padrão; */
        (function () {
            var tema = 'light';
            try { if (localStorage.getItem('nutria-theme') === 'dark') tema = 'dark'; } catch (e) {}
            document.documentElement.setAttribute('data-theme', tema);
        })();
    </script>
    <link rel="stylesheet" href="../../public/css/pg_adm_style.css">
    <link rel="icon" type="image/svg+xml" href="../../public/img/logo.svg">
    <!-- Fonte -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- GOOGLE MATERIAL SYMBOLS -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
</head>

<body>
    <button type="button" class="theme-toggle" id="themeToggle"
            aria-label="Mudar para o tema escuro" title="Mudar para o tema escuro">
        <svg class="icon-moon" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12.8A9 9 0 1111.2 3a7 7 0 009.8 9.8z"/></svg>
        <svg class="icon-sun" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
    </button>

    <main class="container">
        <section class="hero">
            <div>
                <span class="eyebrow">CENTRO DE INTELIGÊNCIA NUTRICIONAL</span>
                <h2>O que você deseja<br><span>analisar?</span></h2>
            </div>
        </section>

        <!-- Cards do adm-->
        <section class="cards-grid">
            <!-- USUÁRIOS -->
            <a href="adm/usuarios_adm.php" class="admin-card">
                <div class="card-top">
                    <div class="card-icon purple">
                        <span class="material-symbols-outlined">group</span>
                    </div>
                </div>
                <div class="card-content">
                    <h3>Usuários</h3>
                    <p>
                        Consulte e analise os usuários
                        do NutrIA.
                    </p>
                </div>
            </a>

            <!-- REFEIÇÕES -->
            <a href="adm/refeicoes_adm.php" class="admin-card">
                <div class="card-top">
                    <div class="card-icon orange">
                        <span class="material-symbols-outlined">flatware</span>
                    </div>
                </div>
                <div class="card-content">
                    <h3>Refeições</h3>
                    <p>
                        Consulte as refeições analisadas
                        pela IA.
                    </p>
                </div>
            </a>

            <!-- SAIR -->
            <a href="../controllers/logout_controller.php" class="admin-card small-card">
                <div class="card-top">
                    <div class="card-icon red">
                        <span class="material-symbols-outlined">logout</span>
                    </div>
                </div>
                <div class="card-content">
                    <h3>Sair do sistema</h3>
                    <p>
                        Voltar a tela inicial
                    </p>
                </div>
            </a>
        </section>
    </main>

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
    </script>
</body>

</html>