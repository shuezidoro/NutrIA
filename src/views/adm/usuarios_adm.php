<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../login.php");
    exit;
}

if (
    !isset($_SESSION['tipo_usuario']) ||
    $_SESSION['tipo_usuario'] !== 'admin'
) {
    http_response_code(403);
    exit("Acesso negado.");
}

require_once __DIR__ . "/../../config/conexao.php";

try {
    $sql = "
    SELECT
        u.id,
        u.nome,
        u.email,
        u.tipo_usuario,
        u.data_cadastro,

        b.peso,
        b.altura,
        b.idade,
        b.genero,
        b.nivel_atividade,
        b.objetivo

    FROM usuarios u

    LEFT JOIN biometria b
        ON b.usuario_id = u.id

    ORDER BY u.id ASC
";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao carregar os usuários: " . htmlspecialchars($e->getMessage()));
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NutrIA — Usuários</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        (function () {
            var tema = 'light';
            try {if (localStorage.getItem('nutria-theme') === 'dark') tema = 'dark';} catch (e) { }
            document.documentElement.setAttribute('data-theme', tema);
        })();
    </script>
    <link rel="stylesheet" href="../../../public/css/usuarios_adm_style.css">
    <link rel="icon" type="image/svg+xml" href="../../../public/img/logo.svg">

    <style>
        .users-filter-controls {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .users-filters {
            display: grid;
            grid-template-columns: minmax(210px, 1.2fr) repeat(2, minmax(170px, 1fr)) auto;
            gap: 12px;
            align-items: end;
            width: 100%;
        }

        .filter-field {
            display: flex;
            flex-direction: column;
            gap: 7px;
            min-width: 0;
        }

        .filter-field>label {
            font-size: .78rem;
            font-weight: 700;
            letter-spacing: .04em;
            color: var(--text-secondary, inherit);
        }

        .filter-field input,
        .filter-field select {
            box-sizing: border-box;
            width: 100%;
            min-width: 0;
            min-height: 42px;
            padding: 10px 12px;
            border: 1px solid var(--border-color, rgba(128, 128, 128, .35));
            border-radius: 10px;
            background: var(--input-bg, var(--card-bg, transparent));
            color: var(--text-primary, inherit);
            font: inherit;
            outline: none;
        }

        .filter-field input:focus,
        .filter-field select:focus {
            border-color: var(--accent-color, #16a34a);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent-color, #16a34a) 16%, transparent);
        }

        .filter-field select option {
            color: #202020;
            background: #fff;
        }

        .age-range {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
            align-items: center;
            gap: 7px;
        }

        .age-range span {
            color: var(--text-secondary, inherit);
            font-size: .8rem;
        }

        .clear-filters {
            min-height: 42px;
            padding: 10px 15px;
            border: 1px solid var(--border-color, rgba(128, 128, 128, .4));
            border-radius: 10px;
            background: transparent;
            color: var(--text-primary, inherit);
            font: inherit;
            font-size: .85rem;
            font-weight: 700;
            cursor: pointer;
            white-space: nowrap;
            transition: border-color .2s ease, background .2s ease;
        }

        .clear-filters:hover {
            border-color: var(--accent-color, #16a34a);
            background: color-mix(in srgb, var(--accent-color, #16a34a) 9%, transparent);
        }

        .total-users small {
            display: block;
            margin-top: 3px;
            opacity: .7;
            font-size: .72rem;
        }

        @media (max-width:900px) {
            .users-filters {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width:520px) {
            .users-filters {
                grid-template-columns: 1fr;
            }

            .clear-filters {
                width: 100%;
            }
        }
    </style>

</head>

<body>
    <button type="button" class="theme-toggle" id="themeToggle" aria-label="Mudar para o tema escuro"
        title="Mudar para o tema escuro">
        <svg class="icon-moon" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M21 12.8A9 9 0 1111.2 3a7 7 0 009.8 9.8z" />
        </svg>
        <svg class="icon-sun" viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="12" cy="12" r="4" />
            <path
                d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4" />
        </svg>
    </button>

    <main class="container">
        <!-- VOLTAR -->
        <a href="../pg_adm.php" class="back-link">← Voltar para o painel</a>
        <section class="page-header">
            <div>
                <span class="eyebrow">GERENCIAMENTO</span>
                <h2>Usuários</h2>
                <p>
                    Todos os usuários cadastrados no NutrIA.
                </p>
            </div>

            <div class="total-users" aria-live="polite">
                <strong id="usersCount"><?= count($usuarios) ?></strong>
                <span>usuários encontrados</span>
                <small>de <?= count($usuarios) ?> cadastrados</small>
            </div>
        </section>

        <section class="users-panel">
            <div class="users-controls users-filter-controls">
                <div class="search-box">
                    <input type="text" id="searchUsers" placeholder="Pesquisar por nome..." autocomplete="off"
                        aria-label="Pesquisar usuário por nome">
                </div>

                <div class="users-filters" aria-label="Filtros de usuários">
                    <div class="filter-field filter-age">
                        <label for="filterAgeMin">Idade</label>
                        <div class="age-range">
                            <input type="number" id="filterAgeMin" min="15" max="120" placeholder="Mín."
                                aria-label="Idade mínima">
                            <span>até</span>
                            <input type="number" id="filterAgeMax" min="15" max="120" placeholder="Máx."
                                aria-label="Idade máxima">
                        </div>
                    </div>

                    <div class="filter-field">
                        <label for="filterGender">Sexo</label>
                        <select id="filterGender">
                            <option value="">Todos</option>
                            <option value="Masculino">Masculino</option>
                            <option value="Feminino">Feminino</option>
                        </select>
                    </div>

                    <div class="filter-field">
                        <label for="filterObjective">Objetivo</label>
                        <select id="filterObjective">
                            <option value="">Todos</option>
                            <option value="Perda_Peso">Perder peso</option>
                            <option value="Manter_Saude">Manter saúde</option>
                            <option value="Ganho_Massa">Ganhar massa</option>
                        </select>
                    </div>

                    <button type="button" id="clearUserFilters" class="clear-filters">
                        Limpar filtros
                    </button>
                </div>
            </div>

            <div class="table-wrapper">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>USUÁRIO</th>
                            <th>E-MAIL</th>
                            <th>TIPO</th>
                            <th>AÇÕES</th>
                        </tr>
                    </thead>

                    <tbody id="usersTableBody">
                        <?php if (empty($usuarios)): ?>
                            <tr>
                                <td colspan="5" class="empty-table"> Nenhum usuário cadastrado.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($usuarios as $usuario): ?>
                                <?php
                                $tipoBanco =
                                    strtolower(
                                        trim(
                                            $usuario['tipo_usuario'] ?? 'usuario'
                                        )
                                    );

                                $ehAdmin =
                                    $tipoBanco === 'admin'
                                    ?>

                                <tr data-type="<?= $ehAdmin ? 'admin' : 'usuario' ?>"
                                    data-name="<?= htmlspecialchars((string) ($usuario['nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-age="<?= htmlspecialchars((string) ($usuario['idade'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-gender="<?= htmlspecialchars((string) ($usuario['genero'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-objective="<?= htmlspecialchars((string) ($usuario['objetivo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    <!-- ID -->
                                    <td>
                                        <span class="user-id">
                                            #<?= htmlspecialchars(
                                                $usuario['id']
                                            ) ?>
                                        </span>
                                    </td>

                                    <!-- USUÁRIO -->
                                    <td>
                                        <div class="user-cell">

                                            <div>
                                                <strong>
                                                    <?= htmlspecialchars(
                                                        $usuario['nome']
                                                    ) ?>
                                                </strong>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- E-MAIL -->
                                    <td>
                                        <span class="user-email">
                                            <?= htmlspecialchars(
                                                $usuario['email']
                                            ) ?>
                                        </span>
                                    </td>

                                    <!-- TIPO -->
                                    <td>
                                        <?php if ($ehAdmin): ?>
                                            <span class="status adm">
                                                <i></i>
                                                Administrador
                                            </span>
                                        <?php else: ?>
                                            <span class="status active">
                                                <i></i>
                                                Usuário
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- AÇÕES -->
                                    <td>
                                        <button type="button" class="view-button btn-ver-perfil"
                                            data-id="<?= htmlspecialchars($usuario['id']) ?>"
                                            data-nome="<?= htmlspecialchars($usuario['nome']) ?>"
                                            data-email="<?= htmlspecialchars($usuario['email']) ?>"
                                            data-peso="<?= htmlspecialchars($usuario['peso'] ?? '') ?>"
                                            data-altura="<?= htmlspecialchars($usuario['altura'] ?? '') ?>"
                                            data-idade="<?= htmlspecialchars($usuario['idade'] ?? '') ?>"
                                            data-genero="<?= htmlspecialchars($usuario['genero'] ?? '') ?>"
                                            data-atividade="<?= htmlspecialchars($usuario['nivel_atividade'] ?? '') ?>"
                                            data-data-cadastro="<?= htmlspecialchars($usuario['data_cadastro'] ?? '') ?>"
                                            data-objetivo="<?= htmlspecialchars($usuario['objetivo'] ?? '') ?>">Ver
                                            perfil<span>→</span>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- SEM RESULTADOS NA PESQUISA -->
            <div id="noSearchResults" class="no-search-results" role="status" aria-live="polite">
                Nenhum usuário corresponde aos filtros selecionados.
            </div>
        </section>
    </main>

    <!-- MODAL PERFIL DO USUÁRIO-->

    <div class="profile-modal-overlay" id="profileModal">
        <div class="profile-modal">
            <div class="profile-modal-header">
                <div>
                    <span class="profile-modal-eyebrow">PERFIL DO USUÁRIO</span>
                    <h3>Informações do usuário</h3>
                </div>
                <button type="button" class="modal-close" id="closeProfileModal">×</button>
            </div>

            <div class="profile-identity">

                <div>
                    <h4 id="modalNome">-</h4>
                    <p id="modalEmail">-</p>
                </div>
            </div>

            <div class="profile-section">
                <div class="profile-section-title">INFORMAÇÕES BÁSICAS</div>
                <div class="profile-info-grid">
                    <div class="profile-info-card">
                        <span>ID</span>
                        <strong id="modalId">-</strong>
                    </div>

                    <div class="profile-info-card">
                        <span>E-MAIL</span>
                        <strong id="modalEmailInfo">-</strong>
                    </div>

                    <div class="profile-info-card">
                        <span>DATA DE CADASTRO</span>
                        <strong id="modalDataCadastro">—</strong>
                    </div>

                    <div class="profile-info-card">
                        <span>TIPO DE CONTA</span>
                        <strong>Usuário</strong>
                    </div>
                </div>
            </div>

            <!-- BIOMETRIA -->
            <div class="profile-section">
                <div class="profile-section-title">
                    BIOMETRIA
                </div>

                <div class="biometric-grid">
                    <!-- PESO -->
                    <div class="biometric-card">
                        <div>
                            <small>PESO</small>
                            <strong id="modalPeso">—</strong>
                        </div>
                    </div>

                    <!-- ALTURA -->
                    <div class="biometric-card">
                        <div>
                            <small>ALTURA</small>
                            <strong id="modalAltura">—</strong>
                        </div>
                    </div>

                    <!-- IDADE -->
                    <div class="biometric-card">
                        <div>
                            <small>IDADE</small>
                            <strong id="modalIdade">—</strong>
                        </div>
                    </div>

                    <!-- IMC -->
                    <div class="biometric-card">
                        <div>
                            <small>IMC</small>
                            <strong id="modalImc">—</strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- OBJETIVO -->
            <div class="profile-section">
                <div class="profile-section-title">
                    OBJETIVO
                </div>
                <div class="objective-box">
                    <div>
                        <small>
                            OBJETIVO NUTRICIONAL
                        </small>
                        <strong id="modalObjetivo">—</strong>
                    </div>
                </div>
            </div>

        </div>
    </div>


    <!-- JAVASCRIPT -->

    <script>
        const searchInput = document.getElementById("searchUsers");
        const tableBody = document.getElementById("usersTableBody");
        const noResults = document.getElementById("noSearchResults");
        const ageMinInput = document.getElementById("filterAgeMin");
        const ageMaxInput = document.getElementById("filterAgeMax");
        const genderFilter = document.getElementById("filterGender");
        const objectiveFilter = document.getElementById("filterObjective");
        const clearFiltersButton = document.getElementById("clearUserFilters");
        const usersCount = document.getElementById("usersCount");
        const totalUsers = tableBody.querySelectorAll("tr[data-type]").length;

        // PESQUISA + FILTROS COMBINADOS

        function normalizeText(value) {
            return String(value ?? "")
                .toLocaleLowerCase("pt-BR")
                .normalize("NFD")
                .replace(/[\u0300-\u036f]/g, "")
                .trim();
        }

        function filterUsers() {
            const search = normalizeText(searchInput.value);
            const minAge = ageMinInput.value.trim() === "" ? null : Number(ageMinInput.value);
            const maxAge = ageMaxInput.value.trim() === "" ? null : Number(ageMaxInput.value);
            const selectedGender = normalizeText(genderFilter.value);
            const selectedObjective = normalizeText(objectiveFilter.value);
            const rows = tableBody.querySelectorAll("tr[data-type]");
            let visibleRows = 0;

            rows.forEach(row => {
                const rowName = normalizeText(row.dataset.name);
                const ageText = row.dataset.age ?? "";
                const rowAge = ageText.trim() === "" ? NaN : Number(ageText);
                const rowGender = normalizeText(row.dataset.gender);
                const rowObjective = normalizeText(row.dataset.objective);

                const matchesSearch = rowName.includes(search);
                const matchesMinAge = minAge === null || (Number.isFinite(rowAge) && rowAge >= minAge);
                const matchesMaxAge = maxAge === null || (Number.isFinite(rowAge) && rowAge <= maxAge);
                const matchesGender = selectedGender === "" || rowGender === selectedGender;
                const matchesObjective = selectedObjective === "" || rowObjective === selectedObjective;

                const visible = matchesSearch && matchesMinAge && matchesMaxAge &&
                    matchesGender && matchesObjective;

                row.style.display = visible ? "" : "none";
                if (visible) visibleRows++;
            });

            usersCount.textContent = visibleRows;
            noResults.style.display = totalUsers > 0 && visibleRows === 0 ? "block" : "none";
        }

        searchInput.addEventListener("input", filterUsers);
        ageMinInput.addEventListener("input", filterUsers);
        ageMaxInput.addEventListener("input", filterUsers);
        genderFilter.addEventListener("change", filterUsers);
        objectiveFilter.addEventListener("change", filterUsers);

        clearFiltersButton.addEventListener("click", function () {
            searchInput.value = "";
            ageMinInput.value = "";
            ageMaxInput.value = "";
            genderFilter.value = "";
            objectiveFilter.value = "";
            filterUsers();
        });

        filterUsers();

        // MODAL

        const profileModal = document.getElementById("profileModal");
        const closeProfileModal = document.getElementById("closeProfileModal");
        const modalId = document.getElementById("modalId");
        const modalNome = document.getElementById("modalNome");
        const modalEmail = document.getElementById("modalEmail");
        const modalEmailInfo = document.getElementById("modalEmailInfo");
        const modalDataCadastro = document.getElementById("modalDataCadastro");

        // ABRIR

        document
            .querySelectorAll(".btn-ver-perfil")
            .forEach(button => {

                button.addEventListener("click", function () {

                    const id = this.dataset.id;
                    const nome = this.dataset.nome;
                    const email = this.dataset.email;
                    const peso = this.dataset.peso;
                    const altura = this.dataset.altura;
                    const idade = this.dataset.idade;
                    const genero = this.dataset.genero;
                    const atividade = this.dataset.atividade;
                    const objetivo = this.dataset.objetivo;
                    const dataCadastro = this.dataset.dataCadastro;

                    // DADOS BÁSICOS

                    modalId.textContent = "#" + id;
                    modalNome.textContent = nome;
                    modalEmail.textContent = email;
                    modalEmailInfo.textContent = email;

                    //  DATA DER CADASTRO

                    if (dataCadastro) {

                        const data = new Date(
                            dataCadastro.replace(" ", "T")
                        );

                        modalDataCadastro.textContent =
                            data.toLocaleDateString("pt-BR") +
                            " às " +
                            data.toLocaleTimeString("pt-BR", {
                                hour: "2-digit",
                                minute: "2-digit"
                            });

                    } else {

                        modalDataCadastro.textContent = "—";

                    }

                    // BIOMETRIA

                    document.getElementById("modalPeso")
                        .textContent =
                        peso
                            ? Number(peso).toFixed(1) + " kg"
                            : "—";

                    document.getElementById("modalAltura")
                        .textContent =
                        altura
                            ? Number(altura).toFixed(2) + " m"
                            : "—";

                    document.getElementById("modalIdade")
                        .textContent =
                        idade
                            ? idade + " anos"
                            : "—";

                    // IMC

                    if (peso && altura) {

                        const imc = Number(peso) / (Number(altura) * Number(altura));

                        document.getElementById("modalImc").textContent = imc.toFixed(1);
                    } else {
                        document.getElementById("modalImc").textContent = "—";
                    }

                    // OBJETIVO

                    let objetivoTexto = "—";
                    if (objetivo === "Perda_Peso") {
                        objetivoTexto = "Perder peso";

                    } else if (objetivo === "Manter_Saude") {
                        objetivoTexto = "Manter saúde";

                    } else if (objetivo === "Ganho_Massa") {
                        objetivoTexto = "Ganhar massa muscular";

                    } else if (objetivo) {
                        objetivoTexto = objetivo;

                    }

                    document.getElementById("modalObjetivo").textContent = objetivoTexto;

                    // ABRIR MODAL
                    profileModal.classList.add("active");
                    document.body.classList.add("modal-open");
                });
            });


        // FECHAR

        function fecharModal() {
            profileModal.classList.remove("active");
            document.body.classList.remove("modal-open");
        }
        closeProfileModal.addEventListener("click", fecharModal);

        // CLICAR FORA

        profileModal.addEventListener(
            "click",
            function (event) {
                if (event.target === profileModal) {
                    fecharModal();
                }
            }
        );

        // ESC

        document.addEventListener(
            "keydown",
            function (event) {

                if (event.key === "Escape" && profileModal.classList.contains("active")) {
                    fecharModal();
                }
            }
        );

    </script>

    <script>
        // TEMA CLARO / ESCURO
        (function () {
            const raiz = document.documentElement;
            const botao = document.getElementById("themeToggle");

            function atualizarBotao() {
                const escuro = raiz.getAttribute("data-theme") === "dark";
                const texto = escuro ? "Mudar para o tema claro" : "Mudar para o tema escuro";
                botao.setAttribute("aria-label", texto);
                botao.setAttribute("title", texto);
            }

            botao.addEventListener("click", function () {
                const novo = raiz.getAttribute("data-theme") === "dark" ? "light" : "dark";
                raiz.setAttribute("data-theme", novo);
                try {localStorage.setItem("nutria-theme", novo);} catch (e) { }
                atualizarBotao();
            });

            atualizarBotao();
        })();
    </script>
</body>

</html>