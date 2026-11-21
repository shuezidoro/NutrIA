<?php
session_start();

// Trava de segurança: Se o utilizador não estiver logado, expulsa para a página de login
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}
require_once __DIR__ . '/../config/conexao.php';

$usuario_id = $_SESSION['usuario_id'];
$usuario_nome = $_SESSION['usuario_nome'] ?? 'Usuário';

// ===================================================
// SISTEMA DE METAS
// ===================================================
$metas = [
    'kcal' => 2000,
    'prot' => 120,
    'carbo' => 250,
    'gord' => 65,
    'fibra' => 25,
    'acucar' => 50,
    'sodio' => 2400
];

try {
    // 1. Tenta obter as metas salvas corretamente da tabela 'metas_diarias'
    $stmtMetas = $pdo->prepare("SELECT kcal_meta, proteina_meta, carbo_meta, gordura_meta FROM metas_diarias WHERE usuario_id = ? ORDER BY id DESC LIMIT 1");
    $stmtMetas->execute([$usuario_id]);
    $userMeta = $stmtMetas->fetch(PDO::FETCH_ASSOC);

    if ($userMeta) {
        $metas['kcal'] = !empty($userMeta['kcal_meta']) ? intval($userMeta['kcal_meta']) : 2000;
        $metas['prot'] = !empty($userMeta['proteina_meta']) ? floatval($userMeta['proteina_meta']) : 120;
        $metas['carbo'] = !empty($userMeta['carbo_meta']) ? floatval($userMeta['carbo_meta']) : 250;
        $metas['gord'] = !empty($userMeta['gordura_meta']) ? floatval($userMeta['gordura_meta']) : 65;
    } else {
        // 2. CASO NÃO EXISTA REGISTRO: calcula dinamicamente
        $stmtBio = $pdo->prepare("SELECT * FROM biometria WHERE usuario_id = ? LIMIT 1");
        $stmtBio->execute([$usuario_id]);
        $biometria = $stmtBio->fetch(PDO::FETCH_ASSOC);

        if ($biometria) {
            $peso = $biometria['peso'];
            $altura_cm = $biometria['altura'] * 100;
            $idade = $biometria['idade'];
            $genero = $biometria['genero'];
            $nivel_atividade = $biometria['nivel_atividade'];
            $objetivo = $biometria['objetivo'];

            if ($genero === 'Masculino') {
                $tmb = 88.36 + (13.4 * $peso) + (4.8 * $altura_cm) - (5.7 * $idade);
            } else {
                $tmb = 447.59 + (9.2 * $peso) + (3.1 * $altura_cm) - (4.3 * $idade);
            }

            $fatores = [
                'Sedentario' => 1.2,
                'Leve' => 1.375,
                'Moderado' => 1.55,
                'Ativo' => 1.725,
                'Muito_Ativo' => 1.9
            ];
            $fator = $fatores[$nivel_atividade] ?? 1.2;
            $get = $tmb * $fator;

            $meta_calorica = $get;
            if ($objetivo === 'Perda_Peso') {
                $meta_calorica = $get - 500;
            } elseif ($objetivo === 'Ganho_Massa') {
                $meta_calorica = $get + 400;
            }

            $meta_final_auto = round($meta_calorica);

            $metas['kcal'] = $meta_final_auto;
            $metas['carbo'] = round(($meta_final_auto * 0.40) / 4);
            $metas['prot'] = round(($meta_final_auto * 0.30) / 4);
            $metas['gord'] = round(($meta_final_auto * 0.30) / 9);
        }
    }
} catch (Exception $e) {
    // Fallback seguro
}

// ===================================================
// CALENDÁRIO: MAPEAMENTO E BUSCA DE REFEIÇÕES
// ===================================================
// ==========================================
// REFEIÇÕES FAVORITAS DO USUÁRIO
// ==========================================
$favoritas = [];
$favChaves = [];
try {
    $stmtFav = $pdo->prepare("
        SELECT id, nome_favorito, descricao_comida, calorias, proteina, carbo, gordura, acucar, fibra, sodio
        FROM refeicoes_favoritas
        WHERE usuario_id = ?
        ORDER BY criado_em DESC, id DESC
    ");
    $stmtFav->execute([$usuario_id]);
    foreach ($stmtFav->fetchAll(PDO::FETCH_ASSOC) as $f) {
        $favoritas[] = [
            'id'        => (int) $f['id'],
            'nome'      => $f['nome_favorito'],
            'descricao' => $f['descricao_comida'],
            'calorias'  => (float) $f['calorias'],
            'proteina'  => (float) $f['proteina'],
            'carbo'     => (float) $f['carbo'],
            'gordura'   => (float) $f['gordura'],
            'acucar'    => (float) $f['acucar'],
            'fibra'     => (float) $f['fibra'],
            'sodio'     => (float) $f['sodio']
        ];
        $favChaves[mb_strtolower(trim($f['descricao_comida']))] = true;
    }
} catch (Exception $e) {
    error_log("Erro ao buscar refeições favoritas: " . $e->getMessage());
}

// Nutrientes de cada refeição (somados por refeicao_id) para o painel do modal de edição
$nutrientesPorRefeicao = [];
try {
    $stmtNut = $pdo->prepare("
        SELECT n.refeicao_id,
               SUM(n.calorias_obtidas) AS kcal,
               SUM(n.proteina_obtida)  AS prot,
               SUM(n.carbo_obtido)     AS carbo,
               SUM(n.gordura_obtida)   AS gord,
               SUM(n.fibra_obtida)     AS fibra,
               SUM(n.acucar_obtido)    AS acucar,
               SUM(n.sodio_obtido)     AS sodio
        FROM nutriente n
        INNER JOIN refeicao r ON r.id = n.refeicao_id
        WHERE r.usuario_id = ?
        GROUP BY n.refeicao_id
    ");
    $stmtNut->execute([$usuario_id]);
    foreach ($stmtNut->fetchAll(PDO::FETCH_ASSOC) as $n) {
        $nutrientesPorRefeicao[(int) $n['refeicao_id']] = [
            'kcal'   => (float) $n['kcal'],
            'prot'   => (float) $n['prot'],
            'carbo'  => (float) $n['carbo'],
            'gord'   => (float) $n['gord'],
            'fibra'  => (float) $n['fibra'],
            'acucar' => (float) $n['acucar'],
            'sodio'  => (float) $n['sodio']
        ];
    }
} catch (Exception $e) {
    error_log("Erro ao buscar nutrientes das refeições: " . $e->getMessage());
}

$eventos_refeicoes = [];
try {
    $stmt = $pdo->prepare("SELECT id, tipo_refeicao, data_consumo, descricao_texto FROM refeicao WHERE usuario_id = ?");
    $stmt->execute([$usuario_id]);
    $refeicoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($refeicoes as $ref) {
        $cor = '#00A3E0';
        if ($ref['tipo_refeicao'] == 'Almoço')
            $cor = '#10ac84';
        if ($ref['tipo_refeicao'] == 'Jantar')
            $cor = '#ee5253';
        if ($ref['tipo_refeicao'] == 'Café da Manhã')
            $cor = '#ff9f43';

        $eventos_refeicoes[] = [
            'id' => $ref['id'],
            'title' => $ref['tipo_refeicao'],
            'start' => $ref['data_consumo'],
            'description' => $ref['descricao_texto'],
            'favorita' => isset($favChaves[mb_strtolower(trim($ref['descricao_texto']))]),
            'nutrientes' => $nutrientesPorRefeicao[(int) $ref['id']] ?? null,
            'backgroundColor' => $cor,
            'borderColor' => $cor
        ];
    }
} catch (Exception $e) {
    $eventos_refeicoes = [];
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Nutricional</title>
    <script>
        (function() {
            var tema = 'light';
            try {
                if (localStorage.getItem('nutria-theme') === 'dark') tema = 'dark';
            } catch (e) {}
            document.documentElement.setAttribute('data-theme', tema);
        })();
    </script>
    <link rel="stylesheet" href="../../public/css/dashboard_style.css">
    <link rel="icon" type="image/svg+xml" href="../../public/img/logo.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css' rel='stylesheet' />
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.11/locales/pt-br.global.min.js'></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
/* Layout da modal de adicionar refeição */
.modal-refeicao-layout {
  display: flex; align-items: stretch; justify-content: center; gap: 12px;
  width: min(1000px, calc(100vw - 32px)); max-width: 1000px;
}
/* Painel lateral esquerdo: nutrientes da refeição em edição */
.modal-nutrientes-refeicao {
  display: none; flex: 0 0 235px; align-self: flex-start; box-sizing: border-box;
  padding: 20px 18px; border-radius: 16px;
  background: var(--bg-card, #fff); color: var(--text, #173b29);
  border: 1px solid var(--border, #dfe7dd);
  box-shadow: 0 12px 32px rgba(0,0,0,.10);
}
.modal-nutrientes-refeicao.visivel { display: block; }
.modal-nutrientes-refeicao h4 { margin: 0 0 4px; font-size: 14px; color: var(--green, #1d633d); }
.modal-nutrientes-refeicao .nut-sub { margin: 0 0 14px; font-size: 11px; line-height: 1.4; color: var(--text-soft, #587064); }
.nut-kcal { display:flex; align-items:baseline; gap:6px; margin-bottom:14px; padding-bottom:12px; border-bottom:1px solid var(--border, #dfe7dd); }
.nut-kcal strong { font-size: 28px; line-height: 1; color: var(--c-kcal, #FF8A00); }
.nut-kcal span { font-size: 12px; font-weight: 600; color: var(--text-soft, #587064); }
.nut-lista { display: grid; gap: 11px; }
.nut-item { --macro-color: var(--green, #1d633d); }
.nut-item.n-prot { --macro-color: var(--c-prot, #2563EB); }
.nut-item.n-carbo { --macro-color: var(--c-carbo, #FACC15); }
.nut-item.n-gord { --macro-color: var(--c-gord, #8B5CF6); }
.nut-item.n-fibra { --macro-color: var(--c-fibra, #16A34A); }
.nut-item.n-acucar { --macro-color: var(--c-acucar, #EC4899); }
.nut-item.n-sodio { --macro-color: var(--c-sodio, #06B6D4); }
.nut-linha { display:flex; align-items:center; justify-content:space-between; gap:8px; font-size:12px; font-weight:600; }
.nut-linha .nut-nome { display:inline-flex; align-items:center; gap:6px; color: var(--text-soft, #587064); }
.nut-linha .nut-nome::before { content:""; width:8px; height:8px; border-radius:50%; background: var(--macro-color); flex-shrink:0; }
.nut-linha .nut-valor { color: var(--text, #173b29); white-space: nowrap; }
.nut-barra { height:5px; margin-top:5px; border-radius:5px; overflow:hidden; background: var(--track, rgba(0,0,0,.08)); }
.nut-barra span { display:block; height:100%; width:0; border-radius:5px; background: var(--macro-color); transition: width .5s ease; }
.nut-meta { margin-top:3px; font-size:10px; color: var(--text-muted, #8a998f); }
.nut-vazio { margin:0; font-size:12px; line-height:1.5; color: var(--text-soft, #587064); }
.modal-refeicao-layout > .modal-conteudo,
.modal-refeicao-layout > .modal-content {
  flex: 1 1 340px; min-width: 0;
}
.modal-recado-nutria {
  position: relative; flex: 0 0 215px; min-height: 145px; align-self: flex-start;
  box-sizing: border-box; padding: 24px 18px 58px; border-radius: 16px;
  background: var(--bg-card, #fff); color: var(--text, #173b29);
  border: 1px solid var(--border, #dfe7dd);
  box-shadow: 0 12px 32px rgba(0,0,0,.10);
  font-family: inherit;
}
.modal-recado-nutria strong { display:block; font-size: 14px; margin-bottom: 8px; color: var(--green, #1d633d); }
.modal-recado-nutria p { margin:0; font-size:12px; line-height:1.55; color:var(--text-soft, #587064); }
.modal-recado-nutria img { position:absolute; width:76px; height:auto; max-height:82px; object-fit:contain; right:8px; bottom:4px; }
@media (max-width: 700px) {
  /* No celular, a rolagem fica no overlay inteiro. Assim o formulário e
     o aviso permanecem dentro do mesmo modal, sem cortar o conteúdo. */
  #modalRefeicao.modal-overlay {
    align-items: flex-start !important;
    justify-content: center !important;
    overflow-x: hidden !important;
    overflow-y: auto !important;
    padding: 12px 0 !important;
    box-sizing: border-box;
    -webkit-overflow-scrolling: touch;
  }
  .modal-refeicao-layout {
    flex-direction: column !important;
    align-items: stretch;
    width: calc(100vw - 24px) !important;
    max-width: 520px;
    max-height: none !important;
    height: auto !important;
    overflow: visible !important;
    margin: 0 auto;
  }
  .modal-refeicao-layout > .modal-conteudo,
  .modal-refeicao-layout > .modal-content {
    flex: 0 0 auto;
    width: 100%;
    min-width: 0;
    max-height: none;
    overflow: visible;
    box-sizing: border-box;
  }
  .modal-nutrientes-refeicao { flex: 0 0 auto; width: 100%; padding: 16px; }
  .nut-lista { grid-template-columns: 1fr 1fr; gap: 12px 16px; }
  .modal-recado-nutria {
    flex: 0 0 auto;
    min-height: 110px;
    width: 100%;
    padding: 16px 100px 18px 16px;
    box-sizing: border-box;
  }
  .modal-recado-nutria img { width:70px; right:12px; bottom:4px; }
}
</style>

<style>
/* =====================================================
   REFEIÇÕES FAVORITAS
===================================================== */
.btn-favoritar {
  width: 34px; height: 34px; padding: 0;
  display: inline-flex; align-items: center; justify-content: center;
  border: 1px solid rgba(127,127,127,.28); border-radius: 10px;
  background: rgba(127,127,127,.10); color: inherit;
  font-size: 18px; line-height: 1; opacity: .55; cursor: pointer;
  transition: opacity .2s, color .2s, background .2s, border-color .2s, transform .15s;
}
.btn-favoritar:hover { opacity: 1; transform: scale(1.06); }
.btn-favoritar.ativa { color: #f5b301; opacity: 1; background: rgba(245,179,1,.14); border-color: rgba(245,179,1,.45); }
.btn-favoritar:disabled { cursor: wait; opacity: .4; transform: none; }

/* Dashboard: estrela ao lado do card da lista do dia */
.refeicao-dia-item { position: relative; }
.refeicao-dia-item .refeicao-dia-card { width: 100%; padding-right: 56px; }
.refeicao-dia-item .btn-favoritar { position: absolute; top: 10px; right: 10px; }

/* Lista de favoritas dentro do modal */
.lista-favoritas { display: grid; gap: 8px; max-height: 240px; overflow-y: auto; padding-right: 2px; }
.favorita-item {
  display: flex; align-items: stretch; overflow: hidden;
  border: 1px solid rgba(127,127,127,.28); border-radius: 12px;
  background: rgba(127,127,127,.08);
  transition: border-color .2s, background .2s;
}
.favorita-item.selecionada { border-color: #f5b301; background: rgba(245,179,1,.12); }
.favorita-escolher {
  flex: 1; min-width: 0; display: flex; align-items: center; justify-content: space-between; gap: 12px;
  padding: 11px 13px; border: none; background: transparent; color: inherit;
  font: inherit; font-size: 13px; text-align: left; cursor: pointer;
}
.favorita-desc {
  overflow: hidden; line-height: 1.4;
  display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
}
.favorita-kcal { flex-shrink: 0; font-size: 12px; font-weight: 700; opacity: .8; white-space: nowrap; }
.favorita-remover {
  width: 38px; border: none; border-left: 1px solid rgba(127,127,127,.2);
  background: transparent; color: inherit; opacity: .5; font-size: 18px; cursor: pointer;
}
.favorita-remover:hover { opacity: 1; color: #ee5253; }
.favoritas-vazio {
  padding: 14px; text-align: center; font-size: 13px; line-height: 1.6; opacity: .75;
  border: 1px dashed rgba(127,127,127,.35); border-radius: 12px;
}
.resumo-favorita {
  margin: 12px 0 4px; padding: 12px 14px; font-size: 13px; line-height: 1.6;
  border: 1px solid rgba(127,127,127,.28); border-radius: 12px; background: rgba(127,127,127,.08);
}
</style>
<style>
/* Sugestão inteligente no dashboard (mesmo comportamento da Home) */
.btn-sugestao { background: var(--tint, rgba(29,99,61,.08)); color: var(--green); border: 1px solid var(--border-green, var(--border)); }
.btn-sugestao:hover { filter: brightness(.97); }
.btn-sugestao:disabled { opacity: .6; cursor: wait; }
.sugestao-box { display:none; background:var(--tint, rgba(29,99,61,.08)); border:1px solid var(--border-green, var(--border)); border-radius:10px; padding:15px; margin:0 0 15px; color:var(--text); }
.sugestao-box h4 { margin:0 0 4px; font-size:15px; color:var(--green); }
.sugestao-box .sug-meta { font-size:12px; color:var(--text-soft); margin-bottom:10px; }
.sugestao-box ul { margin:0 0 10px; padding-left:18px; font-size:13px; line-height:1.6; }
.sugestao-box .sug-kcal { font-weight:600; font-size:13px; margin-bottom:8px; }
.sugestao-box .sug-motivo { font-size:12px; color:var(--text-soft); margin-bottom:8px; }
.sugestao-box .sug-aviso { font-size:11px; color:var(--text-soft); opacity:.8; }
.sugestao-box .sug-outra { margin-top:10px; background:transparent; border:1px solid var(--border-green, var(--border)); color:var(--green); border-radius:6px; padding:6px 12px; cursor:pointer; font-size:12px; }
</style>
</head>

<body>

    <div id="toastContainer" role="status" aria-live="polite"></div>

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
                    <path
                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                Home
            </a>
            <a href="dashboard.php" class="nav-item active">
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
        <div class="dashboard-grid">
            <div id='calendar'></div>

            <div class="side-panel">
                <h3 style="margin-top:0; color:#2c3e50;">Resumo Nutricional</h3>
                <p id="label-data-selecionada" style="color:#7f8c8d; font-weight:bold; margin-bottom: 15px;"></p>

                <div style="max-width: 200px; margin: 0 auto 15px auto;">
                    <canvas id="macroChart"></canvas>
                </div>

                <div class="macro-list">
                    <div class="macro-item-list bg-kcal">
                        <div class="macro-info">
                            <span>Energia Diária</span>
                            <span><span id="res-kcal">0</span> / <?php echo $metas['kcal']; ?> kcal</span>
                        </div>
                        <div class="progress-bar-container">
                            <div id="bar-kcal" class="progress-bar-fill"></div>
                        </div>
                    </div>

                    <div class="macro-item-list bg-prot">
                        <div class="macro-info">
                            <span>Proteínas</span>
                            <span><span id="res-prot">0</span> / <?php echo $metas['prot']; ?> g</span>
                        </div>
                        <div class="progress-bar-container">
                            <div id="bar-prot" class="progress-bar-fill"></div>
                        </div>
                    </div>

                    <div class="macro-item-list bg-carbo">
                        <div class="macro-info">
                            <span>Carboidratos</span>
                            <span><span id="res-carbo">0</span> / <?php echo $metas['carbo']; ?> g</span>
                        </div>
                        <div class="progress-bar-container">
                            <div id="bar-carbo" class="progress-bar-fill"></div>
                        </div>
                    </div>

                    <div class="macro-item-list bg-gord">
                        <div class="macro-info">
                            <span>Gorduras</span>
                            <span><span id="res-gord">0</span> / <?php echo $metas['gord']; ?> g</span>
                        </div>
                        <div class="progress-bar-container">
                            <div id="bar-gord" class="progress-bar-fill"></div>
                        </div>
                    </div>

                    <div class="macro-item-list bg-fibra">
                        <div class="macro-info">
                            <span>Fibras</span>
                            <span><span id="res-fibra">0</span> / <?php echo $metas['fibra']; ?> g</span>
                        </div>
                        <div class="progress-bar-container">
                            <div id="bar-fibra" class="progress-bar-fill"></div>
                        </div>
                    </div>

                    <div class="macro-item-list bg-acucar">
                        <div class="macro-info">
                            <span>Açúcares</span>
                            <span><span id="res-acucar">0</span> / <?php echo $metas['acucar']; ?> g</span>
                        </div>
                        <div class="progress-bar-container">
                            <div id="bar-acucar" class="progress-bar-fill"></div>
                        </div>
                    </div>

                    <div class="macro-item-list bg-sodio">
                        <div class="macro-info">
                            <span>Sódio Total</span>
                            <span><span id="res-sodio">0</span> / <?php echo $metas['sodio']; ?> mg</span>
                        </div>
                        <div class="progress-bar-container">
                            <div id="bar-sodio" class="progress-bar-fill"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- =====================================================
     MODAL: REFEIÇÕES DO DIA
===================================================== -->
    <div id="modalListaDia" class="modal-overlay">
        <div class="modal-content modal-lista-dia">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                <h3 id="modalListaTitulo" style="margin:0; color:#2c3e50;">Refeições do dia</h3>
                <button type="button" id="btnFecharListaDia"
                    style="background:none; border:none; font-size:22px; cursor:pointer; color:#aaa; line-height:1;"
                    title="Fechar">&times;</button>
            </div>

            <div id="listaRefeicoesDia" class="lista-refeicoes-dia"></div>

            <div style="display:flex; gap:10px; margin-top:18px;">
                <button type="button" id="btnAdicionarDia" class="btn-adicionar-dia">Adicionar refeição</button>
            </div>
        </div>
    </div>

    <div id="modalRefeicao" class="modal-overlay">
        <div class="modal-refeicao-layout">
        <aside class="modal-nutrientes-refeicao" id="painelNutrientes" aria-label="Nutrientes da refeição selecionada" aria-live="polite">
            <h4>Nutrientes da refeição</h4>
            <p class="nut-sub" id="nutSub"></p>
            <div id="nutConteudo"></div>
        </aside>
        <div class="modal-content">
            <h3 id="modal_titulo" style="margin-top:0;">Adicionar Refeição</h3>

            <form action="../controllers/refeicao_controller.php" method="POST" id="formRefeicao">
                <input type="hidden" name="data_refeicao" id="form_data">
                <input type="hidden" name="refeicao_id" id="form_refeicao_id">
                <input type="hidden" name="acao" id="form_acao" value="salvar">

                <div class="form-group">
                    <label for="tipo_refeicao">Tipo de Refeição:</label>
                    <select name="tipo_refeicao" id="tipo_refeicao">
                        <option value="Café da Manhã">Café da Manhã</option>
                        <option value="Almoço">Almoço</option>
                        <option value="Café da Tarde">Café da Tarde</option>
                        <option value="Jantar">Jantar</option>
                        <option value="Lanche">Lanche</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="metodo_adicao_dashboard">Como deseja adicionar?</label>
                    <select id="metodo_adicao_dashboard" onchange="alterarMetodoDashboard()">
                        <option value="ia"> Inteligência Artificial</option>
                        <option value="barcode">Código de barras</option>
                        <option value="favorita">Favoritas</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="descricao_comida">
                        O que você comeu?
                    </label>
                    <textarea name="descricao_comida" id="descricao_comida" rows="4"
                        placeholder="Ex: 2 ovos mexidos, 1 fatia de pão integral..." required></textarea>
                </div>

                <!-- =============================== -->
                <!-- CÓDIGO DE BARRAS -->
                <!-- =============================== -->

                <div id="areaBarcodeDashboard" style="display:none;">

                    <div class="form-group">
                        <label for="codigo_barras_dashboard">
                            Código de barras:
                        </label>

                        <div style="display:flex; gap:8px;">
                            <input type="text" id="codigo_barras_dashboard" inputmode="numeric" maxlength="14"
                                placeholder="Digite o código">
                            <button type="button" id="btnBuscarBarcodeDashboard"
                                style="padding:10px 15px; border:none; background:#10ac84; color:white; border-radius:6px; cursor:pointer; font-weight:bold;">
                                Pesquisar
                            </button>
                        </div>
                    </div>

                    <div id="produtoBarcodeDashboard"
                        style="display:none; background:#f5f7fa; border-radius:8px; padding:15px; margin-bottom:15px;">
                        <strong id="barcodeNomeDashboard"></strong>
                        <div id="barcodeMarcaDashboard" style="margin-top:5px;color:#666;"></div>
                        <div id="barcodeBaseDashboard" style="margin-top:5px;color:#666;"></div>
                    </div>

                    <div id="quantidadeBarcodeDashboard" class="form-group" style="display:none;">
                        <label for="quantidade_dashboard"> Quantidade consumida:</label>
                        <div style="display:flex;gap:8px;align-items:center;">
                            <input type="number" maxlength="3000" id="quantidade_dashboard" min="0.1" step="0.1"
                                placeholder="Ex: 200">
                            <strong id="unidadeBarcodeDashboard">g</strong>
                        </div>
                    </div>

                    <div id="resultadoBarcodeDashboard"
                        style="display:none; background:#eefbf5; border-radius:8px; padding:15px; margin-bottom:15px;">
                        <strong>Nutrientes da quantidade informada</strong>
                        <p><span id="resultadoKcalDashboard">0</span> kcal</p>
                        <p><span id="resultadoProtDashboard">0</span> g proteína</p>
                        <p><span id="resultadoCarboDashboard">0</span> g carboidratos</p>
                        <p><span id="resultadoGordDashboard">0</span> g gordura</p>
                        <p><span id="resultadoAcucarDashboard">0</span> g açúcar</p>
                        <p><span id="resultadoFibraDashboard">0</span> g fibra</p>
                        <p><span id="resultadoSodioDashboard">0</span> mg sódio</p>
                    </div>

                    <input type="hidden" name="kcal" id="hiddenKcalDashboard">
                    <input type="hidden" name="proteina" id="hiddenProteinaDashboard">
                    <input type="hidden" name="carbo" id="hiddenCarboDashboard">
                    <input type="hidden" name="gordura" id="hiddenGorduraDashboard">
                    <input type="hidden" name="acucar" id="hiddenAcucarDashboard">
                    <input type="hidden" name="fibra" id="hiddenFibraDashboard">
                    <input type="hidden" name="sodio" id="hiddenSodioDashboard">
                    <input type="hidden" name="quantidade" id="hiddenQuantidadeDashboard">
                </div>

                <div id="sugestaoResultadoDashboard" class="sugestao-box" aria-live="polite" style="display:none;"></div>

                <div id="areaFavoritas" style="display:none;">
                    <div class="form-group">
                        <label>Escolha uma refeição favorita:</label>
                        <div id="listaFavoritas" class="lista-favoritas"></div>
                        <input type="hidden" name="favorita_id" id="favorita_id">
                    </div>
                    <div id="resumoFavorita" class="resumo-favorita" style="display:none;"></div>
                </div>

                <div class="modal-buttons">
                    <button type="button" class="btn btn-excluir-home" id="btnExcluirMeta" style="display:none;">Excluir</button>
                    <button type="button" class="btn btn-sugestao" id="btnSugestaoDashboard" style="display:none;">Sugestão inteligente</button>
                    <button type="button" class="btn btn-cancelar" id="btnFecharModal">Cancelar</button>
                    <button type="submit" class="btn btn-enviar" id="btnEnviarModal">Salvar e Calcular</button>
                </div>
            </form>
        </div>
        <aside class="modal-recado-nutria" role="note" aria-label="Dica para registrar sua refeição">
            <strong>Uma dica importante!</strong>
            <p>Informe as <b>quantidades</b> de cada alimento e especifique o <b>modo de preparo</b> (cozido, grelhado, assado ou frito). Assim, a estimativa nutricional fica mais precisa.</p>
            <img src="../../public/img/Nutri_chef.png" alt="Mascote NutrIA" onerror="this.style.display='none'">
        </aside>
        </div>
    </div>

    <script>
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

        /* ---------- Menu hambúrguer do header ---------- */
        (function() {
            const header = document.querySelector('.main-header');
            const menuToggle = document.getElementById('menuToggle');
            const menuBackdrop = document.getElementById('menuBackdrop');

            function setMenu(aberto) {
                header.classList.toggle('open', aberto);
                menuBackdrop.classList.toggle('show', aberto);
                menuToggle.setAttribute('aria-expanded', aberto);
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
        })();
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var modal = document.getElementById('modalRefeicao');
            var btnFechar = document.getElementById('btnFecharModal');
            var btnExcluir = document.getElementById('btnExcluirMeta');

            var inputData = document.getElementById('form_data');
            var inputId = document.getElementById('form_refeicao_id');
            var inputAcao = document.getElementById('form_acao');
            var txtTitulo = document.getElementById('modal_titulo');
            var txtDescricao = document.getElementById('descricao_comida');
            var selectTipo = document.getElementById('tipo_refeicao');

            // Normaliza texto (sem acento, sem espaços extras, minúsculo) para comparar tipos
            function normalizarTexto(txt) {
                return (txt || '')
                    .toString()
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .trim()
                    .toLowerCase();
            }

            // Seleciona a opção equivalente; se não achar, usa a primeira (nunca fica vazio)
            function selecionarTipo(valor) {
                var alvo = normalizarTexto(valor);
                var achou = false;

                Array.from(selectTipo.options).forEach(function(opt) {
                    if (!achou && normalizarTexto(opt.value) === alvo) {
                        selectTipo.value = opt.value;
                        achou = true;
                    }
                });

                if (!achou) selectTipo.selectedIndex = 0;
            }

            var macroCtx = document.getElementById('macroChart').getContext('2d');
            var macroChart;
            var ultimosArgsGrafico = null;

            const metasDiarias = <?php echo json_encode($metas); ?>;

            function atualizarBarraProgresso(elementId, valorAtual, valorMeta) {
                const barra = document.getElementById(elementId);
                if (!barra) return;

                const cardPai = barra.closest('.macro-item-list');

                let percentual = 0;
                if (valorMeta > 0) {
                    percentual = (valorAtual / valorMeta) * 100;
                }

                if (percentual > 100) {
                    barra.style.width = '100%';
                    barra.style.backgroundColor = '#ff2a2a';

                    if (cardPai) {
                        cardPai.style.boxShadow = '0 0 15px rgba(255, 42, 42, 0.75)';
                        cardPai.style.borderLeft = '6px solid #ff2a2a';
                    }
                } else {
                    if (percentual < 0) percentual = 0;
                    barra.style.width = percentual.toFixed(1) + '%';
                    barra.style.backgroundColor = 'var(--macro-color)';

                    if (cardPai) {
                        cardPai.style.boxShadow = '0 2px 4px rgba(0,0,0,0.04)';
                        cardPai.style.borderLeft = '3px solid var(--macro-color)';
                    }
                }
            }

            function atualizarGrafico(prot, carbo, gord, fibra, acucar, sodioMg) {
                ultimosArgsGrafico = [prot, carbo, gord, fibra, acucar, sodioMg];
                if (macroChart) macroChart.destroy();

                const sodioG = sodioMg / 1000;
                const totalGramas = prot + carbo + gord + fibra + acucar + sodioG;

                let dadosGrafico, coresGrafico, labelsGrafico;

                if (totalGramas > 0) {
                    dadosGrafico = [
                        ((prot / totalGramas) * 100).toFixed(1),
                        ((carbo / totalGramas) * 100).toFixed(1),
                        ((gord / totalGramas) * 100).toFixed(1),
                        ((fibra / totalGramas) * 100).toFixed(1),
                        ((acucar / totalGramas) * 100).toFixed(1),
                        ((sodioG / totalGramas) * 100).toFixed(1)
                    ];
                    const estilo = getComputedStyle(document.documentElement);
                    coresGrafico = ['--c-prot', '--c-carbo', '--c-gord', '--c-fibra', '--c-acucar', '--c-sodio']
                        .map(v => estilo.getPropertyValue(v).trim());
                    labelsGrafico = ['Proteínas', 'Carboidratos', 'Gorduras', 'Fibras', 'Açúcares', 'Sódio'];
                } else {
                    dadosGrafico = [100];
                    coresGrafico = [getComputedStyle(document.documentElement).getPropertyValue('--chart-empty').trim() || '#f0f0f0'];
                    labelsGrafico = ['Sem registros'];
                }

                macroChart = new Chart(macroCtx, {
                    type: 'doughnut',
                    data: {
                        labels: labelsGrafico,
                        datasets: [{
                            data: dadosGrafico,
                            backgroundColor: coresGrafico,
                            borderWidth: 1,
                            borderColor: '#ffffff'
                        }]
                    },
                    options: {
                        cutout: '70%',
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                enabled: true,
                                callbacks: {
                                    label: function(context) {
                                        if (totalGramas === 0) return ' Nenhuma refeição cadastrada';
                                        return ` ${context.label}: ${context.raw}%`;
                                    }
                                }
                            }
                        }
                    }
                });
            }

            document.addEventListener('nutria-tema', function() {
                if (ultimosArgsGrafico) atualizarGrafico.apply(null, ultimosArgsGrafico);
            });

            function carregarMacrosDoDia(dataStr) {
                var dataFormatada = dataStr.split('-').reverse().join('/');
                document.getElementById('label-data-selecionada').innerText = dataFormatada;

                fetch(`../controllers/get_macros_controller.php?data=${dataStr}`)
                    .then(response => response.json())
                    .then(dados => {
                        const kcalVal = dados.kcal || 0;
                        const protVal = dados.prot || 0;
                        const carboVal = dados.carbo || 0;
                        const gordVal = dados.gord || 0;
                        const fibraVal = dados.fibra || 0;
                        const acucarVal = dados.acucar || 0;
                        const sodioVal = dados.sodio || 0;

                        document.getElementById('res-kcal').innerText = kcalVal;
                        document.getElementById('res-prot').innerText = protVal;
                        document.getElementById('res-carbo').innerText = carboVal;
                        document.getElementById('res-gord').innerText = gordVal;
                        document.getElementById('res-fibra').innerText = fibraVal;
                        document.getElementById('res-acucar').innerText = acucarVal;
                        document.getElementById('res-sodio').innerText = sodioVal;

                        atualizarBarraProgresso('bar-kcal', kcalVal, metasDiarias.kcal);
                        atualizarBarraProgresso('bar-prot', protVal, metasDiarias.prot);
                        atualizarBarraProgresso('bar-carbo', carboVal, metasDiarias.carbo);
                        atualizarBarraProgresso('bar-gord', gordVal, metasDiarias.gord);
                        atualizarBarraProgresso('bar-fibra', fibraVal, metasDiarias.fibra);
                        atualizarBarraProgresso('bar-acucar', acucarVal, metasDiarias.acucar);
                        atualizarBarraProgresso('bar-sodio', sodioVal, metasDiarias.sodio);

                        atualizarGrafico(
                            parseFloat(protVal),
                            parseFloat(carboVal),
                            parseFloat(gordVal),
                            parseFloat(fibraVal),
                            parseFloat(acucarVal),
                            parseFloat(sodioVal)
                        );
                    });
            }

            var todosEventos = <?php echo json_encode($eventos_refeicoes); ?>;

            // Mantém a flag "favorita" dos eventos em dia com as estrelas
            window.atualizarEventosFavorita = function(chave, ativa) {
                todosEventos.forEach(function(ev) {
                    if (chaveFavorita(ev.description) === chave) ev.favorita = ativa;
                });
            };
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'pt-br',
                fixedWeekCount: false,
                headerToolbar: {
                    left: '',
                    center: 'prev title next',
                    right: ''
                },
                buttonText: {
                    prev: '<',
                    next: '>'
                },
                selectable: true,
                events: <?php echo json_encode($eventos_refeicoes); ?>,
                eventContent: function() {
                    return {
                        domNodes: []
                    };
                },
                dayCellDidMount: function(info) {
                    var dataStr = info.date.toISOString().split('T')[0];
                    var temRefeicao = todosEventos.some(function(ev) {
                        return ev.start === dataStr;
                    });
                    if (temRefeicao) {
                        var tri = document.createElement('div');
                        tri.className = 'nutria-corner-tri';
                        info.el.style.position = 'relative';
                        info.el.appendChild(tri);
                    }
                },

                dateClick: function(info) {
                    carregarMacrosDoDia(info.dateStr);
                    abrirModalListaDia(info.dateStr);
                },

                eventClick: function(info) {
                    var evento = info.event;
                    var dataStr = evento.startStr.split('T')[0];
                    carregarMacrosDoDia(dataStr);
                    abrirModalListaDia(dataStr);
                }
            });

            calendar.render();
            carregarMacrosDoDia(new Date().toISOString().split('T')[0]);

            var modalListaDia = document.getElementById('modalListaDia');
            var btnFecharLista = document.getElementById('btnFecharListaDia');
            var btnAdicionarDia = document.getElementById('btnAdicionarDia');
            var listaContainer = document.getElementById('listaRefeicoesDia');
            var modalListaTitulo = document.getElementById('modalListaTitulo');

            var coresTipo = {
                'Café da Manhã': '#10ac84',
                'Almoço': '#10ac84',
                'Jantar': '#10ac84',
                'Café da Tarde': '#10ac84'
            };

            function abrirModalListaDia(dataStr) {
                var dataFormatada = dataStr.split('-').reverse().join('/');
                modalListaTitulo.innerText = dataFormatada;

                var refDoDia = todosEventos.filter(function(ev) {
                    return ev.start === dataStr;
                });

                listaContainer.innerHTML = '';

                if (refDoDia.length === 0) {
                    var vazio = document.createElement('div');
                    vazio.className = 'sem-refeicoes-dia';
                    vazio.innerHTML = '<p>Nenhuma refeição registrada neste dia.</p>';
                    listaContainer.appendChild(vazio);
                } else {
                    refDoDia.forEach(function(ev) {
                        var cor = coresTipo[ev.title] || '#10ac84';
                        var card = document.createElement('button');
                        card.type = 'button';
                        card.className = 'refeicao-dia-card';
                        card.style.borderLeftColor = cor;
                        card.innerHTML =
                            '<div class="refeicao-dia-tipo" style="color:' + cor + ';">' + ev.title + '</div>' +
                            '<div class="refeicao-dia-descricao">' + (ev.description || '<em style="color:#aaa;">Sem descrição</em>') + '</div>';

                        card.addEventListener('click', function() {
                            modalListaDia.style.display = 'none';
                            inputData.value = dataStr;
                            inputId.value = ev.id;
                            var selMetodoEd = document.getElementById('metodo_adicao_dashboard');
                            selMetodoEd.value = 'ia';
                            alterarMetodoDashboard();
                            selMetodoEd.closest('.form-group').style.display = 'none';
                            inputAcao.value = 'editar';
                            txtTitulo.innerText = 'Editar Refeição (' + dataFormatada + ')';
                            txtDescricao.value = ev.description || '';
                            selecionarTipo(ev.title);
                            btnExcluir.style.display = '';
                            mostrarNutrientesRefeicao(ev);
                            document.getElementById('btnSugestaoDashboard').style.display = '';
                            document.getElementById('sugestaoResultadoDashboard').style.display = 'none';
                            document.getElementById('sugestaoResultadoDashboard').replaceChildren();
                            modal.style.display = 'flex';
                        });

                        var item = document.createElement('div');
                        item.className = 'refeicao-dia-item';
                        item.appendChild(card);

                        if (ev.description) {
                            var estrela = document.createElement('button');
                            estrela.type = 'button';
                            estrela.className = 'btn-favoritar';
                            estrela.dataset.refeicaoId = ev.id;
                            estrela.dataset.desc = ev.description;
                            definirEstadoFavorita(estrela, !!ev.favorita);
                            item.appendChild(estrela);
                        }

                        listaContainer.appendChild(item);
                    });
                }

                btnAdicionarDia.dataset.data = dataStr;
                btnAdicionarDia.dataset.dataFmt = dataFormatada;
                modalListaDia.style.display = 'flex';
            }

            btnAdicionarDia.addEventListener('click', function() {
                var dataStr = this.dataset.data;
                var dataFormatada = this.dataset.dataFmt;
                modalListaDia.style.display = 'none';
                inputData.value = dataStr;
                inputId.value = '';
                var selMetodo = document.getElementById('metodo_adicao_dashboard');
                selMetodo.closest('.form-group').style.display = 'block';
                selMetodo.value = 'ia';
                alterarMetodoDashboard();
                inputAcao.value = 'salvar';
                txtTitulo.innerText = 'Adicionar Refeição (' + dataFormatada + ')';
                txtDescricao.value = '';
                selectTipo.selectedIndex = 0;
                btnExcluir.style.display = 'none';
                esconderNutrientesRefeicao();
                document.getElementById('btnSugestaoDashboard').style.display = 'none';
                document.getElementById('sugestaoResultadoDashboard').style.display = 'none';
                document.getElementById('sugestaoResultadoDashboard').replaceChildren();
                modal.style.display = 'flex';
            });

            btnFecharLista.addEventListener('click', function() {
                modalListaDia.style.display = 'none';
            });
            window.addEventListener('click', function(e) {
                if (e.target === modalListaDia) modalListaDia.style.display = 'none';
            });

            const formRefeicaoDashboard = document.getElementById('formRefeicao');
            const btnEnviarDashboard = document.getElementById('btnEnviarModal');
            let envioRefeicaoEmAndamento = false;

            formRefeicaoDashboard.addEventListener('submit', async function(event) {
                event.preventDefault();
                if (envioRefeicaoEmAndamento) return;

                if (inputAcao.value === 'salvar_favorita' && !document.getElementById('favorita_id').value) {
                    mostrarToastDashboard('Escolha uma refeição favorita', 'aviso', 4000);
                    return;
                }

                envioRefeicaoEmAndamento = true;
                const textoOriginalBotao = btnEnviarDashboard.textContent;
                btnEnviarDashboard.disabled = true;
                btnEnviarDashboard.textContent = 'Processando...';

                const acaoEnvio = inputAcao.value;
                const controlador = new AbortController();
                const timerTimeout = setTimeout(() => controlador.abort(), 90000);

                try {
                    const resposta = await fetch(formRefeicaoDashboard.action, {
                        method: 'POST',
                        body: new FormData(formRefeicaoDashboard),
                        credentials: 'same-origin',
                        redirect: 'follow',
                        signal: controlador.signal
                    });

                    clearTimeout(timerTimeout);

                    if (resposta.redirected) {
                        const destino = new URL(resposta.url);
                        const temResultado = ['sucesso', 'erro', 'alert', 'alerta']
                            .some(param => destino.searchParams.has(param));
                        if (!temResultado) {
                            const codigoSucesso = acaoEnvio === 'excluir' ? 'excluido'
                                : (acaoEnvio === 'editar' ? 'atualizado' : 'adicionado');
                            destino.searchParams.set('sucesso', codigoSucesso);
                        }
                        window.location.href = destino.toString();
                        return;
                    }

                    if (!resposta.ok) {
                        mostrarToastDashboard(textoErroAcao(acaoEnvio), 'erro', 6000);
                        return;
                    }

                    const textoResposta = await resposta.text();
                    if (/Maximum execution time|Fatal error|Execution time/i.test(textoResposta)) {
                        mostrarToastDashboard('Tempo excedido. Tente novamente', 'aviso', 6000);
                    } else {
                        mostrarToastDashboard('Resposta inesperada do servidor. Confira se a refeição foi salva antes de tentar de novo', 'aviso', 6500);
                    }
                } catch (erroEnvio) {
                    clearTimeout(timerTimeout);
                    if (erroEnvio && erroEnvio.name === 'AbortError') {
                        mostrarToastDashboard('Tempo excedido. Tente com uma descrição mais curta', 'aviso', 6500);
                    } else {
                        mostrarToastDashboard('Sem conexão com o servidor. Verifique a internet e tente novamente', 'erro', 6000);
                    }
                } finally {
                    envioRefeicaoEmAndamento = false;
                    btnEnviarDashboard.disabled = false;
                    btnEnviarDashboard.textContent = textoOriginalBotao;
                }
            });

            btnExcluir.addEventListener('click', function() {
                if (confirm("Tem certeza que deseja remover esta refeição? Seus macros serão recalculados.")) {
                    inputAcao.value = "excluir";
                    formRefeicaoDashboard.requestSubmit();
                }
            });

            const MASCOTE_TOAST = '../../public/img/Nutri_chef.png';

            function textoErroAcao(acao) {
                if (acao === 'excluir') return 'Erro ao excluir a refeição';
                if (acao === 'editar') return 'Erro ao atualizar a refeição';
                return 'Erro ao adicionar a refeição';
            }

            function mostrarToastDashboard(mensagem, tipo = 'sucesso', duracao = 4500) {
                const container = document.getElementById('toastContainer');
                if (!container) return;

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
                msg.textContent = mensagem;

                const barra = document.createElement('div');
                barra.className = 'toast-barra';
                barra.innerHTML = '<span></span>';

                toast.append(img, msg, barra);
                container.appendChild(toast);

                const fechar = () => {
                    if (toast.classList.contains('saindo')) return;
                    toast.classList.add('saindo');
                    toast.addEventListener('animationend', () => toast.remove(), { once: true });
                    setTimeout(() => toast.remove(), 400);
                };
                toast.addEventListener('click', fechar);
                setTimeout(fechar, duracao);
            }

            window.mostrarToastDashboard = mostrarToastDashboard;

            (function tratarAlertasDashboard() {
                const url = new URL(window.location.href);
                const sucesso = url.searchParams.get('sucesso');
                const erro = url.searchParams.get('erro');
                const alerta = url.searchParams.get('alert') || url.searchParams.get('alerta');
                const codigo = (sucesso || erro || alerta || '').toLowerCase().trim();
                if (!codigo) return;

                const ADICIONADA = ['Refeição adicionada', 'sucesso'];
                const ATUALIZADA = ['Refeição atualizada', 'sucesso'];
                const EXCLUIDA = ['Refeição excluída', 'sucesso'];
                const TEMPO = ['Tempo excedido. Tente novamente', 'aviso'];

                const mensagens = {
                    adicionado: ADICIONADA, adicionada: ADICIONADA, salvo: ADICIONADA, salva: ADICIONADA,
                    refeicao_salva: ADICIONADA, refeicao_adicionada: ADICIONADA, '1': ADICIONADA,
                    atualizado: ATUALIZADA, atualizada: ATUALIZADA, editado: ATUALIZADA, editada: ATUALIZADA,
                    refeicao_editada: ATUALIZADA, refeicao_atualizada: ATUALIZADA,
                    excluido: EXCLUIDA, excluida: EXCLUIDA, refeicao_excluida: EXCLUIDA,
                    sucesso: ['Operação concluída', 'sucesso'],
                    nao_comivel: ['Não foi possível calcular os nutrientes. Digite um alimento ou refeição válida', 'aviso'],
                    campos_vazios: ['Preencha todos os campos antes de enviar', 'aviso'],
                    timeout: TEMPO, tempo_exedido: TEMPO, tempo_excedido: TEMPO,
                    ia_indisponivel: ['Análise indisponível no momento. Tente novamente', 'aviso'],
                    erro_adicionar: ['Erro ao adicionar a refeição', 'erro'],
                    erro_salvar: ['Erro ao adicionar a refeição', 'erro'],
                    erro_atualizar: ['Erro ao atualizar a refeição', 'erro'],
                    erro_editar: ['Erro ao atualizar a refeição', 'erro'],
                    erro_excluir: ['Erro ao excluir a refeição', 'erro'],
                    sem_conexao: ['Sem conexão com o serviço. Tente novamente', 'erro']
                };

                let resultado = mensagens[codigo];
                if (!resultado && /tempo|timeout|excedid|exedido/.test(codigo)) {
                    resultado = TEMPO;
                } else if (!resultado && /exclu/.test(codigo)) {
                    resultado = erro ? mensagens.erro_excluir : EXCLUIDA;
                } else if (!resultado && /edit|atualiz/.test(codigo)) {
                    resultado = erro ? mensagens.erro_atualizar : ATUALIZADA;
                } else if (!resultado && erro) {
                    resultado = ['Não foi possível concluir a operação. Tente novamente', 'erro'];
                } else if (!resultado && sucesso) {
                    resultado = mensagens.sucesso;
                } else if (!resultado) {
                    resultado = ['Confira os dados informados e tente novamente', 'aviso'];
                }

                mostrarToastDashboard(resultado[0], resultado[1], resultado[1] === 'sucesso' ? 4000 : 5500);
                ['sucesso', 'erro', 'alert', 'alerta'].forEach(param => url.searchParams.delete(param));
                window.history.replaceState({}, document.title, url.pathname + url.search + url.hash);
            })();

            /* ---------- Painel lateral: nutrientes da refeição ---------- */
            var painelNut = document.getElementById('painelNutrientes');

            function esconderNutrientesRefeicao() {
                painelNut.classList.remove('visivel');
                document.getElementById('nutConteudo').replaceChildren();
            }

            function mostrarNutrientesRefeicao(ev) {
                var conteudo = document.getElementById('nutConteudo');
                conteudo.replaceChildren();
                document.getElementById('nutSub').textContent = ev.title || '';

                var n = ev.nutrientes;
                if (!n) {
                    var vazio = document.createElement('p');
                    vazio.className = 'nut-vazio';
                    vazio.textContent = 'Esta refeição não tem dados nutricionais registrados.';
                    conteudo.appendChild(vazio);
                    painelNut.classList.add('visivel');
                    return;
                }

                var fmt = function(v, casas) { return Number(v || 0).toFixed(casas).replace('.', ','); };

                var kcalBox = document.createElement('div');
                kcalBox.className = 'nut-kcal';
                var kcalNum = document.createElement('strong');
                kcalNum.textContent = Math.round(n.kcal);
                var kcalTxt = document.createElement('span');
                kcalTxt.textContent = 'kcal';
                kcalBox.append(kcalNum, kcalTxt);
                conteudo.appendChild(kcalBox);

                var lista = document.createElement('div');
                lista.className = 'nut-lista';

                [
                    ['prot', 'Proteínas', 'g', 1],
                    ['carbo', 'Carboidratos', 'g', 1],
                    ['gord', 'Gorduras', 'g', 1],
                    ['fibra', 'Fibras', 'g', 1],
                    ['acucar', 'Açúcares', 'g', 1],
                    ['sodio', 'Sódio', 'mg', 0]
                ].forEach(function(c) {
                    var valor = n[c[0]] || 0;
                    var meta = metasDiarias[c[0]] || 0;
                    var pct = meta > 0 ? (valor / meta) * 100 : 0;

                    var item = document.createElement('div');
                    item.className = 'nut-item n-' + c[0];

                    var linha = document.createElement('div');
                    linha.className = 'nut-linha';
                    var nome = document.createElement('span');
                    nome.className = 'nut-nome';
                    nome.textContent = c[1];
                    var val = document.createElement('span');
                    val.className = 'nut-valor';
                    val.textContent = fmt(valor, c[3]) + ' ' + c[2];
                    linha.append(nome, val);

                    var barra = document.createElement('div');
                    barra.className = 'nut-barra';
                    var preenche = document.createElement('span');
                    barra.appendChild(preenche);

                    var info = document.createElement('div');
                    info.className = 'nut-meta';
                    info.textContent = fmt(pct, 0) + '% da meta diária';

                    item.append(linha, barra, info);
                    lista.appendChild(item);

                    // anima a barra depois de inserida no DOM
                    requestAnimationFrame(function() { preenche.style.width = Math.min(pct, 100) + '%'; });
                });

                conteudo.appendChild(lista);
                painelNut.classList.add('visivel');
            }

            function fecharModalRefeicaoDashboard() {
                modal.style.display = 'none';
                esconderNutrientesRefeicao();
                document.getElementById('btnSugestaoDashboard').style.display = 'none';
                const boxSugestao = document.getElementById('sugestaoResultadoDashboard');
                boxSugestao.style.display = 'none';
                boxSugestao.replaceChildren();
            }
            btnFechar.addEventListener('click', fecharModalRefeicaoDashboard);
            window.addEventListener('click', function(e) {
                if (e.target === modal) fecharModalRefeicaoDashboard();
            });
        });

        /* =====================================================
           CÓDIGO DE BARRAS 
        ===================================================== */
        let produtoBarcodeDashboard = null;

        function alterarMetodoDashboard() {
            const metodo = document.getElementById('metodo_adicao_dashboard').value;
            const descricao = document.getElementById('descricao_comida');
            const areaBarcode = document.getElementById('areaBarcodeDashboard');
            const areaFavoritas = document.getElementById('areaFavoritas');
            const acao = document.getElementById('form_acao');

            const grupoDescricao = descricao.parentElement;

            grupoDescricao.style.display = metodo === 'ia' ? 'block' : 'none';
            areaBarcode.style.display = metodo === 'barcode' ? 'block' : 'none';
            areaFavoritas.style.display = metodo === 'favorita' ? 'block' : 'none';

            descricao.required = metodo === 'ia';

            if (metodo === 'barcode') {
                acao.value = 'salvar_barcode';
            } else if (metodo === 'favorita') {
                acao.value = 'salvar_favorita';
            } else {
                acao.value = 'salvar';
            }

            // Zera a escolha e (re)desenha a lista de favoritas
            limparFavorita();
        }

        document
            .getElementById('btnBuscarBarcodeDashboard')
            .addEventListener('click', function() {
                const codigo = document.getElementById('codigo_barras_dashboard').value.trim();
                if (!codigo) {
                    window.mostrarToastDashboard('Digite um código de barras', 'aviso', 4000);
                    return;
                }

                const formData = new FormData();
                formData.append('codigo_barras', codigo);

                const botao = this;
                botao.disabled = true;
                botao.innerText = 'Buscando...';

                fetch('../controllers/alimento_controller.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(dados => {
                        if (!dados.sucesso) {
                            if (dados.erro === 'produto_nao_encontrado') {
                                window.mostrarToastDashboard('Produto não encontrado', 'aviso', 5000);
                            } else {
                                window.mostrarToastDashboard('Não foi possível encontrar os dados nutricionais', 'aviso', 5000);
                            }
                            return;
                        }

                        produtoBarcodeDashboard = dados;
                        document.getElementById('produtoBarcodeDashboard').style.display = 'block';
                        document.getElementById('barcodeNomeDashboard').innerText = dados.produto.nome;
                        document.getElementById('barcodeMarcaDashboard').innerText = dados.produto.marca ? 'Marca: ' + dados.produto.marca : '';
                        document.getElementById('barcodeBaseDashboard').innerText = 'Valores informados por ' + dados.produto.base_nutricional;
                        document.getElementById('unidadeBarcodeDashboard').innerText = dados.produto.unidade;
                        document.getElementById('quantidadeBarcodeDashboard').style.display = 'block';
                    })
                    .catch(error => {
                        console.error(error);
                        window.mostrarToastDashboard('Erro ao consultar o produto. Tente novamente', 'erro', 5500);
                    })
                    .finally(() => {
                        botao.disabled = false;
                        botao.innerText = 'Pesquisar';
                    });
            });

        document.getElementById('quantidade_dashboard').addEventListener('input', function() {
            if (!produtoBarcodeDashboard) return;

            const quantidade = parseFloat(this.value);

            if (isNaN(quantidade) || quantidade <= 0) {
                document.getElementById('resultadoBarcodeDashboard').style.display = 'none';
                return;
            }

            const fator = quantidade / 100;
            const n = produtoBarcodeDashboard.nutrientes;
            const kcal = parseFloat(n.kcal || 0) * fator;
            const proteina = parseFloat(n.proteina || 0) * fator;
            const carbo = parseFloat(n.carbo || 0) * fator;
            const gordura = parseFloat(n.gordura || 0) * fator;
            const acucar = parseFloat(n.acucar || 0) * fator;
            const fibra = parseFloat(n.fibra || 0) * fator;
            const sodio = parseFloat(n.sodio || 0) * fator;

            document.getElementById('resultadoKcalDashboard').innerText = kcal.toFixed(1);
            document.getElementById('resultadoProtDashboard').innerText = proteina.toFixed(1);
            document.getElementById('resultadoCarboDashboard').innerText = carbo.toFixed(1);
            document.getElementById('resultadoGordDashboard').innerText = gordura.toFixed(1);
            document.getElementById('resultadoAcucarDashboard').innerText = acucar.toFixed(1);
            document.getElementById('resultadoFibraDashboard').innerText = fibra.toFixed(1);
            document.getElementById('resultadoSodioDashboard').innerText = sodio.toFixed(1);
            document.getElementById('resultadoBarcodeDashboard').style.display = 'block';

            document.getElementById('hiddenKcalDashboard').value = kcal.toFixed(2);
            document.getElementById('hiddenProteinaDashboard').value = proteina.toFixed(2);
            document.getElementById('hiddenCarboDashboard').value = carbo.toFixed(2);
            document.getElementById('hiddenGorduraDashboard').value = gordura.toFixed(2);
            document.getElementById('hiddenAcucarDashboard').value = acucar.toFixed(2);
            document.getElementById('hiddenFibraDashboard').value = fibra.toFixed(2);
            document.getElementById('hiddenSodioDashboard').value = sodio.toFixed(2);
            document.getElementById('hiddenQuantidadeDashboard').value = quantidade;

            document.getElementById('descricao_comida').value = produtoBarcodeDashboard.produto.nome + ' - ' + quantidade + ' ' + produtoBarcodeDashboard.produto.unidade;
        });
    </script>
<script>
/* =====================================================
   SUGESTÃO INTELIGENTE (mesma lógica da Home)
===================================================== */
(function () {
    const btn = document.getElementById('btnSugestaoDashboard');
    const box = document.getElementById('sugestaoResultadoDashboard');
    if (!btn || !box) return;

    const SUGESTAO_URL = '../controllers/sugestao_controller.php';
    const ERROS_SUGESTAO = {
        timeout: 'A IA demorou para responder. Tente novamente.',
        sem_nutrientes: 'Essa refeição não tem valores nutricionais para comparar.',
        sugestao_fora_do_objetivo: 'A IA não conseguiu uma sugestão adequada ao seu objetivo. Tente de novo.',
        refeicao_nao_encontrada: 'Refeição não encontrada.',
        nao_logado: 'Sua sessão expirou. Entre novamente.'
    };

    function renderizarSugestao(d) {
        const el = (tag, cls, txt) => {
            const node = document.createElement(tag);
            if (cls) node.className = cls;
            if (txt !== undefined) node.textContent = txt;
            return node;
        };
        const lista = el('ul');
        (d.ingredientes || []).forEach(function (item) {
            lista.appendChild(el('li', '', item.quantidade ? item.quantidade + ' de ' + item.item : item.item));
        });
        const outra = el('button', 'sug-outra', '↻ Gerar outra sugestão');
        outra.type = 'button';
        outra.addEventListener('click', function () { pedirSugestao(true); });
        box.replaceChildren(
            el('h4', '', d.nome || 'Sugestão de refeição'),
            el('div', 'sug-meta', 'Objetivo: ' + (d.objetivo || 'não informado') + (d.categoria ? ' • ' + d.categoria : '')),
            lista,
            el('div', 'sug-kcal', '≈ ' + d.kcal_estimadas + ' kcal (a sua tem ' + d.kcal_original + ' kcal) • ≈ ' + d.proteina_estimada + ' g de proteína'),
            el('div', 'sug-motivo', d.motivo || ''),
            el('div', 'sug-aviso', 'Estimativa feita por IA. Sua refeição não foi alterada.'),
            outra
        );
        box.style.display = 'block';
    }

    async function pedirSugestao(nova) {
        const id = document.getElementById('form_refeicao_id').value;
        if (!id) {
            window.mostrarToastDashboard('Salve a refeição antes de solicitar uma sugestão.', 'aviso', 4000);
            return;
        }
        btn.disabled = true;
        btn.textContent = 'Pensando...';
        box.replaceChildren(document.createTextNode('Analisando sua refeição e seu objetivo...'));
        box.style.display = 'block';
        try {
            const fd = new FormData();
            fd.append('refeicao_id', id);
            if (nova) fd.append('nova', '1');
            const resp = await fetch(SUGESTAO_URL, { method: 'POST', body: fd, credentials: 'same-origin' });
            const dados = await resp.json();
            if (!resp.ok || dados.erro) {
                box.style.display = 'none';
                window.mostrarToastDashboard(ERROS_SUGESTAO[dados.erro] || 'Não foi possível gerar a sugestão agora.', 'erro', 5000);
                return;
            }
            renderizarSugestao(dados);
        } catch (e) {
            box.style.display = 'none';
            window.mostrarToastDashboard('Não foi possível gerar a sugestão agora.', 'erro', 5000);
        } finally {
            btn.disabled = false;
            btn.textContent = 'Sugestão inteligente';
        }
    }

    btn.addEventListener('click', function () { pedirSugestao(false); });
})();
</script>
<script>
/* =====================================================
   REFEIÇÕES FAVORITAS
===================================================== */
let FAVORITAS = <?php echo json_encode($favoritas, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
const FAVORITA_URL = '../controllers/favorita_controller.php';

function chaveFavorita(texto) {
    return (texto || '').trim().toLowerCase();
}

function avisoFavorita(texto, tipo) {
    const fn = window.mostrarToastDashboard || window.mostrarToast;
    if (typeof fn === 'function') fn(texto, tipo, 3500);
}

function definirEstadoFavorita(btn, ativa) {
    btn.classList.toggle('ativa', ativa);
    btn.setAttribute('aria-pressed', ativa ? 'true' : 'false');
    const texto = ativa ? 'Remover dos favoritos' : 'Favoritar refeição';
    btn.setAttribute('aria-label', texto);
    btn.title = texto;
    btn.textContent = '★';
}

// Atualiza todas as estrelas (e os eventos do calendário) que têm a mesma descrição
function sincronizarFavorita(descricao, ativa) {
    const chave = chaveFavorita(descricao);
    document.querySelectorAll('.btn-favoritar').forEach(function (b) {
        if (chaveFavorita(b.dataset.desc) === chave) definirEstadoFavorita(b, ativa);
    });
    if (typeof window.atualizarEventosFavorita === 'function') {
        window.atualizarEventosFavorita(chave, ativa);
    }
}

async function enviarFavorita(campos) {
    const fd = new FormData();
    Object.keys(campos).forEach(function (k) { fd.append(k, campos[k]); });
    const resp = await fetch(FAVORITA_URL, { method: 'POST', body: fd, credentials: 'same-origin' });
    const dados = await resp.json();
    if (!resp.ok || !dados.ok) throw new Error(dados.erro || 'erro');
    return dados;
}

async function alternarFavorita(btn) {
    if (btn.disabled) return;
    btn.disabled = true;
    try {
        const dados = await enviarFavorita({ acao: 'alternar', refeicao_id: btn.dataset.refeicaoId });

        if (dados.favorita) {
            FAVORITAS = FAVORITAS.filter(function (f) { return f.id !== dados.favorito.id; });
            FAVORITAS.unshift(dados.favorito);
        } else {
            FAVORITAS = FAVORITAS.filter(function (f) { return f.id !== dados.removido_id; });
        }

        sincronizarFavorita(btn.dataset.desc, dados.favorita);
        renderizarFavoritas();
        avisoFavorita(dados.favorita ? 'Refeição adicionada aos favoritos' : 'Refeição removida dos favoritos', 'sucesso');
    } catch (err) {
        avisoFavorita('Não foi possível atualizar os favoritos', 'erro');
    } finally {
        btn.disabled = false;
    }
}

async function removerFavorita(id) {
    const fav = FAVORITAS.find(function (f) { return f.id === id; });
    if (!fav) return;
    try {
        await enviarFavorita({ acao: 'remover', favorita_id: id });

        FAVORITAS = FAVORITAS.filter(function (f) { return f.id !== id; });
        const campo = document.getElementById('favorita_id');
        if (campo && String(campo.value) === String(id)) campo.value = '';

        sincronizarFavorita(fav.descricao, false);
        renderizarFavoritas();
        avisoFavorita('Favorita removida', 'sucesso');
    } catch (err) {
        avisoFavorita('Não foi possível remover a favorita', 'erro');
    }
}

function selecionarFavorita(id) {
    const campo = document.getElementById('favorita_id');
    if (!campo) return;
    campo.value = String(campo.value) === String(id) ? '' : String(id);
    renderizarFavoritas();
}

function atualizarResumoFavorita() {
    const resumo = document.getElementById('resumoFavorita');
    const campo = document.getElementById('favorita_id');
    if (!resumo || !campo) return;

    const fav = FAVORITAS.find(function (f) { return String(f.id) === String(campo.value); });
    if (!fav) {
        resumo.style.display = 'none';
        resumo.textContent = '';
        return;
    }

    const n = function (v) { return Number(v || 0).toFixed(1).replace('.', ','); };
    resumo.textContent = 'Nutrientes: ' + Math.round(fav.calorias) + ' kcal · ' +
        n(fav.proteina) + ' g proteína · ' + n(fav.carbo) + ' g carboidratos · ' +
        n(fav.gordura) + ' g gordura · ' + n(fav.acucar) + ' g açúcar · ' +
        n(fav.fibra) + ' g fibra · ' + Math.round(fav.sodio) + ' mg sódio';
    resumo.style.display = 'block';
}

function renderizarFavoritas() {
    const box = document.getElementById('listaFavoritas');
    const campo = document.getElementById('favorita_id');
    if (!box || !campo) return;

    box.innerHTML = '';

    if (!FAVORITAS.length) {
        const vazio = document.createElement('p');
        vazio.className = 'favoritas-vazio';
        vazio.textContent = 'Você ainda não tem refeições favoritas. Registre uma refeição e toque na ★ para favoritá-la.';
        box.appendChild(vazio);
        atualizarResumoFavorita();
        return;
    }

    FAVORITAS.forEach(function (f) {
        const selecionada = String(f.id) === String(campo.value);

        const item = document.createElement('div');
        item.className = 'favorita-item' + (selecionada ? ' selecionada' : '');

        const escolher = document.createElement('button');
        escolher.type = 'button';
        escolher.className = 'favorita-escolher';
        escolher.setAttribute('aria-pressed', selecionada ? 'true' : 'false');

        const desc = document.createElement('span');
        desc.className = 'favorita-desc';
        desc.textContent = f.descricao;

        const kcal = document.createElement('span');
        kcal.className = 'favorita-kcal';
        kcal.textContent = Math.round(f.calorias) + ' kcal';

        escolher.append(desc, kcal);
        escolher.addEventListener('click', function () { selecionarFavorita(f.id); });

        const remover = document.createElement('button');
        remover.type = 'button';
        remover.className = 'favorita-remover';
        remover.textContent = '×';
        remover.title = 'Remover dos favoritos';
        remover.setAttribute('aria-label', 'Remover dos favoritos');
        remover.addEventListener('click', function () { removerFavorita(f.id); });

        item.append(escolher, remover);
        box.appendChild(item);
    });

    atualizarResumoFavorita();
}

function limparFavorita() {
    const campo = document.getElementById('favorita_id');
    if (campo) campo.value = '';
    renderizarFavoritas();
}

document.addEventListener('click', function (e) {
    const btn = e.target.closest ? e.target.closest('.btn-favoritar') : null;
    if (!btn) return;
    e.preventDefault();
    e.stopPropagation();
    alternarFavorita(btn);
});
</script>
</body>

</html>