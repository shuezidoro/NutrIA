<?php
session_start();

// Bloqueia acessos de usuários deslogados
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/../config/conexao.php';

$usuario_id = $_SESSION['usuario_id'];
$usuario_nome = $_SESSION['usuario_nome'] ?? 'Usuário';
$dataHoje = date('Y-m-d');

// ==========================================
// BUSCA AS METAS DO USUÁRIO (DIÁRIAS)
// ==========================================

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
    $stmtMetas = $pdo->prepare("
        SELECT 
            kcal_meta,
            proteina_meta,
            carbo_meta,
            gordura_meta
        FROM metas_diarias
        WHERE usuario_id = ?
        ORDER BY id DESC
        LIMIT 1
    ");

    $stmtMetas->execute([$usuario_id]);
    $userMeta = $stmtMetas->fetch(PDO::FETCH_ASSOC);

    if ($userMeta) {
        $metas['kcal'] = !empty($userMeta['kcal_meta']) ? intval($userMeta['kcal_meta']) : 2000;
        $metas['prot'] = !empty($userMeta['proteina_meta']) ? floatval($userMeta['proteina_meta']) : 120;
        $metas['carbo'] = !empty($userMeta['carbo_meta']) ? floatval($userMeta['carbo_meta']) : 250;
        $metas['gord'] = !empty($userMeta['gordura_meta']) ? floatval($userMeta['gordura_meta']) : 65;
    }
} catch (Exception $e) {
    error_log("Erro ao buscar metas do Home: " . $e->getMessage());
}

// ==========================================
// BUSCA OS CONSUMOS DE HOJE
// ==========================================
$consumo = [
    'kcal' => 0,
    'prot' => 0,
    'carbo' => 0,
    'gord' => 0,
    'fibra' => 0,
    'acucar' => 0,
    'sodio' => 0
];

try {
    $stmtConsumo = $pdo->prepare("
        SELECT 
            SUM(n.calorias_obtidas) as total_kcal,
            SUM(n.proteina_obtida) as total_prot,
            SUM(n.carbo_obtido) as total_carbo,
            SUM(n.gordura_obtida) as total_gord,
            SUM(n.fibra_obtida) as total_fibra,
            SUM(n.acucar_obtido) as total_acucar,
            SUM(n.sodio_obtido) as total_sodio
        FROM refeicao r
        INNER JOIN nutriente n ON r.id = n.refeicao_id
        WHERE r.usuario_id = ? AND DATE(r.data_consumo) = ?
    ");
    $stmtConsumo->execute([$usuario_id, $dataHoje]);
    $consumoDb = $stmtConsumo->fetch(PDO::FETCH_ASSOC);

    if ($consumoDb) {
        $consumo = [
            'kcal' => intval($consumoDb['total_kcal'] ?? 0),
            'prot' => floatval($consumoDb['total_prot'] ?? 0),
            'carbo' => floatval($consumoDb['total_carbo'] ?? 0),
            'gord' => floatval($consumoDb['total_gord'] ?? 0),
            'fibra' => floatval($consumoDb['total_fibra'] ?? 0),
            'acucar' => floatval($consumoDb['total_acucar'] ?? 0),
            'sodio' => floatval($consumoDb['total_sodio'] ?? 0)
        ];
    }
} catch (Exception $e) {
    error_log("Erro ao buscar consumo: " . $e->getMessage());
}

// ==========================================
// CALCULA PORCENTAGENS
// ==========================================
function calcularPorcentagem($consumo, $meta)
{
    if ($meta <= 0) return 0;
    return min(100, round(($consumo / $meta) * 100));
}

$porcentagens = [
    'kcal' => calcularPorcentagem($consumo['kcal'], $metas['kcal']),
    'prot' => calcularPorcentagem($consumo['prot'], $metas['prot']),
    'carbo' => calcularPorcentagem($consumo['carbo'], $metas['carbo']),
    'gord' => calcularPorcentagem($consumo['gord'], $metas['gord']),
    'fibra' => calcularPorcentagem($consumo['fibra'], $metas['fibra']),
    'acucar' => calcularPorcentagem($consumo['acucar'], $metas['acucar']),
    'sodio' => calcularPorcentagem($consumo['sodio'], $metas['sodio'])
];

// ==========================================
// BUSCA REFEIÇÕES DE HOJE
// ==========================================
$refeicoes = [];
try {
    $stmtRef = $pdo->prepare("
        SELECT r.id, r.tipo_refeicao, r.descricao_texto, 
               SUM(n.calorias_obtidas) as kcal_refeicao,
               SUM(n.proteina_obtida)  as prot_refeicao,
               SUM(n.carbo_obtido)     as carbo_refeicao,
               SUM(n.gordura_obtida)   as gord_refeicao,
               SUM(n.fibra_obtida)     as fibra_refeicao,
               SUM(n.acucar_obtido)    as acucar_refeicao,
               SUM(n.sodio_obtido)     as sodio_refeicao
        FROM refeicao r
        LEFT JOIN nutriente n ON r.id = n.refeicao_id
        WHERE r.usuario_id = ? AND DATE(r.data_consumo) = ?
        GROUP BY r.id
        ORDER BY r.data_consumo DESC
    ");
    $stmtRef->execute([$usuario_id, $dataHoje]);
    $refeicoes = $stmtRef->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Erro ao buscar refeições: " . $e->getMessage());
}

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

// ==========================================
// FUNÇÃO AUXILIAR: Cor da barra de progresso
// ==========================================
function getCor($porcentagem)
{
    if ($porcentagem <= 100) return 'var(--green)';
    return '#ef4444';
}
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
    <link rel="icon" type="image/svg+xml" href="../../public/img/logo.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- GOOGLE MATERIAL SYMBOLS -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />

    <title>Início - NutrIA</title>

<style>
.macro-label {
  display: flex;
  align-items: center;
  gap: 6px;
}
.macro-label .material-symbols-outlined {
  font-size: 16px;
  color: var(--green);
}

.modal-refeicao-layout {
  display: flex; 
  align-items: stretch; 
  justify-content: center; 
  gap: 16px;
  width: 100%;
  max-width: 1000px;
  margin: 0 auto;
}
/* Painel lateral esquerdo: nutrientes da refeição em edição */
.modal-nutrientes-refeicao {
  display: none; flex: 0 0 240px; align-self: flex-start; box-sizing: border-box;
  padding: 22px 18px; border-radius: 20px;
  background: var(--bg-card, #fff); color: var(--text, #173b29);
  border: 1px solid var(--border, #dfe7dd);
  box-shadow: var(--shadow-modal);
  font-family: inherit;
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

/* Tablets: painel ocupa a linha de cima e o formulário + dica ficam embaixo */
@media (min-width: 769px) and (max-width: 960px) {
  .modal-refeicao-layout { flex-wrap: wrap; }
  .modal-nutrientes-refeicao { flex: 1 1 100%; }
  .nut-lista { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px 16px; }
}
.modal-refeicao-layout > .modal-conteudo {
  flex: 1 1 340px; 
  min-width: 0;
  margin: 0 !important;
}
.modal-recado-nutria {
  position: relative; 
  flex: 0 0 240px; 
  min-height: 145px; 
  align-self: flex-start;
  box-sizing: border-box; 
  padding: 24px 18px 58px; 
  border-radius: 20px;
  background: var(--bg-card, #fff); 
  color: var(--text, #173b29);
  border: 1px solid var(--border, #dfe7dd);
  box-shadow: var(--shadow-modal);
  font-family: inherit;
}
.modal-recado-nutria strong { display:block; font-size: 14px; margin-bottom: 8px; color: var(--green, #1d633d); }
.modal-recado-nutria p { margin:0; font-size:12px; line-height:1.55; color:var(--text-soft, #587064); }
.modal-recado-nutria img { position:absolute; width:76px; height:auto; max-height:82px; object-fit:contain; right:8px; bottom:4px; }

@media (max-width: 768px) {
  .modal-refeicao-layout { 
    flex-direction: column; 
    align-items: center; 
    width: 100%; 
    max-height: 88vh; 
    overflow-y: auto; 
    gap: 12px;
  }
  .modal-refeicao-layout > .modal-conteudo { 
    flex: 0 0 auto; 
    width: 100% !important; 
  }
  #modalRefeicao .modal-nutrientes-refeicao,
  #modalEdicao .modal-nutrientes-refeicao { flex: 0 0 auto; width: 100%; padding: 16px; }
  .nut-lista { grid-template-columns: 1fr 1fr; gap: 12px 16px; }
  .modal-recado-nutria { 
    flex: 0 0 auto; 
    min-height: 110px; 
    width: 100%; 
    padding: 16px 90px 18px 16px; 
  }
  .modal-recado-nutria img { width:65px; right:12px; bottom:4px; }
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
.btn-favoritar.ativa { color: #facc15; opacity: 1; background: rgba(245, 180, 1, 0); border-color:rgba(245, 180, 1, 0)); }
.btn-favoritar:disabled { cursor: wait; opacity: .4; transform: none; }

/* Home: estrela no canto do card da refeição */
.refeicao-item { position: relative; }
.refeicao-item .btn-favoritar { position: absolute; top: 10px; right: 10px; }

/* Edição de refeição (clique no card da home) */
.refeicao-editavel { cursor: pointer; }
.refeicao-editavel:focus-visible { outline: 2px solid rgba(127,127,127,.7); outline-offset: 2px; }
.btn-excluir-home {
  margin-right: auto;
  background: rgba(238,82,83,.12); color: #ee5253; border: 1px solid rgba(238,82,83,.4);
}
.btn-excluir-home:hover { background: rgba(238,82,83,.22); }

/* Sugestão inteligente */
.btn-sugestao {
  background: var(--tint); color: var(--green); border: 1px solid var(--border-green);
}
.btn-sugestao:disabled { opacity: .6; cursor: wait; }
.sugestao-box {
  display: none; background: var(--tint); border: 1px solid var(--border-green);
  border-radius: 8px; padding: 15px; margin-bottom: 15px;
}
.sugestao-box h4 { margin: 0 0 4px; font-size: 15px; color: var(--green); }
.sugestao-box .sug-meta { font-size: 12px; color: var(--text-soft); margin-bottom: 10px; }
.sugestao-box ul { margin: 0 0 10px; padding-left: 18px; font-size: 13px; line-height: 1.6; }
.sugestao-box .sug-kcal { font-weight: 600; font-size: 13px; margin-bottom: 8px; }
.sugestao-box .sug-motivo { font-size: 12px; color: var(--text-soft); margin-bottom: 8px; }
.sugestao-box .sug-aviso { font-size: 11px; color: var(--text-soft); opacity: .8; }
.sugestao-box .sug-outra {
  margin-top: 10px; background: transparent; border: 1px solid var(--border-green);
  color: var(--green); border-radius: 6px; padding: 6px 12px; cursor: pointer; font-size: 12px;
}

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
</head>

<body>

    <div id="toastContainer" role="status" aria-live="polite"></div>

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
            <a href="home.php" class="nav-item active">
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
        <div class="main">
            <!-- ESQUERDA: PROGRESSO DE HOJE -->
            <div class="card">
                <h2>Seus Macronutrientes de Hoje</h2>

                <div class="macros-grid">
                    <div class="macro-item kcal">
                        <div class="macro-label">
                            <span class="material-symbols-outlined">bolt</span>
                            Energia (kcal)
                        </div>
                        <div class="macro-progress">
                            <div class="macro-bar" style="width: <?php echo min(100, $porcentagens['kcal']); ?>%; background: <?php echo getCor($porcentagens['kcal']); ?>;"></div>
                        </div>
                        <div class="macro-values">
                            <span><strong><?php echo $consumo['kcal']; ?></strong> / <?php echo $metas['kcal']; ?></span>
                            <span><?php echo $porcentagens['kcal']; ?>%</span>
                        </div>
                    </div>

                    <div class="macro-item prot">
                        <div class="macro-label">
                            <span class="material-symbols-outlined">Weight</span>
                            Proteína
                        </div>
                        <div class="macro-progress">
                            <div class="macro-bar" style="width: <?php echo min(100, $porcentagens['prot']); ?>%; background: <?php echo getCor($porcentagens['prot']); ?>;"></div>
                        </div>
                        <div class="macro-values">
                            <span><strong><?php echo number_format($consumo['prot'], 1, ',', '.'); ?></strong> / <?php echo number_format($metas['prot'], 0, ',', '.'); ?></span>
                            <span><?php echo $porcentagens['prot']; ?>%</span>
                        </div>
                    </div>

                    <div class="macro-item carbo">
                        <div class="macro-label">
                            <span class="material-symbols-outlined">Wheat</span>
                            Carboidrato
                        </div>
                        <div class="macro-progress">
                            <div class="macro-bar" style="width: <?php echo min(100, $porcentagens['carbo']); ?>%; background: <?php echo getCor($porcentagens['carbo']); ?>;"></div>
                        </div>
                        <div class="macro-values">
                            <span><strong><?php echo number_format($consumo['carbo'], 1, ',', '.'); ?></strong> / <?php echo number_format($metas['carbo'], 0, ',', '.'); ?></span>
                            <span><?php echo $porcentagens['carbo']; ?>%</span>
                        </div>
                    </div>

                    <div class="macro-item gord">
                        <div class="macro-label">
                            <span class="material-symbols-outlined">water_drop</span>
                            Gordura
                        </div>
                        <div class="macro-progress">
                            <div class="macro-bar" style="width: <?php echo min(100, $porcentagens['gord']); ?>%; background: <?php echo getCor($porcentagens['gord']); ?>;"></div>
                        </div>
                        <div class="macro-values">
                            <span><strong><?php echo number_format($consumo['gord'], 1, ',', '.'); ?></strong> / <?php echo number_format($metas['gord'], 0, ',', '.'); ?></span>
                            <span><?php echo $porcentagens['gord']; ?>%</span>
                        </div>
                    </div>

                    <div class="macro-item fibra">
                        <div class="macro-label">
                            <span class="material-symbols-outlined">eco</span>
                            Fibra
                        </div>
                        <div class="macro-progress">
                            <div class="macro-bar" style="width: <?php echo min(100, $porcentagens['fibra']); ?>%; background: <?php echo getCor($porcentagens['fibra']); ?>;"></div>
                        </div>
                        <div class="macro-values">
                            <span><strong><?php echo number_format($consumo['fibra'], 1, ',', '.'); ?></strong> / <?php echo number_format($metas['fibra'], 0, ',', '.'); ?></span>
                            <span><?php echo $porcentagens['fibra']; ?>%</span>
                        </div>
                    </div>

                    <div class="macro-item acucar">
                        <div class="macro-label">
                            <span class="material-symbols-outlined">cookie</span>
                            Açúcar
                        </div>
                        <div class="macro-progress">
                            <div class="macro-bar" style="width: <?php echo min(100, $porcentagens['acucar']); ?>%; background: <?php echo getCor($porcentagens['acucar']); ?>;"></div>
                        </div>
                        <div class="macro-values">
                            <span><strong><?php echo number_format($consumo['acucar'], 1, ',', '.'); ?></strong> / <?php echo number_format($metas['acucar'], 0, ',', '.'); ?></span>
                            <span><?php echo $porcentagens['acucar']; ?>%</span>
                        </div>
                    </div>

                    <div class="macro-item sodio">
                        <div class="macro-label">
                            <span class="material-symbols-outlined">Salinity</span>
                            Sódio (mg)
                        </div>
                        <div class="macro-progress">
                            <div class="macro-bar" style="width: <?php echo min(100, $porcentagens['sodio']); ?>%; background: <?php echo getCor($porcentagens['sodio']); ?>;"></div>
                        </div>
                        <div class="macro-values">
                            <span><strong><?php echo number_format($consumo['sodio'], 0, ',', '.'); ?></strong> / <?php echo number_format($metas['sodio'], 0, ',', '.'); ?></span>
                            <span><?php echo $porcentagens['sodio']; ?>%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- DIREITA: REFEIÇÕES DE HOJE -->
            <div class="card">
                <div class="titulo-refeicoes">
                    <h2>Refeições de Hoje</h2>
                    <button type="button" class="btn-add-refeicao" onclick="abrirModal()">Adicionar Refeição</button>
                </div>

                <?php if (!empty($refeicoes)): ?>
                    <div class="lista-refeicoes">
                        <?php foreach ($refeicoes as $ref): ?>
                            <?php
                            // Nutrientes da refeição para o painel lateral do modal de edição
                            $nutAttr = '';
                            if ($ref['kcal_refeicao'] !== null) {
                                $nutAttr = json_encode([
                                    'kcal'   => (float) $ref['kcal_refeicao'],
                                    'prot'   => (float) $ref['prot_refeicao'],
                                    'carbo'  => (float) $ref['carbo_refeicao'],
                                    'gord'   => (float) $ref['gord_refeicao'],
                                    'fibra'  => (float) $ref['fibra_refeicao'],
                                    'acucar' => (float) $ref['acucar_refeicao'],
                                    'sodio'  => (float) $ref['sodio_refeicao']
                                ]);
                            }
                            ?>
                            <div class="refeicao-item refeicao-editavel" tabindex="0"
                                data-id="<?php echo (int) $ref['id']; ?>"
                                data-nutrientes="<?php echo htmlspecialchars($nutAttr, ENT_QUOTES, 'UTF-8'); ?>"
                                data-tipo="<?php echo htmlspecialchars($ref['tipo_refeicao'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                data-descricao="<?php echo htmlspecialchars($ref['descricao_texto'], ENT_QUOTES, 'UTF-8'); ?>"
                                title="Clique para editar ou excluir">
                                <div class="refeicao-tipo">
                                    <?php
                                    $tipos = [
                                        'Café da manhã' => 'Café da Manhã',
                                        'Almoço' => 'Almoço',
                                        'Café da tarde' => 'Café da Tarde'
                                    ];
                                    echo $tipos[$ref['tipo_refeicao']] ?? $ref['tipo_refeicao'];
                                    ?>
                                </div>
                                <div class="refeicao-descricao">
                                    <?php echo htmlspecialchars($ref['descricao_texto']); ?>
                                </div>
                                <div class="refeicao-kcal">
                                    <?php echo intval($ref['kcal_refeicao'] ?? 0); ?> kcal
                                </div>
                                <?php $ehFav = isset($favChaves[mb_strtolower(trim($ref['descricao_texto']))]); ?>
                                <button type="button"
                                    class="btn-favoritar<?php echo $ehFav ? ' ativa' : ''; ?>"
                                    data-refeicao-id="<?php echo (int) $ref['id']; ?>"
                                    data-desc="<?php echo htmlspecialchars($ref['descricao_texto'], ENT_QUOTES, 'UTF-8'); ?>"
                                    aria-pressed="<?php echo $ehFav ? 'true' : 'false'; ?>"
                                    aria-label="<?php echo $ehFav ? 'Remover dos favoritos' : 'Favoritar refeição'; ?>"
                                    title="<?php echo $ehFav ? 'Remover dos favoritos' : 'Favoritar refeição'; ?>">★</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="refeicoes-vazio">
                        <img src="../../public/img/pratoSemRef.svg" alt="Nenhuma refeição registrada" class="ilustracao-prato-vazio">
                        <h3>Nenhuma refeição registrada</h3>
                        <p>Adicione sua primeira refeição do dia para começar.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- MODAL ADICIONAR REFEIÇÃO -->
    <div id="modalRefeicao" class="modal">
        <div class="modal-refeicao-layout">
            <div class="modal-conteudo">
                <div class="modal-header" id="modalTitulo">
                    Adicionar Refeição
                </div>

                <form method="POST" action="../controllers/refeicao_controller.php" id="formRefeicao">
                    <input type="hidden" name="acao" id="barcode_acao" value="salvar">
                    <input type="hidden" name="retorno" value="home">
                    <input type="hidden" name="data_refeicao" value="<?php echo date('Y-m-d'); ?>">

                    <div class="form-group">
                        <label for="tipo">Tipo de Refeição:</label>
                        <select name="tipo_refeicao" id="tipo" required>
                            <option value="Café da manhã">Café da Manhã</option>
                            <option value="Almoço">Almoço</option>
                            <option value="Café da tarde">Café da Tarde</option>
                            <option value="Jantar">Jantar</option>
                            <option value="Lanche">Lanche</option>
                        </select>
                    </div>

                    <div class="form-group" id="grupoMetodo">
                        <label>Como deseja adicionar?</label>
                        <select id="metodo_adicao" onchange="alterarMetodoHome()">
                            <option value="ia">Inteligência Artificial</option>
                            <option value="barcode">Código de barras</option>
                            <option value="favorita">Favoritas</option>
                        </select>
                    </div>

                    <div id="areaIA">
                        <div class="form-group">
                            <label for="descricao">Descrição da Refeição:</label>
                            <textarea name="descricao_comida" id="descricao" placeholder="Sua refeição" required></textarea>
                        </div>
                    </div>

                    <div id="areaBarcode" style="display:none;">
                        <div class="form-group">
                            <label for="codigo_barras">Código de barras:</label>
                            <div style="display:flex; gap:8px;">
                                <input type="text" id="codigo_barras" inputmode="numeric" maxlength="14" placeholder="Digite o código">
                                <button type="button" class="btn btn-enviar" onclick="buscarProdutoHome()">Pesquisar</button>
                            </div>
                        </div>
                    </div>

                    <div id="areaFavoritas" style="display:none;">
                        <div class="form-group">
                            <label>Escolha uma refeição favorita:</label>
                            <div id="listaFavoritas" class="lista-favoritas"></div>
                            <input type="hidden" name="favorita_id" id="favorita_id">
                        </div>
                        <div id="resumoFavorita" class="resumo-favorita" style="display:none;"></div>
                    </div>

                    <div id="mensagemBarcode" style="display:none;" role="alert"></div>

                    <div id="produtoBarcode" style="display:none; background:var(--field-bg); border-radius:8px; padding:15px; margin-bottom:15px; border:1px solid var(--border);">
                        <strong id="barcodeNome">Produto</strong>
                        <div id="barcodeMarca" style="margin-top:5px; color:var(--text-soft);"></div>
                        <div id="barcodeBase" style="margin-top:5px; color:var(--text-soft);"></div>
                    </div>

                    <div id="quantidadeBarcode" class="form-group" style="display:none;">
                        <label for="quantidade">Quantidade consumida:</label>
                        <div style="display:flex; gap:8px; align-items:center;">
                            <input type="number" id="quantidade" min="0.1" step="0.1" maxlength="3000" placeholder="Ex: 200">
                            <strong id="unidadeBarcode">g</strong>
                        </div>
                    </div>

                    <div id="resultadoBarcode" style="display:none; background:var(--tint); border-radius:8px; padding:15px; margin-bottom:15px; border:1px solid var(--border-green);">
                        <strong>Nutrientes da quantidade informada</strong>
                        <div style="margin-top:10px;">
                            <div><span id="resultadoKcal">0</span> kcal</div>
                            <div><span id="resultadoProt">0</span> g proteína</div>
                            <div><span id="resultadoCarbo">0</span> g carboidratos</div>
                            <div><span id="resultadoGord">0</span> g gordura</div>
                            <div><span id="resultadoAcucar">0</span> g açúcar</div>
                            <div><span id="resultadoFibra">0</span> g fibra</div>
                            <div><span id="resultadoSodio">0</span> mg sódio</div>
                        </div>
                    </div>

                    <input type="hidden" name="kcal" id="hiddenKcal">
                    <input type="hidden" name="proteina" id="hiddenProteina">
                    <input type="hidden" name="carbo" id="hiddenCarbo">
                    <input type="hidden" name="gordura" id="hiddenGordura">
                    <input type="hidden" name="acucar" id="hiddenAcucar">
                    <input type="hidden" name="fibra" id="hiddenFibra">
                    <input type="hidden" name="sodio" id="hiddenSodio">
                    <input type="hidden" name="quantidade" id="hiddenQuantidade">

                    <div class="modal-buttons">
                        <button type="button" class="btn btn-cancelar" onclick="fecharModal()">Cancelar</button>
                        <button type="submit" class="btn btn-enviar" id="btnSalvarBarcode">Adicionar Refeição</button>
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

    <!-- MODAL EDITAR REFEIÇÃO (editar, excluir e sugestão inteligente) -->
    <div id="modalEdicao" class="modal">
        <div class="modal-refeicao-layout">
            <aside class="modal-nutrientes-refeicao" id="painelNutrientes" aria-label="Nutrientes da refeição selecionada" aria-live="polite">
                <h4>Nutrientes da refeição</h4>
                <p class="nut-sub" id="nutSub"></p>
                <div id="nutConteudo"></div>
            </aside>
            <div class="modal-conteudo">
                <div class="modal-header" id="modalTituloEdicao">
                    Editar Refeição
                </div>

                <form method="POST" action="../controllers/refeicao_controller.php" id="formEdicao">
                    <input type="hidden" name="acao" id="edicao_acao" value="editar">
                    <input type="hidden" name="retorno" value="home">
                    <input type="hidden" name="refeicao_id" id="refeicao_id">
                    <input type="hidden" name="data_refeicao" value="<?php echo date('Y-m-d'); ?>">

                    <div class="form-group">
                        <label for="tipoEdicao">Tipo de Refeição:</label>
                        <select name="tipo_refeicao" id="tipoEdicao" required>
                            <option value="Café da manhã">Café da Manhã</option>
                            <option value="Almoço">Almoço</option>
                            <option value="Café da tarde">Café da Tarde</option>
                            <option value="Jantar">Jantar</option>
                            <option value="Lanche">Lanche</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="descricaoEdicao">Descrição da Refeição:</label>
                        <textarea name="descricao_comida" id="descricaoEdicao" placeholder="Sua refeição" required></textarea>
                    </div>

                    <div id="sugestaoResultado" class="sugestao-box" aria-live="polite"></div>

                    <div class="modal-buttons">
                        <button type="button" class="btn btn-excluir-home" id="btnExcluirRefeicao">Excluir</button>
                        <button type="button" class="btn btn-sugestao" id="btnSugestao">Sugestão inteligente</button>
                        <button type="button" class="btn btn-cancelar" onclick="fecharModalEdicao()">Cancelar</button>
                        <button type="submit" class="btn btn-enviar" id="btnSalvarEdicao">Salvar</button>
                    </div>
                </form>
            </div>

            <aside class="modal-recado-nutria" role="note" aria-label="Dica para editar sua refeição">
                <strong>Uma dica importante!</strong>
                <p>Ao salvar, a NutrIA recalcula os nutrientes pela descrição. Informe as <b>quantidades</b> e o <b>modo de preparo</b> (cozido, grelhado, assado ou frito) para uma estimativa mais precisa.</p>
                <img src="../../public/img/Nutri_chef.png" alt="Mascote NutrIA" onerror="this.style.display='none'">
            </aside>
        </div>
    </div>

    <script>
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

        const MASCOTE_TOAST = '../../public/img/Nutri_chef.png';

        const MENSAGENS_REFEICAO = {
            salva:          {texto: 'Refeição salva',                             tipo: 'sucesso'},
            tempo_excedido: {texto: 'Tempo excedido. Tente novamente',            tipo: 'aviso'},
            sem_conexao:    {texto: 'Sem conexão com o serviço. Tente novamente', tipo: 'erro'},
            ia_indisponivel:{texto: 'Análise indisponível. Tente novamente',      tipo: 'aviso'},
            invalido:       {texto: 'Descreva a refeição para salvar',            tipo: 'aviso'},
            atualizada:     {texto: 'Refeição atualizada',                       tipo: 'sucesso'},
            excluida:       {texto: 'Refeição excluída',                         tipo: 'sucesso'},
            nao_comivel:    {texto: 'Não foi possível calcular os nutrientes. Digite um alimento ou refeição válida', tipo: 'aviso'},
            favorita_invalida: {texto: 'Essa favorita não existe mais. Atualize a página', tipo: 'aviso'},
            erro:           {texto: 'Não foi possível salvar a refeição',         tipo: 'erro'}
        };

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

        function mostrarResultadoRefeicao(codigo) {
            const m = MENSAGENS_REFEICAO[codigo] || MENSAGENS_REFEICAO.erro;
            mostrarToast(m.texto, m.tipo, m.tipo === 'sucesso' ? 4000 : 5500);
        }

        (function () {
            const params = new URLSearchParams(window.location.search);
            let codigo = params.get('refeicao');

            let antes = null;
            let acao = null;
            try {
                antes = sessionStorage.getItem('nutria_refeicoes_antes');
                acao = sessionStorage.getItem('nutria_acao_refeicao');
                sessionStorage.removeItem('nutria_refeicoes_antes');
                sessionStorage.removeItem('nutria_acao_refeicao');
            } catch (err) {}

            // Resultado informado pelo controller (?sucesso=... ou ?erro=...)
            if (!codigo) {
                const sucesso = params.get('sucesso');
                const erro = params.get('erro');
                const codigosErro = {
                    nao_comivel: 'nao_comivel',
                    timeout: 'tempo_excedido',
                    ia: 'ia_indisponivel',
                    campos_vazios: 'invalido',
                    favorita_invalida: 'favorita_invalida'
                };

                if (sucesso === 'excluido') {
                    codigo = 'excluida';
                } else if (sucesso) {
                    codigo = acao === 'editar' ? 'atualizada' : 'salva';
                } else if (erro) {
                    codigo = codigosErro[erro] || 'erro';
                }
            }

            if (!codigo && antes !== null) {
                const agora = document.querySelectorAll('.refeicao-item').length;
                codigo = agora > parseInt(antes) ? 'salva' : 'erro';
            }

            if (codigo) {
                mostrarResultadoRefeicao(codigo);

                ['refeicao', 'sucesso', 'erro'].forEach(function (p) { params.delete(p); });
                const resto = params.toString();
                window.history.replaceState({}, document.title, window.location.pathname + (resto ? '?' + resto : ''));
            }
        })();

        const modal = document.getElementById('modalRefeicao');
        const modalEdicao = document.getElementById('modalEdicao');

        /* ---------- Painel lateral: nutrientes da refeição ---------- */
        const METAS_HOME = <?php echo json_encode($metas); ?>;
        const painelNut = document.getElementById('painelNutrientes');

        function esconderNutrientesRefeicao() {
            painelNut.classList.remove('visivel');
            document.getElementById('nutConteudo').replaceChildren();
        }

        function mostrarNutrientesRefeicao(tipo, json) {
            const conteudo = document.getElementById('nutConteudo');
            conteudo.replaceChildren();
            document.getElementById('nutSub').textContent = tipo || '';

            let n = null;
            try { n = json ? JSON.parse(json) : null; } catch (e) { n = null; }

            if (!n) {
                const vazio = document.createElement('p');
                vazio.className = 'nut-vazio';
                vazio.textContent = 'Esta refeição não tem dados nutricionais registrados.';
                conteudo.appendChild(vazio);
                painelNut.classList.add('visivel');
                return;
            }

            const fmt = (v, casas) => Number(v || 0).toFixed(casas).replace('.', ',');

            const kcalBox = document.createElement('div');
            kcalBox.className = 'nut-kcal';
            const kcalNum = document.createElement('strong');
            kcalNum.textContent = Math.round(n.kcal);
            const kcalTxt = document.createElement('span');
            kcalTxt.textContent = 'kcal';
            kcalBox.append(kcalNum, kcalTxt);
            conteudo.appendChild(kcalBox);

            const lista = document.createElement('div');
            lista.className = 'nut-lista';

            [
                ['prot', 'Proteínas', 'g', 1],
                ['carbo', 'Carboidratos', 'g', 1],
                ['gord', 'Gorduras', 'g', 1],
                ['fibra', 'Fibras', 'g', 1],
                ['acucar', 'Açúcares', 'g', 1],
                ['sodio', 'Sódio', 'mg', 0]
            ].forEach(function (c) {
                const valor = n[c[0]] || 0;
                const meta = METAS_HOME[c[0]] || 0;
                const pct = meta > 0 ? (valor / meta) * 100 : 0;

                const item = document.createElement('div');
                item.className = 'nut-item n-' + c[0];

                const linha = document.createElement('div');
                linha.className = 'nut-linha';
                const nome = document.createElement('span');
                nome.className = 'nut-nome';
                nome.textContent = c[1];
                const val = document.createElement('span');
                val.className = 'nut-valor';
                val.textContent = fmt(valor, c[3]) + ' ' + c[2];
                linha.append(nome, val);

                const barra = document.createElement('div');
                barra.className = 'nut-barra';
                const preenche = document.createElement('span');
                barra.appendChild(preenche);

                const info = document.createElement('div');
                info.className = 'nut-meta';
                info.textContent = fmt(pct, 0) + '% da meta diária';

                item.append(linha, barra, info);
                lista.appendChild(item);

                requestAnimationFrame(function () { preenche.style.width = Math.min(pct, 100) + '%'; });
            });

            conteudo.appendChild(lista);
            painelNut.classList.add('visivel');
        }

        function limparDadosBarcode() {
            produtoBarcodeHome = null;
            document.getElementById('produtoBarcode').style.display = 'none';
            document.getElementById('quantidadeBarcode').style.display = 'none';
            document.getElementById('resultadoBarcode').style.display = 'none';
            
            const msg = document.getElementById('mensagemBarcode');
            msg.style.display = 'none';
            msg.innerHTML = '';
            msg.className = '';

            document.getElementById('hiddenKcal').value = '';
            document.getElementById('hiddenProteina').value = '';
            document.getElementById('hiddenCarbo').value = '';
            document.getElementById('hiddenGordura').value = '';
            document.getElementById('hiddenAcucar').value = '';
            document.getElementById('hiddenFibra').value = '';
            document.getElementById('hiddenSodio').value = '';
            document.getElementById('hiddenQuantidade').value = '';
        }

        // Normaliza texto (sem acento, sem espaços extras, minúsculo) para comparar tipos
        function normalizarTipo(txt) {
            return (txt || '')
                .toString()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .trim()
                .toLowerCase();
        }

        // Seleciona o tipo equivalente; se não achar, usa a primeira opção (nunca fica vazio)
        function selecionarTipoHome(valor) {
            const select = document.getElementById('tipoEdicao');
            const alvo = normalizarTipo(valor);
            let achou = false;

            Array.from(select.options).forEach(function (opt) {
                if (!achou && normalizarTipo(opt.value) === alvo) {
                    select.value = opt.value;
                    achou = true;
                }
            });

            if (!achou) select.selectedIndex = 0;
        }

        // Modal de cadastro: só adiciona (IA, código de barras ou favoritas)
        function abrirModal() {
            modal.classList.add('ativo');
        }

        // Modal de edição: editar, excluir e pedir sugestão de uma refeição já salva
        function abrirModalEdicao(id, tipo, descricao, nutrientesJson) {
            document.getElementById('refeicao_id').value = id;
            document.getElementById('edicao_acao').value = 'editar';

            const campoDescricao = document.getElementById('descricaoEdicao');
            campoDescricao.required = true;
            campoDescricao.value = descricao;
            selecionarTipoHome(tipo);

            const boxSug = document.getElementById('sugestaoResultado');
            boxSug.style.display = 'none';
            boxSug.replaceChildren();

            mostrarNutrientesRefeicao(tipo, nutrientesJson);
            modalEdicao.classList.add('ativo');
        }

        document.querySelectorAll('.refeicao-editavel').forEach(function (item) {
            function abrir() {
                abrirModalEdicao(item.dataset.id, item.dataset.tipo, item.dataset.descricao, item.dataset.nutrientes);
            }

            item.addEventListener('click', function (e) {
                if (e.target.closest('.btn-favoritar')) return;
                abrir();
            });

            item.addEventListener('keydown', function (e) {
                if (e.target !== item) return;
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    abrir();
                }
            });
        });

        document.getElementById('btnExcluirRefeicao').addEventListener('click', function () {
            if (!confirm('Tem certeza que deseja remover esta refeição? Seus macros serão recalculados.')) return;

            document.getElementById('edicao_acao').value = 'excluir';
            document.getElementById('descricaoEdicao').required = false;
            document.getElementById('formEdicao').requestSubmit();
        });

        // ---------- Sugestão inteligente (só lê e sugere, não altera a refeição) ----------
        const SUGESTAO_URL = '../controllers/sugestao_controller.php';
        const ERROS_SUGESTAO = {
            timeout: 'A IA demorou para responder. Tente novamente.',
            sem_nutrientes: 'Essa refeição não tem valores nutricionais para comparar.',
            sugestao_fora_do_objetivo: 'A IA não conseguiu uma sugestão adequada ao seu objetivo. Tente de novo.',
            refeicao_nao_encontrada: 'Refeição não encontrada.',
            nao_logado: 'Sua sessão expirou. Entre novamente.'
        };

        function renderizarSugestao(d) {
            const box = document.getElementById('sugestaoResultado');
            const el = (tag, cls, txt) => {
                const e = document.createElement(tag);
                if (cls) e.className = cls;
                if (txt !== undefined) e.textContent = txt;
                return e;
            };

            const lista = el('ul');
            d.ingredientes.forEach(function (i) {
                lista.appendChild(el('li', '', i.quantidade ? i.quantidade + ' de ' + i.item : i.item));
            });

            const outra = el('button', 'sug-outra', '↻ Gerar outra sugestão');
            outra.type = 'button';
            outra.addEventListener('click', function () { pedirSugestao(true); });

            box.replaceChildren(
                el('h4', '', d.nome),
                el('div', 'sug-meta', 'Objetivo: ' + d.objetivo + (d.categoria ? ' • ' + d.categoria : '')),
                lista,
                el('div', 'sug-kcal', '≈ ' + d.kcal_estimadas + ' kcal (a sua tem ' + d.kcal_original + ' kcal) • ≈ ' + d.proteina_estimada + ' g de proteína'),
                el('div', 'sug-motivo', d.motivo),
                el('div', 'sug-aviso', 'Estimativa feita por IA. Sua refeição não foi alterada.'),
                outra
            );
            box.style.display = 'block';
        }

        async function pedirSugestao(nova) {
            const id = document.getElementById('refeicao_id').value;
            if (!id) return;

            const btn = document.getElementById('btnSugestao');
            const box = document.getElementById('sugestaoResultado');
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
                    mostrarToast(ERROS_SUGESTAO[dados.erro] || 'Não foi possível gerar a sugestão agora.', 'erro');
                    return;
                }
                renderizarSugestao(dados);
            } catch (e) {
                box.style.display = 'none';
                mostrarToast('Não foi possível gerar a sugestão agora.', 'erro');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Sugestão inteligente';
            }
        }

        document.getElementById('btnSugestao').addEventListener('click', function () { pedirSugestao(false); });

        function fecharModal() {
            modal.classList.remove('ativo');
            document.getElementById('formRefeicao').reset();
            limparDadosBarcode();
            alterarMetodoHome();
        }

        function fecharModalEdicao() {
            modalEdicao.classList.remove('ativo');
            esconderNutrientesRefeicao();
            document.getElementById('formEdicao').reset();
            document.getElementById('edicao_acao').value = 'editar';
            document.getElementById('descricaoEdicao').required = true;
            document.getElementById('refeicao_id').value = '';
            const boxSug = document.getElementById('sugestaoResultado');
            boxSug.style.display = 'none';
            boxSug.replaceChildren();
        }

        window.addEventListener('click', function (event) {
            if (event.target === modal) fecharModal();
            if (event.target === modalEdicao) fecharModalEdicao();
        });

        document.getElementById('formEdicao').addEventListener('submit', function () {
            try {
                sessionStorage.setItem('nutria_refeicoes_antes', document.querySelectorAll('.refeicao-item').length);
                sessionStorage.setItem('nutria_acao_refeicao', document.getElementById('edicao_acao').value);
            } catch (err) {}

            const btnSalvar = document.getElementById('btnSalvarEdicao');
            if (btnSalvar) {
                btnSalvar.disabled = true;
                btnSalvar.textContent = document.getElementById('edicao_acao').value === 'excluir' ? 'Excluindo...' : 'Salvando...';
            }
        });

        document.getElementById('formRefeicao').addEventListener('submit', function (e) {
            if (document.getElementById('metodo_adicao').value === 'barcode' && !document.getElementById('hiddenKcal').value) {
                e.preventDefault();
                const msg = document.getElementById('mensagemBarcode');
                msg.style.display = 'none';
                void msg.offsetWidth;
                msg.className = 'aviso';
                msg.innerHTML = '⚠️ <strong>Pesquise um produto e informe a quantidade</strong> antes de adicionar.';
                msg.style.display = 'block';
                return;
            }

            if (document.getElementById('metodo_adicao').value === 'favorita' && !document.getElementById('favorita_id').value) {
                e.preventDefault();
                mostrarToast('Escolha uma refeição favorita', 'aviso', 4000);
                return;
            }

            try {
                sessionStorage.setItem('nutria_refeicoes_antes', document.querySelectorAll('.refeicao-item').length);
                sessionStorage.setItem('nutria_acao_refeicao', document.getElementById('barcode_acao').value);
            } catch (err) {}

            const btnSalvar = document.getElementById('btnSalvarBarcode');
            if (btnSalvar) {
                btnSalvar.disabled = true;
                btnSalvar.textContent = 'Salvando...';
            }
        });

        let produtoBarcodeHome = null;

        function alterarMetodoHome() {
            const metodo = document.getElementById('metodo_adicao').value;
            const areaIA = document.getElementById('areaIA');
            const areaBarcode = document.getElementById('areaBarcode');
            const areaFavoritas = document.getElementById('areaFavoritas');
            const descricao = document.getElementById('descricao');
            const acao = document.getElementById('barcode_acao');

            areaIA.style.display = metodo === 'ia' ? 'block' : 'none';
            areaBarcode.style.display = metodo === 'barcode' ? 'block' : 'none';
            areaFavoritas.style.display = metodo === 'favorita' ? 'block' : 'none';

            descricao.required = metodo === 'ia';
            descricao.value = '';

            if (metodo === 'barcode') {
                acao.value = 'salvar_barcode';
            } else if (metodo === 'favorita') {
                acao.value = 'salvar_favorita';
            } else {
                acao.value = 'salvar';
            }

            if (metodo !== 'barcode') {
                limparDadosBarcode();
            }

            // Zera a escolha e (re)desenha a lista de favoritas
            limparFavorita();
        }

        function buscarProdutoHome() {
            const input = document.getElementById('codigo_barras');
            const botao = document.querySelector('#areaBarcode button[onclick="buscarProdutoHome()"]');
            const codigo = input.value.trim();
            const mensagem = document.getElementById('mensagemBarcode');
            const produto = document.getElementById('produtoBarcode');
            const quantidade = document.getElementById('quantidadeBarcode');

            function mostrarMensagem(texto, tipo) {
                mensagem.className = tipo;
                mensagem.innerHTML = texto;
                mensagem.style.display = 'block';
            }

            mensagem.style.display = 'none';
            mensagem.innerHTML = '';
            mensagem.className = '';

            if (!codigo) {
                mostrarMensagem('Digite um código de barras para pesquisar o produto.', 'aviso');
                input.focus();
                return;
            }

            if (botao) {
                botao.disabled = true;
                botao.innerText = 'Buscando...';
            }

            produto.style.display = 'none';
            quantidade.style.display = 'none';

            const formData = new FormData();
            formData.append('codigo_barras', codigo);

            fetch('../controllers/alimento_controller.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) throw new Error('HTTP_' + response.status);
                return response.json();
            })
            .then(dados => {
                if (!dados.sucesso) {
                    mostrarMensagem('❌ <strong>Não foi possível encontrar este produto.</strong><br>', 'erro');
                    return;
                }

                produtoBarcodeHome = dados;
                mensagem.style.display = 'none';
                produto.style.display = 'block';
                document.getElementById('barcodeNome').innerText = dados.produto.nome;
                document.getElementById('barcodeMarca').innerText = dados.produto.marca ? 'Marca: ' + dados.produto.marca : '';
                document.getElementById('barcodeBase').innerText = 'Valores informados por ' + dados.produto.base_nutricional;
                document.getElementById('unidadeBarcode').innerText = dados.produto.unidade;
                quantidade.style.display = 'block';
                document.getElementById('quantidade').value = '';
                document.getElementById('resultadoBarcode').style.display = 'none';
            })
            .catch(error => {
                mostrarMensagem('🌐 <strong>Não foi possível consultar o produto.</strong><br>', 'erro');
            })
            .finally(() => {
                if (botao) {
                    botao.disabled = false;
                    botao.innerText = 'Pesquisar';
                }
            });
        }

        document.addEventListener('input', function (event) {
            if (event.target.id !== 'quantidade' || !produtoBarcodeHome) return;

            const quantidade = parseFloat(event.target.value);
            if (isNaN(quantidade) || quantidade <= 0) {
                document.getElementById('resultadoBarcode').style.display = 'none';
                return;
            }

            const fator = quantidade / 100;
            const nutrientes = produtoBarcodeHome.nutrientes;
            const kcal = parseFloat(nutrientes.kcal || 0) * fator;
            const proteina = parseFloat(nutrientes.proteina || 0) * fator;
            const carbo = parseFloat(nutrientes.carbo || 0) * fator;
            const gordura = parseFloat(nutrientes.gordura || 0) * fator;
            const acucar = parseFloat(nutrientes.acucar || 0) * fator;
            const fibra = parseFloat(nutrientes.fibra || 0) * fator;
            const sodio = parseFloat(nutrientes.sodio || 0) * fator;

            document.getElementById('resultadoKcal').innerText = kcal.toFixed(1);
            document.getElementById('resultadoProt').innerText = proteina.toFixed(1);
            document.getElementById('resultadoCarbo').innerText = carbo.toFixed(1);
            document.getElementById('resultadoGord').innerText = gordura.toFixed(1);
            document.getElementById('resultadoAcucar').innerText = acucar.toFixed(1);
            document.getElementById('resultadoFibra').innerText = fibra.toFixed(1);
            document.getElementById('resultadoSodio').innerText = sodio.toFixed(1);
            document.getElementById('resultadoBarcode').style.display = 'block';

            document.getElementById('hiddenKcal').value = kcal.toFixed(2);
            document.getElementById('hiddenProteina').value = proteina.toFixed(2);
            document.getElementById('hiddenCarbo').value = carbo.toFixed(2);
            document.getElementById('hiddenGordura').value = gordura.toFixed(2);
            document.getElementById('hiddenAcucar').value = acucar.toFixed(2);
            document.getElementById('hiddenFibra').value = fibra.toFixed(2);
            document.getElementById('hiddenSodio').value = sodio.toFixed(2);
            document.getElementById('hiddenQuantidade').value = quantidade;

            const unidade = produtoBarcodeHome.produto.unidade;
            const nome = produtoBarcodeHome.produto.nome;
            document.getElementById('descricao').value = nome + ' - ' + quantidade + ' ' + unidade;
        });

        const formRefeicaoHome = document.getElementById('formRefeicao');
        if (formRefeicaoHome) {
            formRefeicaoHome.addEventListener('reset', function () {
                limparDadosBarcode();
            });
        }
    </script>
<script>
/* =====================================================
   REFEIÇÕES FAVORITAS (home + dashboard)
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