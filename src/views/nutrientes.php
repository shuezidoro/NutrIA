<?php
session_start();

// Bloqueia acessos de usuários deslogados (igual à home)
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$usuario_nome = $_SESSION['usuario_nome'] ?? 'Usuário';

// ==========================================
// CONTEÚDO DO GUIA (edite os textos aqui)
// ==========================================
$nutrientes = [
    [
        'id' => 'kcal', 'icone' => 'local_fire_department', 'nome' => 'Calorias (Kcal)',
        'resumo' => 'A energia que os alimentos fornecem ao corpo.',
        'oque' => 'Kcal (quilocaloria) é a unidade que mede quanta energia um alimento libera quando é utilizado pelo organismo. Ela vem dos macronutrientes: cada grama de carboidrato e de proteína rende cerca de 4 kcal, e cada grama de gordura rende cerca de 9 kcal.',
        'funcao' => [
            'Mantém funções vitais em repouso: respiração, batimentos, temperatura e funcionamento do cérebro.',
            'Dá energia para movimento, trabalho e exercício.',
            'Se você come mais do que gasta, o excesso é guardado (ganho de peso); se come menos, o corpo usa as reservas.',
        ],
        'titulo_fontes' => 'Boas fontes de energia (com nutrientes junto)',
        'fontes' => ['Aveia', 'Batata-doce', 'Arroz integral', 'Feijão', 'Ovos', 'Abacate', 'Castanhas', 'Azeite de oliva', 'Banana'],
        'dica' => 'Mais importante que contar só kcal é a qualidade: 200 kcal de castanhas nutrem muito mais que 200 kcal de refrigerante.',
    ],
    [
        'id' => 'proteina', 'icone' => 'fitness_center', 'nome' => 'Proteínas',
        'resumo' => 'Os "tijolos" que constroem e reparam o corpo.',
        'oque' => 'Proteínas são formadas por aminoácidos. Alguns deles o corpo não produz (os essenciais) e precisam vir da alimentação.',
        'funcao' => [
            'Constroem e recuperam músculos, pele, cabelo e unhas.',
            'Formam enzimas, hormônios e anticorpos (defesa do organismo).',
            'Dão saciedade, ajudando a controlar a fome.',
        ],
        'titulo_fontes' => 'Principais fontes (mais saudáveis)',
        'fontes' => ['Ovos', 'Peito de frango', 'Peixes (sardinha, salmão, atum)', 'Feijão', 'Lentilha', 'Grão-de-bico', 'Iogurte natural', 'Leite', 'Tofu'],
        'dica' => 'Combinar arroz com feijão fornece uma proteína vegetal bem completa em aminoácidos.',
    ],
    [
        'id' => 'carboidrato', 'icone' => 'bakery_dining', 'nome' => 'Carboidratos',
        'resumo' => 'A principal fonte de energia do corpo.',
        'oque' => 'São açúcares e amidos que o corpo transforma em glicose, o "combustível" preferido do cérebro e dos músculos. Os complexos (integrais) liberam energia aos poucos; os simples liberam rápido.',
        'funcao' => [
            'Fornecem energia para o cérebro, músculos e atividades do dia a dia.',
            'Poupam as proteínas, que assim podem se dedicar a construir tecidos.',
            'Os integrais, ricos em fibra, mantêm a glicemia mais estável.',
        ],
        'titulo_fontes' => 'Principais fontes (mais saudáveis)',
        'fontes' => ['Aveia', 'Arroz integral', 'Batata-doce', 'Mandioca', 'Quinoa', 'Pão integral', 'Feijão e lentilha', 'Frutas', 'Milho'],
        'dica' => 'Prefira versões integrais e pouco processadas: elas trazem fibras, vitaminas e minerais.',
    ],
    [
        'id' => 'gordura', 'icone' => 'water_drop', 'nome' => 'Gorduras',
        'resumo' => 'Energia concentrada e proteção para o organismo.',
        'oque' => 'As gorduras (lipídios) são o nutriente mais calórico. Existem as insaturadas (mono e poli, as "boas"), as saturadas (consumir com moderação) e as trans (devem ser evitadas).',
        'funcao' => [
            'Reserva de energia e proteção dos órgãos.',
            'Ajudam a absorver as vitaminas A, D, E e K.',
            'Participam da produção de hormônios e das membranas das células.',
            'O ômega-3 contribui para a saúde do coração e do cérebro.',
        ],
        'titulo_fontes' => 'Principais fontes (mais saudáveis)',
        'fontes' => ['Azeite de oliva', 'Abacate', 'Castanhas e nozes', 'Amêndoas', 'Chia e linhaça', 'Peixes gordurosos (salmão, sardinha)', 'Amendoim'],
        'dica' => 'Evite gorduras trans, comuns em frituras de fast-food, biscoitos recheados e alguns salgadinhos.',
    ],
    [
        'id' => 'sodio', 'icone' => 'grain', 'nome' => 'Sódio',
        'resumo' => 'Mineral essencial, mas perigoso em excesso.',
        'oque' => 'O sódio é um mineral presente no sal de cozinha (cerca de 40% do sal é sódio) e em muitos alimentos industrializados. A OMS recomenda consumir menos de 2 g de sódio por dia, o equivalente a cerca de 5 g de sal.',
        'funcao' => [
            'Equilibra a quantidade de líquidos no corpo.',
            'Participa da contração dos músculos e da transmissão dos impulsos nervosos.',
            'Em excesso, aumenta a pressão arterial e sobrecarrega o coração e os rins.',
        ],
        'titulo_fontes' => 'Como consumir de forma saudável',
        'fontes' => ['Alimentos in natura (ovos, leite, carnes frescas)', 'Beterraba', 'Aipo', 'Temperos naturais (alho, cebola, limão, ervas)'],
        'dica' => 'A maior parte do sódio escondido vem de embutidos, enlatados, temperos prontos, macarrão instantâneo e salgadinhos. Leia o rótulo!',
    ],
    [
        'id' => 'acucar', 'icone' => 'cookie', 'nome' => 'Açúcar',
        'resumo' => 'Carboidrato simples de energia rápida.',
        'oque' => 'O açúcar é um carboidrato simples. Existe o açúcar natural (das frutas e do leite) e o açúcar adicionado (refrigerantes, doces, biscoitos). A OMS recomenda que os açúcares livres sejam menos de 10% das kcal do dia, e idealmente menos de 5%.',
        'funcao' => [
            'Fornece energia rápida, pois é absorvido facilmente.',
            'Em excesso, eleva a glicemia, favorece o ganho de peso e cáries e aumenta o risco de diabetes tipo 2.',
            'O açúcar das frutas vem junto com fibras, o que torna a absorção mais lenta.',
        ],
        'titulo_fontes' => 'Fontes mais saudáveis de açúcar natural',
        'fontes' => ['Frutas frescas (maçã, laranja, mamão, banana)', 'Iogurte natural (lactose)', 'Leite', 'Tâmaras', 'Batata-doce'],
        'dica' => 'Prefira a fruta inteira ao suco: ela mantém a fibra e sacia mais.',
    ],
    [
        'id' => 'fibra', 'icone' => 'grass', 'nome' => 'Fibras',
        'resumo' => 'Aliadas do intestino e da saciedade.',
        'oque' => 'Fibras são partes dos vegetais que o corpo não digere. As solúveis formam um gel e as insolúveis ajudam no trânsito intestinal. A recomendação geral para adultos é de cerca de 25 a 30 g por dia.',
        'funcao' => [
            'Melhoram o funcionamento do intestino e previnem a constipação.',
            'Aumentam a saciedade, ajudando no controle de peso.',
            'Ajudam a reduzir o colesterol e a controlar a glicemia.',
            'Alimentam as bactérias boas da flora intestinal.',
        ],
        'titulo_fontes' => 'Principais fontes (mais saudáveis)',
        'fontes' => ['Aveia', 'Feijão', 'Lentilha', 'Grão-de-bico', 'Chia e linhaça', 'Frutas com casca', 'Brócolis e folhas verdes', 'Pão e arroz integrais'],
        'dica' => 'Aumente a fibra aos poucos e beba bastante água para evitar desconforto intestinal.',
    ],
];
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script>
        (function () {
            var tema = 'light';
            try { if (localStorage.getItem('nutria-theme') === 'dark') tema = 'dark'; } catch (e) {}
            document.documentElement.setAttribute('data-theme', tema);
        })();
    </script>
    <link rel="stylesheet" href="../../public/css/home_style.css">
    <link rel="stylesheet" href="../../public/css/nutrientes_style.css">
    <link rel="icon" type="image/svg+xml" href="../../public/img/logo.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- GOOGLE MATERIAL SYMBOLS -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />

    <title>Guia de Nutrientes - NutrIA</title>
</head>

<body>

    <div class="background-glow glow-1"></div>
    <div class="background-glow glow-2"></div>

    <div class="menu-backdrop" id="menuBackdrop"></div>

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
                    <path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
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
            <a href="metas.php" class="nav-item">
                <svg viewBox="0 0 24 24">
                    <path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                Metas
            </a>
            <a href="nutrientes.php" class="nav-item active">
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
                <svg class="icon-moon" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M21 12.8A9 9 0 1111.2 3a7 7 0 009.8 9.8z" />
                </svg>
                <svg class="icon-sun" viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="12" cy="12" r="4" />
                    <path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4" />
                </svg>
            </button>
            <a href="../controllers/logout_controller.php" class="btn-logout">Sair</a>
        </div>
    </header>

    <div class="container">
        <section class="guia-topo">
            <h1>Guia de Nutrientes</h1>
            <p>Entenda o que cada número do seu dia significa, o que ele faz no seu corpo e quais alimentos são as melhores fontes.</p>
        </section>

        <nav class="guia-indice" aria-label="Atalhos">
            <?php foreach ($nutrientes as $n): ?>
                <a href="#<?php echo $n['id']; ?>">
                    <span class="material-symbols-outlined"><?php echo $n['icone']; ?></span>
                    <?php echo htmlspecialchars($n['nome']); ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <section class="guia-energia" aria-label="Energia por grama">
            <div><strong>4 kcal</strong><small>1 g de carboidrato</small></div>
            <div><strong>4 kcal</strong><small>1 g de proteína</small></div>
            <div><strong>9 kcal</strong><small>1 g de gordura</small></div>
        </section>

        <section class="guia-grid">
            <?php foreach ($nutrientes as $n): ?>
                <article class="card guia-card" id="<?php echo $n['id']; ?>">
                    <div class="guia-card-topo">
                        <span class="guia-icone"><span class="material-symbols-outlined"><?php echo $n['icone']; ?></span></span>
                        <div>
                            <h2><?php echo htmlspecialchars($n['nome']); ?></h2>
                            <p class="guia-resumo"><?php echo htmlspecialchars($n['resumo']); ?></p>
                        </div>
                    </div>

                    <h3>O que é</h3>
                    <p><?php echo htmlspecialchars($n['oque']); ?></p>

                    <h3>O que faz no corpo</h3>
                    <ul>
                        <?php foreach ($n['funcao'] as $f): ?>
                            <li><?php echo htmlspecialchars($f); ?></li>
                        <?php endforeach; ?>
                    </ul>

                    <h3><?php echo htmlspecialchars($n['titulo_fontes']); ?></h3>
                    <div class="guia-chips">
                        <?php foreach ($n['fontes'] as $f): ?>
                            <span><?php echo htmlspecialchars($f); ?></span>
                        <?php endforeach; ?>
                    </div>

                    <p class="guia-dica">
                        <span class="material-symbols-outlined">lightbulb</span>
                        <span><?php echo htmlspecialchars($n['dica']); ?></span>
                    </p>
                </article>
            <?php endforeach; ?>
        </section>

        <p class="guia-aviso">Conteúdo educativo. Para um plano alimentar individual, consulte um nutricionista.</p>
    </div>

    <script>
        // Botão de tema claro/escuro
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

        // Menu mobile
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

        // Animação de entrada dos cards
        const obs = new IntersectionObserver((itens) => {
            itens.forEach(i => { if (i.isIntersecting) { i.target.classList.add('visivel'); obs.unobserve(i.target); } });
        }, { threshold: 0.1 });
        document.querySelectorAll('.guia-card').forEach(c => obs.observe(c));
    </script>
</body>

</html>