<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode([
        'sucesso' => false,
        'erro' => 'nao_autorizado'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
// Exclusivo para POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'sucesso' => false,
        'erro' => 'metodo_invalido'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

//pega codigo de barras
$codigo = $_POST['codigo_barras'] ?? '';
$codigo = preg_replace('/\D/', '', $codigo);

if ($codigo === '') {
    echo json_encode([
        'sucesso' => false,
        'erro' => 'codigo_invalido'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
//url do Open Food Facts
$url = 'https://world.openfoodfacts.org/api/v3/product/'
    . urlencode($codigo)
    . '?fields=code,product_name,brands,quantity,product_quantity,product_quantity_unit,nutrition_data_per,serving_size,nutriments';

//curl
$ch = curl_init($url);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPGET => true,

    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT => 15,

    CURLOPT_HTTPHEADER => [
        'User-Agent: NutrIA/1.0 - projeto academico'
    ],

    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2
]);

$resposta = curl_exec($ch);

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErro = curl_error($ch);

curl_close($ch);
// erro curl
if ($resposta === false || $curlErro) {
    echo json_encode([
        'sucesso' => false,
        'erro' => 'erro_conexao',
        'detalhes' => $curlErro
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

//Erro no http
if ($httpCode !== 200) {

    echo json_encode([
        'sucesso' => false,
        'erro' => 'http',
        'http_code' => $httpCode
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

//Decodifica o Json
$dados = json_decode($resposta, true);
if (!is_array($dados)) {
    echo json_encode([
        'sucesso' => false,
        'erro' => 'json_invalido'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}


$status = $dados['status'] ?? '';
$produto = $dados['product'] ?? null;

if (
    !in_array($status, [
        'success',
        'success_with_warnings',
        'success_with_errors'
    ], true)
    || !is_array($produto)
) {

    echo json_encode([
        'sucesso' => false,
        'erro' => 'produto_nao_encontrado',
        'status_api' => $status
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

//Nutrientes
$nutrientes = $produto['nutriments'] ?? [];
if (!is_array($nutrientes)) {
    $nutrientes = [];
}

//identifica a base nutricional

$nutritionDataPer = strtolower(
    trim($produto['nutrition_data_per'] ?? '100g')
);

$eh100ml = (
    strpos($nutritionDataPer, '100ml') !== false ||
    strpos($nutritionDataPer, '100 ml') !== false
);

//define campos

if ($eh100ml) {

    $base = '100ml';
    $unidade = 'ml';

    $kcal = $nutrientes['energy-kcal_100ml']
        ?? $nutrientes['energy-kcal_value']
        ?? null;

    $proteina = $nutrientes['proteins_100ml'] ?? null;
    $carbo = $nutrientes['carbohydrates_100ml'] ?? null;
    $gordura = $nutrientes['fat_100ml'] ?? null;
    $acucar = $nutrientes['sugars_100ml'] ?? null;
    $fibra = $nutrientes['fiber_100ml'] ?? null;
    $sodio = $nutrientes['sodium_100ml'] ?? null;

} else {

    $base = '100g';
    $unidade = 'g';

    $kcal = $nutrientes['energy-kcal_100g'] ?? null;
    $proteina = $nutrientes['proteins_100g'] ?? null;
    $carbo = $nutrientes['carbohydrates_100g'] ?? null;
    $gordura = $nutrientes['fat_100g'] ?? null;
    $acucar = $nutrientes['sugars_100g'] ?? null;
    $fibra = $nutrientes['fiber_100g'] ?? null;
    $sodio = $nutrientes['sodium_100g'] ?? null;
}

// calorias são obrigatória

if ($kcal === null || $kcal === '') {

    echo json_encode([
        'sucesso' => false,
        'erro' => 'nutricao_nao_disponivel'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

//produto

$nome = $produto['product_name'] ?? 'Produto sem nome';
$marca = $produto['brands'] ?? '';
$quantidadeEmbalagem = $produto['quantity'] ?? '';
$quantidadeProduto = $produto['product_quantity'] ?? '';
$unidadeProduto = $produto['product_quantity_unit'] ?? '';
$porcao = $produto['serving_size'] ?? '';

// resposta padronizada

echo json_encode([
    'sucesso' => true,

    'produto' => [
        'codigo' => $codigo,
        'nome' => $nome,
        'marca' => $marca,
        'quantidade_embalagem' => $quantidadeEmbalagem,
        'quantidade_produto' => $quantidadeProduto,
        'unidade_produto' => $unidadeProduto,
        'base_nutricional' => $base,
        'unidade' => $unidade,
        'porcao' => $porcao
    ],

    'nutrientes' => [
        'kcal' => (float) $kcal,
        'proteina' => (float) ($proteina ?? 0),
        'carbo' => (float) ($carbo ?? 0),
        'gordura' => (float) ($gordura ?? 0),
        'acucar' => (float) ($acucar ?? 0),
        'fibra' => (float) ($fibra ?? 0),
        'sodio' => (float) ($sodio ?? 0)
    ]
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);