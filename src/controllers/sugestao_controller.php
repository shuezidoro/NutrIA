<?php

session_start();
header('Content-Type: application/json; charset=utf-8');

function responder($dados, $codigo = 200)
{
    http_response_code($codigo);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_SESSION['usuario_id'])) {
    responder(['erro' => 'nao_logado'], 401);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(['erro' => 'metodo'], 405);
}

require_once __DIR__ . '/../config/conexao.php';

define('NVIDIA_API_KEY', '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');
define('NVIDIA_API_URL', 'https://integrate.api.nvidia.com/v1/chat/completions');
define('NVIDIA_MODELO_SUGESTAO', 'nvidia/nemotron-3.5-lightning-30b-a3b');

$usuario_id = (int) $_SESSION['usuario_id'];
$refeicao_id = (int) ($_POST['refeicao_id'] ?? 0);
$gerarOutra = ($_POST['nova'] ?? '') === '1';

if ($refeicao_id <= 0) {
    responder(['erro' => 'refeicao_invalida'], 400);
}

// ---------------------------------------------------------------
// Dados da refeição + objetivo + meta
// ---------------------------------------------------------------
try {
    $stmt = $pdo->prepare("
        SELECT r.descricao_texto, r.tipo_refeicao,
               COALESCE(n.calorias_obtidas, 0) AS kcal,
               COALESCE(n.proteina_obtida, 0)  AS proteina,
               COALESCE(n.carbo_obtido, 0)     AS carbo,
               COALESCE(n.gordura_obtida, 0)   AS gordura
        FROM refeicao r
        LEFT JOIN nutriente n ON n.refeicao_id = r.id
        WHERE r.id = ? AND r.usuario_id = ?
        LIMIT 1
    ");
    $stmt->execute([$refeicao_id, $usuario_id]);
    $ref = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT objetivo FROM biometria WHERE usuario_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$usuario_id]);
    $objetivo = $stmt->fetchColumn() ?: 'Manter_Saude';

    $stmt = $pdo->prepare("SELECT kcal_meta FROM metas_diarias WHERE usuario_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$usuario_id]);
    $kcalMeta = (int) ($stmt->fetchColumn() ?: 0);
} catch (Exception $e) {
    error_log('Erro na sugestão inteligente (banco): ' . $e->getMessage());
    responder(['erro' => 'banco'], 500);
}

if (!$ref) {
    responder(['erro' => 'refeicao_nao_encontrada'], 404);
}

$kcalOriginal = (float) $ref['kcal'];
if ($kcalOriginal <= 0) {
    responder(['erro' => 'sem_nutrientes'], 422);
}

// ---------------------------------------------------------------
// Alvo numérico conforme o objetivo
// ---------------------------------------------------------------
switch ($objetivo) {
    case 'Perda_Peso':
        $rotulo = 'Perda de peso';
        $kcalMin = (int) round($kcalOriginal * 0.70);
        $kcalMax = (int) round($kcalOriginal * 0.85);
        $instrucao = "O usuário quer PERDER PESO. A sugestão deve ser MENOS calórica que a original, entre {$kcalMin} e {$kcalMax} kcal, "
            . "priorizando mais proteína e fibras e menos açúcar e gordura (trocas como açúcar por adoçante culinário, farinha branca por integral, frito por assado).";
        break;
    case 'Ganho_Massa':
        $rotulo = 'Ganho de massa';
        $kcalMin = (int) round($kcalOriginal * 1.10);
        $kcalMax = (int) round($kcalOriginal * 1.25);
        $instrucao = "O usuário quer GANHAR MASSA MUSCULAR. A sugestão deve ser MAIS calórica que a original, entre {$kcalMin} e {$kcalMax} kcal, "
            . "com mais proteína (ex.: adicionar ovos, iogurte, pasta de amendoim, leite em pó, aveia).";
        break;
    default:
        $rotulo = 'Manter a saúde';
        $kcalMin = (int) round($kcalOriginal * 0.90);
        $kcalMax = (int) round($kcalOriginal * 1.10);
        $instrucao = "O usuário quer MANTER A SAÚDE. A sugestão deve ter calorias parecidas com a original, entre {$kcalMin} e {$kcalMax} kcal, "
            . "com ingredientes mais nutritivos e equilibrados.";
        $objetivo = 'Manter_Saude';
}

// ---------------------------------------------------------------
// Cache por sessão 
// ---------------------------------------------------------------
$chaveCache = md5($refeicao_id . '|' . $ref['descricao_texto'] . '|' . $ref['tipo_refeicao'] . '|' . $objetivo . '|' . round($kcalOriginal));
$anterior = $_SESSION['sugestoes_cache'][$chaveCache] ?? null;

if ($anterior && !$gerarOutra) {
    responder($anterior);
}

// Libera o lock da sessão enquanto espera a IA 
session_write_close();

// ---------------------------------------------------------------
// Prompt
// ---------------------------------------------------------------
$promptSistema = "Você é o módulo de SUGESTÃO INTELIGENTE do NutrIA, um app de acompanhamento nutricional brasileiro. "
    . "Receberá uma refeição já registrada e deve sugerir UMA alternativa da MESMA CATEGORIA de preparo e da mesma ocasião de consumo, "
    . "ajustada ao objetivo do usuário.\n\n"
    . "REGRAS:\n"
    . "1. Mesma categoria: se a original é um bolo, sugira outro bolo; se é um prato de arroz, feijão e carne, sugira outro prato de refeição principal; "
    . "se é um sanduíche, sugira outro sanduíche. Nunca troque por um tipo de prato diferente. Ex.: bolo de chocolate -> bolo de cacau com adoçante.\n"
    . "2. Use ingredientes comuns no Brasil, fáceis de achar em mercado, e quantidades em gramas, ml, unidades ou medidas caseiras.\n"
    . "3. Respeite a faixa de calorias pedida. Estime as calorias de forma realista somando os ingredientes; não force o número.\n"
    . "4. Não dê conselhos médicos, não converse e não explique fora do JSON.\n"
    . "5. O campo \"motivo\" tem no máximo 2 frases curtas, explicando a troca em relação ao objetivo.\n\n"
    . "FORMATO OBRIGATÓRIO: responda apenas com um objeto JSON válido, sem Markdown, com exatamente estas chaves:\n"
    . "{\"nome\": string, \"categoria\": string, \"ingredientes\": [{\"item\": string, \"quantidade\": string}], "
    . "\"kcal\": number, \"proteina\": number, \"motivo\": string}\n"
    . "Use de 3 a 10 ingredientes. kcal e proteina (em gramas) são números, sem unidades.";

$promptUsuario = "Refeição registrada: " . $ref['descricao_texto'] . "\n"
    . "Ocasião: " . ($ref['tipo_refeicao'] ?: 'não informada') . "\n"
    . "Valores atuais: " . round($kcalOriginal) . " kcal, " . round((float) $ref['proteina']) . " g de proteína, "
    . round((float) $ref['carbo']) . " g de carboidratos, " . round((float) $ref['gordura']) . " g de gordura.\n"
    . ($kcalMeta > 0 ? "Meta diária do usuário: {$kcalMeta} kcal.\n" : '')
    . $instrucao;

if ($gerarOutra && $anterior) {
    $promptUsuario .= "\nSugira uma opção DIFERENTE de \"" . $anterior['nome'] . "\".";
}

// ---------------------------------------------------------------
// Chamada à IA 
// ---------------------------------------------------------------
function chamarIA($promptSistema, $promptUsuario, $temperatura)
{
    $dados = [
        'model' => NVIDIA_MODELO_SUGESTAO,
        'messages' => [
            ['role' => 'system', 'content' => $promptSistema],
            ['role' => 'user', 'content' => $promptUsuario]
        ],
        'temperature' => $temperatura,
        'max_tokens' => 700,
        'chat_template_kwargs' => ['enable_thinking' => false],
        'stream' => false
    ];

    $ch = curl_init(NVIDIA_API_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($dados),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . NVIDIA_API_KEY
        ],
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_SSL_VERIFYPEER => false 
    ]);

    $resposta = curl_exec($ch);
    $erroCurl = strtolower(curl_error($ch));
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($erroCurl) {
        return ['erro' => (strpos($erroCurl, 'time') !== false) ? 'timeout' : 'curl'];
    }
    if ($http < 200 || $http >= 300 || !$resposta) {
        error_log("Sugestão IA: HTTP {$http}");
        return ['erro' => 'api'];
    }

    $json = json_decode($resposta, true);
    $texto = $json['choices'][0]['message']['content'] ?? '';
    $texto = trim(str_replace(['```json', '```'], '', $texto));

    if (preg_match('/\{.*\}/s', $texto, $m)) {
        $texto = $m[0];
    }

    $obj = json_decode($texto, true);
    if (!is_array($obj)) {
        return ['erro' => 'json_ia'];
    }
    return $obj;
}

function sugestaoValida($s, $objetivo, $kcalOriginal)
{
    if (empty($s['nome']) || empty($s['ingredientes']) || !is_array($s['ingredientes'])) return false;
    if (!isset($s['kcal']) || !is_numeric($s['kcal']) || $s['kcal'] <= 0) return false;

    $kcal = (float) $s['kcal'];
    // Confere se a sugestão realmente vai na direção do objetivo
    if ($objetivo === 'Perda_Peso') return $kcal <= $kcalOriginal * 0.92;
    if ($objetivo === 'Ganho_Massa') return $kcal >= $kcalOriginal * 1.05;
    return $kcal >= $kcalOriginal * 0.85 && $kcal <= $kcalOriginal * 1.15;
}

$sugestao = null;
$ultimoErro = 'ia';

for ($tentativa = 1; $tentativa <= 2; $tentativa++) {
    $r = chamarIA($promptSistema, $promptUsuario, $gerarOutra ? 0.8 : 0.4);

    if (isset($r['erro'])) {
        $ultimoErro = $r['erro'];
        if ($r['erro'] === 'timeout') break;
        continue;
    }
    if (sugestaoValida($r, $objetivo, $kcalOriginal)) {
        $sugestao = $r;
        break;
    }
    $ultimoErro = 'sugestao_fora_do_objetivo';
}

if (!$sugestao) {
    responder(['erro' => $ultimoErro], 502);
}

// ---------------------------------------------------------------
// Monta a resposta limpa 
// ---------------------------------------------------------------
$ingredientes = [];
foreach (array_slice($sugestao['ingredientes'], 0, 12) as $ing) {
    if (is_array($ing)) {
        $item = trim((string) ($ing['item'] ?? ''));
        $qtd = trim((string) ($ing['quantidade'] ?? ''));
    } else {
        $item = trim((string) $ing);
        $qtd = '';
    }
    if ($item !== '') {
        $ingredientes[] = ['item' => mb_substr($item, 0, 80), 'quantidade' => mb_substr($qtd, 0, 40)];
    }
}

$resultado = [
    'nome' => mb_substr(trim((string) $sugestao['nome']), 0, 100),
    'categoria' => mb_substr(trim((string) ($sugestao['categoria'] ?? '')), 0, 60),
    'ingredientes' => $ingredientes,
    'kcal_estimadas' => (int) round((float) $sugestao['kcal']),
    'proteina_estimada' => (int) round((float) ($sugestao['proteina'] ?? 0)),
    'motivo' => mb_substr(trim((string) ($sugestao['motivo'] ?? '')), 0, 300),
    'kcal_original' => (int) round($kcalOriginal),
    'objetivo' => $rotulo
];

session_start();
$_SESSION['sugestoes_cache'][$chaveCache] = $resultado;

responder($resultado);