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
            r.id AS refeicao_id,
            r.usuario_id,
            u.nome AS usuario_nome,
            u.email AS usuario_email,

            r.descricao_texto,
            r.tipo_refeicao,
            r.data_consumo,
            r.criado_em,

            n.calorias_obtidas,
            n.proteina_obtida,
            n.carbo_obtido,
            n.gordura_obtida,
            n.acucar_obtido,
            n.fibra_obtida,
            n.sodio_obtido

        FROM refeicao r

        INNER JOIN usuarios u
            ON u.id = r.usuario_id

        LEFT JOIN nutriente n
            ON n.refeicao_id = r.id

        ORDER BY r.data_consumo DESC, r.id DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    $refeicoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    die(
        "Erro ao carregar as refeições: " .
        htmlspecialchars($e->getMessage())
    );

}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NutrIA — Refeições</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />

    <script>
        (function () {
            var tema = 'light';
            try {if (localStorage.getItem('nutria-theme') === 'dark') tema = 'dark';} catch (e) { }
            document.documentElement.setAttribute('data-theme', tema);
        })();
    </script>
    <link rel="stylesheet" href="../../../public/css/refeicoes_adm_style.css">
    <link rel="icon" type="image/svg+xml" href="../../../public/img/logo.svg">

    <style>
        .users-filter-controls {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .users-filters {
            display: grid;
            grid-template-columns: minmax(260px, 1.2fr) minmax(180px, 1fr) auto;
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

        .date-range {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
            align-items: center;
            gap: 7px;
        }

        .date-range span {
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
                grid-template-columns: 1fr;
            }
        }

        @media (max-width:520px) {
            .clear-filters {
                width: 100%;
            }
        }
    </style>
</head>

<body>

    <!-- BOTÃO DE TEMA -->
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
                <h2>Refeições</h2>
                <p>
                    Todas as refeições cadastradas no NutrIA.
                </p>
            </div>

            <div class="total-users" aria-live="polite">
                <strong id="refeicoesCount"><?= count($refeicoes) ?></strong>
                <span>refeições encontradas</span>
                <small>de <?= count($refeicoes) ?> cadastradas</small>
            </div>
        </section>

        <section class="users-panel">
            <!-- CONTROLES DE FILTRO -->
            <div class="users-controls users-filter-controls">
                <div class="search-box">
                    <input type="text" id="searchRefeicoes" placeholder="Pesquisar por nome de usuário ou e-mail..."
                        autocomplete="off" aria-label="Pesquisar por nome de usuário ou e-mail">
                </div>

                <div class="users-filters" aria-label="Filtros de refeições">
                    <div class="filter-field">
                        <label for="filterDateStart">Período de Data</label>
                        <div class="date-range">
                            <input type="date" id="filterDateStart" aria-label="Data inicial">
                            <span>até</span>
                            <input type="date" id="filterDateEnd" aria-label="Data final">
                        </div>
                    </div>

                    <div class="filter-field">
                        <label for="filterTipo">Tipo de Refeição</label>
                        <select id="filterTipo">
                            <option value="">Todos os tipos</option>
                            <option value="Café da Manhã">Café da Manhã</option>
                            <option value="Almoço">Almoço</option>
                            <option value="Café da Tarde">Café da Tarde</option>
                            <option value="Jantar">Jantar</option>
                            <option value="Lanche">Lanche</option>
                        </select>
                    </div>

                    <button type="button" id="clearRefeicaoFilters" class="clear-filters">
                        Limpar filtros
                    </button>
                </div>
            </div>

            <div class="table-wrapper">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>DATA</th>
                            <th>USUÁRIO</th>
                            <th>TIPO</th>
                            <th>REFEIÇÃO</th>
                            <th>AÇÕES</th>
                        </tr>
                    </thead>

                    <tbody id="refeicoesTableBody">
                        <?php if (empty($refeicoes)): ?>
                            <tr>
                                <td colspan="5" class="empty-table">Nenhuma refeição cadastrada.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($refeicoes as $refeicao): ?>
                                <?php
                                $dataIso = !empty($refeicao['data_consumo']) ? date('Y-m-d', strtotime($refeicao['data_consumo'])) : '';
                                ?>
                                <tr data-user="<?= htmlspecialchars((string) ($refeicao['usuario_nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-email="<?= htmlspecialchars((string) ($refeicao['usuario_email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-tipo="<?= htmlspecialchars((string) ($refeicao['tipo_refeicao'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-date="<?= $dataIso ?>">
                                    <!-- DATA -->
                                    <td>
                                        <?= date('d/m/Y', strtotime($refeicao['data_consumo'])) ?>
                                    </td>

                                    <!-- USUÁRIO -->
                                    <td>
                                        <div class="user-cell">
                                            <div>
                                                <strong>
                                                    <?= htmlspecialchars($refeicao['usuario_nome']) ?>
                                                </strong>
                                                <span class="user-email" style="display:block; font-size:10px;">
                                                    <?= htmlspecialchars($refeicao['usuario_email']) ?>
                                                </span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- TIPO -->
                                    <td>
                                        <?= htmlspecialchars($refeicao['tipo_refeicao'] ?? '—') ?>
                                    </td>

                                    <!-- DESCRIÇÃO -->
                                    <td>
                                        <div class="refeicao-descricao">
                                            <?= htmlspecialchars($refeicao['descricao_texto']) ?>
                                        </div>
                                    </td>

                                    <!-- AÇÃO -->
                                    <td>
                                        <button type="button" class="view-button btn-ver-refeicao"
                                            data-id="<?= htmlspecialchars($refeicao['refeicao_id']) ?>"
                                            data-usuario="<?= htmlspecialchars($refeicao['usuario_nome']) ?>"
                                            data-email="<?= htmlspecialchars($refeicao['usuario_email']) ?>"
                                            data-tipo="<?= htmlspecialchars($refeicao['tipo_refeicao'] ?? '') ?>"
                                            data-descricao="<?= htmlspecialchars($refeicao['descricao_texto'] ?? '') ?>"
                                            data-data="<?= htmlspecialchars($refeicao['data_consumo'] ?? '') ?>"
                                            data-calorias="<?= htmlspecialchars($refeicao['calorias_obtidas'] ?? '') ?>"
                                            data-proteina="<?= htmlspecialchars($refeicao['proteina_obtida'] ?? '') ?>"
                                            data-carbo="<?= htmlspecialchars($refeicao['carbo_obtido'] ?? '') ?>"
                                            data-gordura="<?= htmlspecialchars($refeicao['gordura_obtida'] ?? '') ?>"
                                            data-acucar="<?= htmlspecialchars($refeicao['acucar_obtido'] ?? '') ?>"
                                            data-fibra="<?= htmlspecialchars($refeicao['fibra_obtida'] ?? '') ?>"
                                            data-sodio="<?= htmlspecialchars($refeicao['sodio_obtido'] ?? '') ?>">Analisar<span>→</span></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- SEM RESULTADOS NA PESQUISA -->
            <div id="noSearchResults" class="no-search-results" role="status" aria-live="polite">
                Nenhuma refeição corresponde aos filtros selecionados.
            </div>
        </section>
    </main>

    <!-- MODAL DETALHES DA REFEIÇÃO-->
    <div class="profile-modal-overlay" id="modalRefeicao">
        <div class="profile-modal">
            <div class="profile-modal-header">
                <div>
                    <span class="profile-modal-eyebrow">REFEIÇÃO</span>
                    <h3 id="modalRefeicaoTipo">Informações da refeição</h3>
                </div>
                <button type="button" class="modal-close" id="btnFecharModalRefeicao">×</button>
            </div>

            <div class="profile-identity">
                <div>
                    <h4 id="modalRefeicaoUsuarioNome">-</h4>
                    <p id="modalRefeicaoUsuario">-</p>
                </div>
            </div>

            <!-- INFORMAÇÕES BÁSICAS -->
            <div class="profile-section">
                <div class="profile-section-title">INFORMAÇÕES</div>
                <div class="profile-info-grid">
                    <div class="profile-info-card">
                        <span>ID DA REFEIÇÃO</span>
                        <strong id="modalRefeicaoId">-</strong>
                    </div>

                    <div class="profile-info-card">
                        <span>DATA DE CONSUMO</span>
                        <strong id="modalRefeicaoData">—</strong>
                    </div>

                    <div class="profile-info-card">
                        <span>TIPO DE REFEIÇÃO</span>
                        <strong id="modalRefeicaoTipoInfo">—</strong>
                    </div>

                    <div class="profile-info-card">
                        <span>USUÁRIO</span>
                        <strong id="modalRefeicaoUsuarioCard">—</strong>
                    </div>
                </div>
            </div>

            <!-- DESCRIÇÃO -->
            <div class="profile-section">
                <div class="profile-section-title">DESCRIÇÃO</div>
                <div class="objective-box">
                    <div>
                        <strong id="modalRefeicaoDescricao" style="font-weight: 400; line-height: 1.5;">—</strong>
                    </div>
                </div>
            </div>

            <!-- INFORMAÇÕES NUTRICIONAIS -->
            <div class="profile-section">
                <div class="profile-section-title">INFORMAÇÕES NUTRICIONAIS</div>

                <div class="biometric-grid">
                    <!-- CALORIAS -->
                    <div class="biometric-card">
                        <span class="material-symbols-outlined" style="color: var(--primary);">bolt</span>
                        <div>
                            <small>CALORIAS</small>
                            <strong id="modalRefeicaoCalorias">—</strong>
                        </div>
                    </div>

                    <!-- PROTEÍNA -->
                    <div class="biometric-card">
                        <span class="material-symbols-outlined" style="color: var(--primary);">Weight</span>
                        <div>
                            <small>PROTEÍNA</small>
                            <strong id="modalRefeicaoProteina">—</strong>
                        </div>
                    </div>

                    <!-- CARBOIDRATOS -->
                    <div class="biometric-card">
                        <span class="material-symbols-outlined" style="color: var(--primary);">Wheat</span>
                        <div>
                            <small>CARBOIDRATOS</small>
                            <strong id="modalRefeicaoCarbo">—</strong>
                        </div>
                    </div>

                    <!-- GORDURAS -->
                    <div class="biometric-card">
                        <span class="material-symbols-outlined" style="color: var(--primary);">water_drop</span>
                        <div>
                            <small>GORDURAS</small>
                            <strong id="modalRefeicaoGordura">—</strong>
                        </div>
                    </div>

                    <!-- AÇÚCAR -->
                    <div class="biometric-card">
                        <span class="material-symbols-outlined" style="color: var(--primary);">cookie</span>
                        <div>
                            <small>AÇÚCAR</small>
                            <strong id="modalRefeicaoAcucar">—</strong>
                        </div>
                    </div>

                    <!-- FIBRA -->
                    <div class="biometric-card">
                        <span class="material-symbols-outlined" style="color: var(--primary);">eco</span>
                        <div>
                            <small>FIBRA</small>
                            <strong id="modalRefeicaoFibra">—</strong>
                        </div>
                    </div>

                    <!-- SÓDIO -->
                    <div class="biometric-card">
                        <span class="material-symbols-outlined" style="color: var(--primary);">Salinity</span>
                        <div>
                            <small>SÓDIO</small>
                            <strong id="modalRefeicaoSodio">—</strong>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        // ELEMENTOS E FILTROS DE REFEIÇÕES
        const searchInput = document.getElementById("searchRefeicoes");
        const tableBody = document.getElementById("refeicoesTableBody");
        const noResults = document.getElementById("noSearchResults");
        const dateStartInput = document.getElementById("filterDateStart");
        const dateEndInput = document.getElementById("filterDateEnd");
        const tipoFilter = document.getElementById("filterTipo");
        const clearFiltersButton = document.getElementById("clearRefeicaoFilters");
        const refeicoesCount = document.getElementById("refeicoesCount");
        const totalRefeicoes = tableBody.querySelectorAll("tr[data-user]").length;

        function normalizeText(value) {
            return String(value ?? "")
                .toLocaleLowerCase("pt-BR")
                .normalize("NFD")
                .replace(/[\u0300-\u036f]/g, "")
                .trim();
        }

        function filterRefeicoes() {
            const search = normalizeText(searchInput.value);
            const dateStart = dateStartInput.value;
            const dateEnd = dateEndInput.value;
            const selectedTipo = normalizeText(tipoFilter.value);

            const rows = tableBody.querySelectorAll("tr[data-user]");
            let visibleRows = 0;

            rows.forEach(row => {
                const userName = normalizeText(row.dataset.user);
                const userEmail = normalizeText(row.dataset.email);
                const rowTipo = normalizeText(row.dataset.tipo);
                const rowDate = row.dataset.date ?? "";

                const matchesSearch = userName.includes(search) || userEmail.includes(search);
                const matchesTipo = selectedTipo === "" || rowTipo === selectedTipo;

                let matchesDate = true;
                if (dateStart && rowDate < dateStart) matchesDate = false;
                if (dateEnd && rowDate > dateEnd) matchesDate = false;

                const visible = matchesSearch && matchesTipo && matchesDate;

                row.style.display = visible ? "" : "none";
                if (visible) visibleRows++;
            });

            refeicoesCount.textContent = visibleRows;
            noResults.style.display = totalRefeicoes > 0 && visibleRows === 0 ? "block" : "none";
        }

        searchInput.addEventListener("input", filterRefeicoes);
        dateStartInput.addEventListener("change", filterRefeicoes);
        dateEndInput.addEventListener("change", filterRefeicoes);
        tipoFilter.addEventListener("change", filterRefeicoes);

        clearFiltersButton.addEventListener("click", function () {
            searchInput.value = "";
            dateStartInput.value = "";
            dateEndInput.value = "";
            tipoFilter.value = "";
            filterRefeicoes();
        });

        filterRefeicoes();

        // MODAL DETALHES
        const modalRefeicao = document.getElementById("modalRefeicao");
        const btnFecharModalRefeicao = document.getElementById("btnFecharModalRefeicao");

        document.querySelectorAll(".btn-ver-refeicao").forEach(button => {
            button.addEventListener("click", function () {
                const id = this.dataset.id;
                const usuario = this.dataset.usuario;
                const email = this.dataset.email;
                const tipo = this.dataset.tipo;
                const descricao = this.dataset.descricao;
                const data = this.dataset.data;
                const calorias = this.dataset.calorias;
                const proteina = this.dataset.proteina;
                const carbo = this.dataset.carbo;
                const gordura = this.dataset.gordura;
                const acucar = this.dataset.acucar;
                const fibra = this.dataset.fibra;
                const sodio = this.dataset.sodio;

                document.getElementById("modalRefeicaoId").textContent = "#" + id;
                document.getElementById("modalRefeicaoUsuario").textContent = email;
                document.getElementById("modalRefeicaoUsuarioNome").textContent = usuario;
                document.getElementById("modalRefeicaoUsuarioCard").textContent = usuario;
                document.getElementById("modalRefeicaoTipo").textContent = tipo || "Refeição";
                document.getElementById("modalRefeicaoTipoInfo").textContent = tipo || "—";

                if (data) {
                    const dataObj = new Date(data.replace(" ", "T"));
                    document.getElementById("modalRefeicaoData").textContent = dataObj.toLocaleDateString("pt-BR");
                } else {
                    document.getElementById("modalRefeicaoData").textContent = "—";
                }

                document.getElementById("modalRefeicaoDescricao").textContent = descricao || "Nenhuma descrição";
                document.getElementById("modalRefeicaoCalorias").textContent = calorias ? Number(calorias).toFixed(0) + " kcal" : "—";
                document.getElementById("modalRefeicaoProteina").textContent = proteina ? Number(proteina).toFixed(1) + " g" : "—";
                document.getElementById("modalRefeicaoCarbo").textContent = carbo ? Number(carbo).toFixed(1) + " g" : "—";
                document.getElementById("modalRefeicaoGordura").textContent = gordura ? Number(gordura).toFixed(1) + " g" : "—";
                document.getElementById("modalRefeicaoAcucar").textContent = acucar ? Number(acucar).toFixed(1) + " g" : "—";
                document.getElementById("modalRefeicaoFibra").textContent = fibra ? Number(fibra).toFixed(1) + " g" : "—";
                document.getElementById("modalRefeicaoSodio").textContent = sodio ? Number(sodio).toFixed(1) + " mg" : "—";

                modalRefeicao.classList.add("active");
                document.body.classList.add("modal-open");
            });
        });

        function fecharModalRefeicao() {
            modalRefeicao.classList.remove("active");
            document.body.classList.remove("modal-open");
        }

        btnFecharModalRefeicao.addEventListener("click", fecharModalRefeicao);

        modalRefeicao.addEventListener("click", function (event) {
            if (event.target === modalRefeicao) {
                fecharModalRefeicao();
            }
        });

        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape" && modalRefeicao.classList.contains("active")) {
                fecharModalRefeicao();
            }
        });

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