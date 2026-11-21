<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/../config/conexao.php';

$usuario_id = $_SESSION['usuario_id'];
$usuario_nome = $_SESSION['usuario_nome'] ?? 'Usuário';

$idade = 'Não informado';
$peso = 'Não informado';
$altura = 'Não informado';
$atividade = 'Não informado';
$objetivo = 'Não informado';
$biometria = [];
$imc = null;
$imcClasse = '';

try {
    $stmtBio = $pdo->prepare("SELECT idade, peso, altura, nivel_atividade, objetivo, genero FROM biometria WHERE usuario_id = ?");
    $stmtBio->execute([$usuario_id]);
    $biometria = $stmtBio->fetch(PDO::FETCH_ASSOC);
    if ($biometria) {
        $idade = $biometria['idade'] . ' anos';
        $peso = number_format($biometria['peso'], 1, ',', '.') . ' kg';
        $alturaRaw = floatval($biometria['altura']);
        $altura = ($alturaRaw < 3 ? $alturaRaw * 100 : $alturaRaw) . ' cm';

        $procuraAtv = [
            'Sedentario' => 'Sedentário',
            'Leve' => 'Leve',
            'Moderado' => 'Moderado',
            'Ativo' => 'Ativo',
            'Muito_Ativo' => 'Muito Ativo'
        ];
        $alturaM = $alturaRaw < 3 ? $alturaRaw : $alturaRaw / 100;
        if ($alturaM > 0) {
            $imc = floatval($biometria['peso']) / ($alturaM * $alturaM);
            if ($imc < 18.5)
                $imcClasse = 'Abaixo do peso';
            elseif ($imc < 25)
                $imcClasse = 'Peso normal';
            elseif ($imc < 30)
                $imcClasse = 'Sobrepeso';
            else
                $imcClasse = 'Obesidade';
        }

        $atividade = $procuraAtv[$biometria['nivel_atividade']] ?? $biometria['nivel_atividade'];

        $procuraObj = [
            'Perda_Peso' => 'Perder peso',
            'Manter_Saude' => 'Manter peso',
            'Ganho_Massa' => 'Ganhar peso'
        ];
        $objetivo = $procuraObj[$biometria['objetivo']] ?? $biometria['objetivo'];
    }
} catch (Exception $e) {
}

try {
    $stmtPeso = $pdo->prepare("SELECT peso, data_registro FROM historico_peso WHERE usuario_id = ? ORDER BY data_registro ASC");
    $stmtPeso->execute([$usuario_id]);
    $historico = $stmtPeso->fetchAll(PDO::FETCH_ASSOC);
    $labelsDias = [];
    $dadosPesos = [];
    foreach ($historico as $r) {
        $labelsDias[] = date('d/m', strtotime($r['data_registro']));
        $dadosPesos[] = floatval($r['peso']);
    }
} catch (Exception $e) {
    $labelsDias = [];
    $dadosPesos = [];
}

$variacao = null;
if (count($dadosPesos) >= 2) {
    $variacao = end($dadosPesos) - $dadosPesos[0];
}
$totalRegistros = count($dadosPesos);
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil — NutrIA</title>
    <script>
        (function() {
            var tema = 'light';
            try {
                if (localStorage.getItem('nutria-theme') === 'dark') tema = 'dark';
            } catch (e) {}
            document.documentElement.setAttribute('data-theme', tema);
        })();
    </script>
    <link rel="stylesheet" href="../../public/css/perfil_style.css">
    <link rel="icon" type="image/svg+xml" href="../../public/img/logo.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>

    <div class="menu-backdrop" id="menuBackdrop"></div>

    <!-- =====================================================
     HEADER
===================================================== -->
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
            <a href="metas.php" class="nav-item">
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
            <a href="perfil.php" class="nav-item active">
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

    <div class="background-glow glow-1"></div>
    <div class="background-glow glow-2"></div>

    <!-- =====================================================
     MODAL EDITAR PERFIL
===================================================== -->
    <div id="modalEditar" style="display:none;">
        <div class="modal-inner">

            <div class="modal-title">Atualizar dados</div>

            <?php if (isset($_GET['erro'])): ?>
                <div class="modal-error">
                    <?php
                    $erros = [
                        'nome_invalido' => 'Nome deve ter entre 3 e 100 caracteres',
                        'idade_invalida' => 'Idade deve estar entre 15 e 120 anos',
                        'peso_invalido' => 'Peso deve estar entre 30 kg e 300 kg',
                        'altura_invalida' => 'Altura deve estar entre 80 cm e 250 cm',
                        'genero_invalido' => 'Gênero inválido',
                        'atividade_invalida' => 'Nível de atividade inválido',
                        'objetivo_invalido' => 'Objetivo inválido',
                        'proporcoes_irreais' => 'Proporções entre peso e altura não são realistas (IMC inválido)',
                    ];
                    echo $erros[$_GET['erro']] ?? 'Erro desconhecido';
                    ?>
                </div>
            <?php endif; ?>

            <div class="form-alert" id="erroForm" role="alert" aria-live="assertive">
                <svg viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10" />
                    <line x1="12" y1="8" x2="12" y2="12" />
                    <line x1="12" y1="16" x2="12.01" y2="16" />
                </svg>
                <span id="erroFormTexto"></span>
            </div>

            <form action="../controllers/perfil_controller.php" method="POST" id="formPerfil" novalidate>

                <div class="modal-section">
                    <label>Nome</label>
                    <input type="text" name="nome" value="<?php echo htmlspecialchars($usuario_nome); ?>" required
                        minlength="3" maxlength="50" placeholder="Seu nome">
                </div>

                <div class="modal-row">
                    <div class="modal-section">
                        <label>Idade</label>
                        <input type="number" name="idade" value="<?php echo $biometria['idade'] ?? ''; ?>" required
                            min="15" max="100" placeholder="Ex: 25">
                    </div>
                    <div class="modal-section">
                        <label>Sexo</label>
                        <select name="genero" required>
                            <option value="Masculino" <?php echo ($biometria['genero'] ?? '') === 'Masculino' ? 'selected' : ''; ?>>Masculino</option>
                            <option value="Feminino" <?php echo ($biometria['genero'] ?? '') === 'Feminino' ? 'selected' : ''; ?>>Feminino</option>
                            <option value="Outro" <?php echo ($biometria['genero'] ?? '') === 'Outro' ? 'selected' : ''; ?>>Outro</option>
                        </select>
                    </div>
                </div>

                <div class="modal-row">
                    <div class="modal-section">
                        <label>Peso atual (kg)</label>
                        <input type="number" name="peso" step="0.1" value="<?php echo $biometria['peso'] ?? ''; ?>"
                            required min="30" max="300" placeholder="Ex: 70">
                    </div>
                    <div class="modal-section">
                        <label>Altura (cm)</label>
                        <input type="number" name="altura"
                            value="<?php echo isset($biometria['altura']) ? ($biometria['altura'] * 100) : ''; ?>"
                            required min="80" max="250" placeholder="Ex: 175">
                    </div>
                </div>

                <div class="modal-section">
                    <label>Nível de atividade</label>
                    <select name="nivel_atividade" required>
                        <option value="Sedentario" <?php echo ($biometria['nivel_atividade'] ?? '') === 'Sedentario' ? 'selected' : ''; ?>>Sedentário</option>
                        <option value="Leve" <?php echo ($biometria['nivel_atividade'] ?? '') === 'Leve' ? 'selected' : ''; ?>>Leve</option>
                        <option value="Moderado" <?php echo ($biometria['nivel_atividade'] ?? '') === 'Moderado' ? 'selected' : ''; ?>>Moderado</option>
                        <option value="Ativo" <?php echo ($biometria['nivel_atividade'] ?? '') === 'Ativo' ? 'selected' : ''; ?>>Ativo</option>
                        <option value="Muito_Ativo" <?php echo ($biometria['nivel_atividade'] ?? '') === 'Muito_Ativo' ? 'selected' : ''; ?>>Muito Ativo</option>
                    </select>
                </div>

                <div class="modal-section">
                    <label>Objetivo</label>
                    <select name="objetivo" required>
                        <option value="Perda_Peso" <?php echo ($biometria['objetivo'] ?? '') === 'Perda_Peso' ? 'selected' : ''; ?>>Perder peso</option>
                        <option value="Manter_Saude" <?php echo ($biometria['objetivo'] ?? '') === 'Manter_Saude' ? 'selected' : ''; ?>>Manter saúde</option>
                        <option value="Ganho_Massa" <?php echo ($biometria['objetivo'] ?? '') === 'Ganho_Massa' ? 'selected' : ''; ?>>Ganhar peso</option>
                    </select>
                </div>

                <div class="modal-footer">
                    <button type="button" id="btnFecharModal" class="btn-cancel">Cancelar</button>
                    <button type="submit" class="btn-save">Salvar →</button>
                </div>

            </form>
        </div>
    </div>

    <!-- =====================================================
     CONTEÚDO
===================================================== -->
    <div id="toastContainer" role="status" aria-live="polite"></div>
    <div class="container">
        <div class="perfil-layout">

            <!-- Info card -->
            <aside class="info-card">
                <div class="card-header">
                    <h2>Informações</h2>
                </div>
                <ul>
                    <li><span>Nome</span> <strong><?php echo htmlspecialchars($usuario_nome); ?></strong></li>
                    <li><span>Idade</span> <strong><?php echo htmlspecialchars($idade); ?></strong></li>
                    <li><span>Peso atual</span> <strong><?php echo htmlspecialchars($peso); ?></strong></li>
                    <li><span>Altura</span> <strong><?php echo htmlspecialchars($altura); ?></strong></li>
                    <li><span>Atividade</span> <strong><?php echo htmlspecialchars($atividade); ?></strong></li>
                    <li><span>Objetivo</span> <strong><?php echo htmlspecialchars($objetivo); ?></strong></li>
                </ul>
                <button id="btnEditarPerfil">Editar perfil →</button>
            </aside>

            <!-- Gráfico -->
            <section class="chart-card">
                <div class="card-header">
                    <h3>Evolução do peso corporal</h3>
                </div>
                <div class="chart-container">
                    <canvas id="graficoEvolucaoPeso"></canvas>
                </div>

                <div class="stats-row">
                    <div class="stat-tile">
                        <span>IMC</span>
                        <strong><?php echo $imc !== null ? number_format($imc, 1, ',', '.') : '—'; ?></strong>
                        <small><?php echo htmlspecialchars($imcClasse ?: 'Sem dados'); ?></small>
                    </div>
                    <div class="stat-tile">
                        <span>Variação</span>
                        <strong
                            class="<?php echo $variacao === null ? '' : ($variacao > 0 ? 'sobe' : ($variacao < 0 ? 'desce' : '')); ?>">
                            <?php echo $variacao !== null ? (($variacao > 0 ? '+' : '') . number_format($variacao, 1, ',', '.') . ' kg') : '—'; ?>
                        </strong>
                        <small>Desde o 1º registro</small>
                    </div>
                    <div class="stat-tile">
                        <span>Registros</span>
                        <strong><?php echo $totalRegistros; ?></strong>
                        <small>Pesagens salvas</small>
                    </div>
                </div>
            </section>

        </div>
    </div>

    <script>
        const modal = document.getElementById('modalEditar');
        const btnAbrir = document.getElementById('btnEditarPerfil');
        const btnFechar = document.getElementById('btnFecharModal');
        const form = document.getElementById('formPerfil');

        btnAbrir.addEventListener('click', () => modal.style.display = 'flex');
        btnFechar.addEventListener('click', () => modal.style.display = 'none');
        window.addEventListener('click', e => {
            if (e.target === modal) modal.style.display = 'none';
        });

        /* ---------- Tema claro / escuro ---------- */
        (function() {
            const raiz = document.documentElement;
            const botao = document.getElementById('themeToggle');

            function atualizarBotao() {
                const escuro = raiz.getAttribute('data-theme') === 'dark';
                const texto = escuro ? 'Mudar para o tema claro' : 'Mudar para o tema escuro';
                botao.setAttribute('aria-label', texto);
                botao.setAttribute('title', texto);
            }

            botao.addEventListener('click', function() {
                const novo = raiz.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                raiz.setAttribute('data-theme', novo);
                try {
                    localStorage.setItem('nutria-theme', novo);
                } catch (e) {}
                atualizarBotao();
                document.dispatchEvent(new CustomEvent('nutria-tema', {
                    detail: novo
                }));
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
        document.addEventListener('click', e => {
            if (!header.contains(e.target) && e.target !== menuToggle) setMenu(false);
        });
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') setMenu(false);
        });
        window.matchMedia('(min-width: 1025px)').addEventListener('change', e => {
            if (e.matches) setMenu(false);
        });

        /* ---------- Toast com o mascote ---------- */
        const MASCOTE_TOAST = '../../public/img/Nutri_musc.png';

        const MENSAGENS_PERFIL = {
            perfil_atualizado: {texto: 'Perfil atualizado',                         tipo: 'sucesso'},
            tempo_excedido:    {texto: 'Tempo excedido. Tente novamente',            tipo: 'aviso'},
            sem_conexao:       {texto: 'Sem conexão com o serviço. Tente novamente', tipo: 'erro'},
            erro:              {texto: 'Não foi possível atualizar o perfil',        tipo: 'erro'}
        };

        const ERROS_VALIDACAO = ['nome_invalido', 'idade_invalida', 'peso_invalido', 'altura_invalida',
            'genero_invalido', 'atividade_invalida', 'objetivo_invalido', 'proporcoes_irreais'];

        function mostrarToast(texto, tipo = 'sucesso', duracao = 4500) {
            const box = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = 'toast-card ' + tipo;
            toast.style.setProperty('--dur', duracao + 'ms');

            const img = document.createElement('img');
            img.className = 'toast-mascote';
            img.src = MASCOTE_TOAST;
            img.alt = '';
            img.setAttribute('aria-hidden', 'true');
            img.addEventListener('error', () => {
                img.remove();
                toast.classList.add('sem-mascote');
            });

            const msg = document.createElement('div');
            msg.className = 'toast-texto';
            msg.textContent = texto;

            const barra = document.createElement('div');
            barra.className = 'toast-barra';
            barra.innerHTML = '<span></span>';

            toast.append(img, msg, barra);
            box.appendChild(toast);

            const fechar = () => {
                if (toast.classList.contains('saindo')) return;
                toast.classList.add('saindo');
                toast.addEventListener('animationend', () => toast.remove(), {once: true});
            };
            toast.addEventListener('click', fechar);
            setTimeout(fechar, duracao);
        }

        /* ---------- Erro no modal ---------- */
        const erroBox = document.getElementById('erroForm');
        const erroTexto = document.getElementById('erroFormTexto');

        function limparErro() {
            erroBox.classList.remove('show');
            form.querySelectorAll('.invalido').forEach(el => el.classList.remove('invalido'));
        }

        function mostrarErro(mensagem, campo) {
            erroTexto.textContent = mensagem;
            erroBox.classList.remove('show');
            void erroBox.offsetWidth;
            erroBox.classList.add('show');

            form.querySelectorAll('.invalido').forEach(el => el.classList.remove('invalido'));
            if (campo) {
                campo.classList.add('invalido');
                campo.focus();
            }
        }

        form.addEventListener('input', limparErro);

        form.addEventListener('submit', function(e) {
            const nome = this.nome.value.trim();
            const idade = parseInt(this.idade.value);
            const peso = parseFloat(this.peso.value);
            const altura = parseFloat(this.altura.value);

            if (nome.length < 3 || nome.length > 100) {
                e.preventDefault();
                mostrarErro('Nome deve ter entre 3 e 100 caracteres', this.nome);
                return;
            }
            if (isNaN(idade) || idade < 15 || idade > 100) {
                e.preventDefault();
                mostrarErro('Idade deve estar entre 15 e 100 anos', this.idade);
                return;
            }
            if (isNaN(peso) || peso < 30 || peso > 300) {
                e.preventDefault();
                mostrarErro('Peso deve estar entre 30 kg e 300 kg', this.peso);
                return;
            }
            if (isNaN(altura) || altura < 80 || altura > 250) {
                e.preventDefault();
                mostrarErro('Altura deve estar entre 80 cm e 250 cm', this.altura);
                return;
            }
            const imc = peso / Math.pow(altura / 100, 2);
            if (imc < 13 || imc > 50) {
                e.preventDefault();
                mostrarErro('Proporções entre peso e altura não são realistas (IMC ' + imc.toFixed(1) + '; faixa válida: 13 a 50)', this.peso);
                return;
            }
        });

        /* ---------- Sucesso via URL ---------- */
        const urlParams = new URLSearchParams(window.location.search);
        const sucessoUrl = urlParams.get('sucesso');
        const erroUrl = urlParams.get('erro');

        if (sucessoUrl === 'perfil_atualizado') {
            const m = MENSAGENS_PERFIL.perfil_atualizado;
            mostrarToast(m.texto, m.tipo, 4000);
            document.querySelector('.info-card').classList.add('atualizado');
            window.history.replaceState({}, document.title, window.location.pathname);
        } else if (erroUrl) {
            if (ERROS_VALIDACAO.includes(erroUrl)) {
                mostrarToast('Confira os dados informados', 'aviso', 5000);
            } else {
                const m = MENSAGENS_PERFIL[erroUrl] || MENSAGENS_PERFIL.erro;
                mostrarToast(m.texto, m.tipo, 5500);
            }
        }
        <?php if (isset($_GET['erro'])): ?>
            document.getElementById('modalEditar').style.display = 'flex';
        <?php endif; ?>

        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('graficoEvolucaoPeso').getContext('2d');
            const pequeno = window.matchMedia('(max-width: 600px)').matches;
            const fonteEixo = {
                family: "'Inter', sans-serif",
                size: pequeno ? 10 : 12
            };
            const cssVar = n => getComputedStyle(document.documentElement).getPropertyValue(n).trim();
            const grafico = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($labelsDias); ?>,
                    datasets: [{
                        label: 'Peso (kg)',
                        data: <?php echo json_encode($dadosPesos); ?>,
                        borderColor: cssVar('--chart-line'),
                        backgroundColor: cssVar('--chart-fill'),
                        borderWidth: pequeno ? 2 : 2.5,
                        tension: 0.35,
                        pointBackgroundColor: cssVar('--chart-point'),
                        pointBorderColor: cssVar('--chart-point-border'),
                        pointBorderWidth: 2,
                        pointRadius: pequeno ? 3 : 5,
                        pointHoverRadius: pequeno ? 5 : 7,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            grid: {
                                color: cssVar('--chart-grid')
                            },
                            ticks: {
                                color: cssVar('--chart-tick'),
                                font: fonteEixo,
                                maxTicksLimit: pequeno ? 5 : 8
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: cssVar('--chart-tick'),
                                font: fonteEixo,
                                maxRotation: 0,
                                autoSkip: true,
                                maxTicksLimit: pequeno ? 5 : 10
                            }
                        }
                    }
                }
            });

            document.addEventListener('nutria-tema', function() {
                const ds = grafico.data.datasets[0];
                ds.borderColor = cssVar('--chart-line');
                ds.backgroundColor = cssVar('--chart-fill');
                ds.pointBackgroundColor = cssVar('--chart-point');
                ds.pointBorderColor = cssVar('--chart-point-border');
                grafico.options.scales.y.grid.color = cssVar('--chart-grid');
                grafico.options.scales.y.ticks.color = cssVar('--chart-tick');
                grafico.options.scales.x.ticks.color = cssVar('--chart-tick');
                grafico.update();
            });
        });
    </script>
</body>

</html>