<?php
session_start();
require_once __DIR__ . '/../config/conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['usuario_id'])) {
    header("Location: ../views/login.php");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

// Coleta e sanitiza os dados enviados pelo formulário
$nome = trim($_POST['nome']);
$idade = intval($_POST['idade']);
$peso = floatval($_POST['peso']);
$altura = floatval($_POST['altura']);
$genero = $_POST['genero'];
$nivel_atividade = $_POST['nivel_atividade'];
$objetivo = $_POST['objetivo'];


//  VALIDAÇÃO DE NOME
if (empty($nome) || strlen($nome) < 3 || strlen($nome) > 100) {
    header("Location: ../views/perfil.php?erro=nome_invalido");
    exit;
}

// VALIDAÇÃO DE IDADE (15-120 anos)
if ($idade < 15 || $idade > 120) {
    header("Location: ../views/perfil.php?erro=idade_invalida");
    exit;
}

// VALIDAÇÃO DE PESO (30kg - 300kg, muito realista)
if ($peso < 30 || $peso > 300) {
    header("Location: ../views/perfil.php?erro=peso_invalido");
    exit;
}

// VALIDAÇÃO DE ALTURA
if ($altura > 3) {
    $altura = $altura / 100;
}

// Valida altura (0.80m até 2.50m = 80cm a 250cm)
if ($altura < 0.80 || $altura > 2.50) {
    header("Location: ../views/perfil.php?erro=altura_invalida");
    exit;
}

// VALIDAÇÃO DE GÊNERO
$generos_validos = ['Masculino', 'Feminino', 'Outro'];
if (!in_array($genero, $generos_validos)) {
    header("Location: ../views/perfil.php?erro=genero_invalido");
    exit;
}

// VALIDAÇÃO DE NÍVEL DE ATIVIDADE
$niveis_validos = ['Sedentario', 'Leve', 'Moderado', 'Ativo', 'Muito_Ativo'];
if (!in_array($nivel_atividade, $niveis_validos)) {
    header("Location: ../views/perfil.php?erro=atividade_invalida");
    exit;
}

//VALIDAÇÃO DE OBJETIVO
$objetivos_validos = ['Perda_Peso', 'Manter_Saude', 'Ganho_Massa'];
if (!in_array($objetivo, $objetivos_validos)) {
    header("Location: ../views/perfil.php?erro=objetivo_invalido");
    exit;
}

// CÁLCULO DO IMC PARA VERIFICAR COERÊNCIA

// IMC = peso / (altura²)
$imc = $peso / ($altura * $altura);

// Valida se o IMC está dentro de um intervalo humanamente possível
// IMC válido: 13 (magreza extrema) a 50 (obesidade severa)
if ($imc < 13 || $imc > 50) {
    header("Location: ../views/perfil.php?erro=proporcoes_irreais");
    exit;
}

//  ATUALIZAR PERFIL (Tudo validado)

try {
    $pdo->beginTransaction();

    //Atualiza o nome na tabela 'usuarios'
    $stmtUser = $pdo->prepare("UPDATE usuarios SET nome = ? WHERE id = ?");
    $stmtUser->execute([$nome, $usuario_id]);

    // Atualiza o nome gravado na sessão ativa
    $_SESSION['usuario_nome'] = $nome;

    // Busca o peso atual registrado antes de atualizar para saber se mudou
    $stmtCheckPeso = $pdo->prepare("SELECT peso FROM biometria WHERE usuario_id = ? LIMIT 1");
    $stmtCheckPeso->execute([$usuario_id]);
    $pesoAtual = $stmtCheckPeso->fetchColumn();

    // Atualiza os dados na tabela 'biometria'
    $sqlBio = "UPDATE biometria SET 
                peso = ?, 
                altura = ?, 
                idade = ?, 
                genero = ?, 
                nivel_atividade = ?, 
                objetivo = ? 
               WHERE usuario_id = ?";
    $stmtBio = $pdo->prepare($sqlBio);
    $stmtBio->execute([$peso, $altura, $idade, $genero, $nivel_atividade, $objetivo, $usuario_id]);

    //Se o peso mudou, alimenta a evolução de peso
    if ($pesoAtual === false || floatval($pesoAtual) !== $peso) {
        $sqlHist = "INSERT INTO historico_peso (usuario_id, peso, data_registro) 
                    VALUES (?, ?, NOW()) 
                    ON DUPLICATE KEY UPDATE peso = VALUES(peso)";

        $stmtHist = $pdo->prepare($sqlHist);
        $stmtHist->execute([$usuario_id, $peso]);
    }

    // Recalcula e salva automaticamente as metas após atualizar o perfil.
    $stmtMetaExistente = $pdo->prepare("
        SELECT origem_meta
        FROM metas_diarias
        WHERE usuario_id = ?
        LIMIT 1
    ");
    $stmtMetaExistente->execute([$usuario_id]);
    $metaExistente = $stmtMetaExistente->fetch(PDO::FETCH_ASSOC);

    // Não sobrescreve metas configuradas manualmente pelo usuário.
    if (!$metaExistente || ($metaExistente['origem_meta'] ?? '') === 'Sistema') {

        $altura_cm = $altura * 100;

        // Taxa Metabólica Basal
        $tmbMasculina = (10 * $peso) + (6.25 * $altura_cm) - (5 * $idade) + 5;
        $tmbFeminina = (10 * $peso) + (6.25 * $altura_cm) - (5 * $idade) - 161;

        if ($genero === 'Masculino') {
            $tmb = $tmbMasculina;
        } elseif ($genero === 'Feminino') {
            $tmb = $tmbFeminina;
        } else {
            $tmb = ($tmbMasculina + $tmbFeminina) / 2;
        }

        // Fator de atividade
        $fatores = [
            'Sedentario' => 1.2,
            'Leve' => 1.375,
            'Moderado' => 1.55,
            'Ativo' => 1.725,
            'Muito_Ativo' => 1.9
        ];

        $fator = $fatores[$nivel_atividade] ?? 1.2;
        $gastoEstimado = $tmb * $fator;

        // Ajuste conforme o objetivo
        $ajuste = 0;
        if ($objetivo === 'Perda_Peso') {
            $ajuste = -400;
        } elseif ($objetivo === 'Ganho_Massa') {
            $ajuste = 300;
        }

        $metaCalorias = (int) round($gastoEstimado + $ajuste);
        if ($metaCalorias < 1200) {
            $metaCalorias = 1200;
        }

        // Macronutrientes
        $metaProteina = (int) round($peso * 1.6);
        $metaGordura = (int) round($peso * 0.8);
        $caloriasRestantes = $metaCalorias - ($metaProteina * 4) - ($metaGordura * 9);

        if ($caloriasRestantes < 0) {
            $metaGordura = max(0, (int) floor(($metaCalorias - ($metaProteina * 4)) / 9));
            $caloriasRestantes = $metaCalorias - ($metaProteina * 4) - ($metaGordura * 9);
        }

        $metaCarbo = max(0, (int) round($caloriasRestantes / 4));

        if ($metaExistente) {
            $stmtAtualizarMeta = $pdo->prepare("
                UPDATE metas_diarias
                SET kcal_meta = ?,
                    proteina_meta = ?,
                    carbo_meta = ?,
                    gordura_meta = ?,
                    origem_meta = 'Sistema',
                    data_atualizacao = CURRENT_TIMESTAMP
                WHERE usuario_id = ?
            ");
            $stmtAtualizarMeta->execute([
                $metaCalorias,
                $metaProteina,
                $metaCarbo,
                $metaGordura,
                $usuario_id
            ]);
        } else {
            $stmtInserirMeta = $pdo->prepare("
                INSERT INTO metas_diarias
                    (usuario_id, kcal_meta, proteina_meta, carbo_meta, gordura_meta, origem_meta)
                VALUES (?, ?, ?, ?, ?, 'Sistema')
            ");
            $stmtInserirMeta->execute([
                $usuario_id,
                $metaCalorias,
                $metaProteina,
                $metaCarbo,
                $metaGordura
            ]);
        }
    }

    // Confirma todas as alterações no banco de dados
    $pdo->commit();

    // Redireciona de volta para o perfil informando o sucesso
    header("Location: ../views/perfil.php?sucesso=perfil_atualizado");
    exit;

} catch (Exception $e) {
    // Cancela as alterações em caso de falha crítica
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("Erro ao atualizar o perfil: " . $e->getMessage());
}
?>