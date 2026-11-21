<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();


//impede acessos de usuários deslogados
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../views/login.php");
    exit;
}

require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/ia_controller.php'; // Integração com NVIDIA Nemotron Lightning

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $usuario_id = $_SESSION['usuario_id'];
    $data_consumo = $_POST['data_refeicao'];
    $tipo_refeicao = $_POST['tipo_refeicao'];
    $refeicao_id = $_POST['refeicao_id'] ?? null;
    $acao = $_POST['acao'] ?? 'salvar';

    $retorno = $_POST['retorno'] ?? 'dashboard';

    if ($retorno === 'home') {
        $paginaRetorno = '../views/home.php';
    } else {
        $paginaRetorno = '../views/dashboard.php';
    }

    // Impede injeções manuais em datas futuras
    if (strtotime($data_consumo) > strtotime(date('Y-m-d'))) {
        header("Location: ../views/dashboard.php?erro=data_futura");
        exit;
    }

    // OPERAÇÃO DE EXCLUSÃO DE REFEIÇÃO
    if ($acao === 'excluir' && !empty($refeicao_id)) {
        try {
            $pdo->beginTransaction();

            // Exclui primeiro os registros filhos na tabela 'nutriente' devido à Foreign Key
            // (somente se a refeição pertencer ao usuário logado)
            $stmtNut = $pdo->prepare("
                DELETE n FROM nutriente n
                INNER JOIN refeicao r ON r.id = n.refeicao_id
                WHERE n.refeicao_id = ? AND r.usuario_id = ?
            ");
            $stmtNut->execute([$refeicao_id, $usuario_id]);

            // Exclui a refeição principal garantindo o ID e a segurança do usuário logado
            $stmtRef = $pdo->prepare("DELETE FROM refeicao WHERE id = ? AND usuario_id = ?");
            $stmtRef->execute([$refeicao_id, $usuario_id]);

            $pdo->commit();
            header("Location: {$paginaRetorno}?sucesso=excluido");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            die("Erro crítico ao excluir o registro: " . $e->getMessage());
        }
    }

    // OPERAÇÃO: ADICIONAR REFEIÇÃO A PARTIR DE UMA FAVORITA (sem passar pela IA)
    if ($acao === 'salvar_favorita') {

        $favorita_id = intval($_POST['favorita_id'] ?? 0);

        if ($favorita_id <= 0 || empty($data_consumo) || empty($tipo_refeicao)) {
            header("Location: {$paginaRetorno}?erro=campos_vazios");
            exit;
        }

        try {

            // Busca a favorita (somente do usuário logado): os nutrientes vêm do banco, não do navegador
            $stmtFav = $pdo->prepare("
                SELECT descricao_comida, calorias, proteina, carbo, gordura, acucar, fibra, sodio
                FROM refeicoes_favoritas
                WHERE id = ? AND usuario_id = ?
            ");
            $stmtFav->execute([$favorita_id, $usuario_id]);
            $fav = $stmtFav->fetch(PDO::FETCH_ASSOC);

            if (!$fav) {
                header("Location: {$paginaRetorno}?erro=favorita_invalida");
                exit;
            }

            $pdo->beginTransaction();

            $stmtRef = $pdo->prepare("
                INSERT INTO refeicao (usuario_id, data_consumo, tipo_refeicao, descricao_texto)
                VALUES (?, ?, ?, ?)
            ");
            $stmtRef->execute([
                $usuario_id,
                $data_consumo,
                $tipo_refeicao,
                $fav['descricao_comida']
            ]);

            $novoId = $pdo->lastInsertId();

            $stmtNut = $pdo->prepare("
                INSERT INTO nutriente
                    (refeicao_id, calorias_obtidas, proteina_obtida, carbo_obtido, gordura_obtida, acucar_obtido, fibra_obtida, sodio_obtido)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmtNut->execute([
                $novoId,
                $fav['calorias'],
                $fav['proteina'],
                $fav['carbo'],
                $fav['gordura'],
                $fav['acucar'],
                $fav['fibra'],
                $fav['sodio']
            ]);

            $pdo->commit();

            header("Location: {$paginaRetorno}?sucesso=1");
            exit;

        } catch (Exception $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log("Erro ao salvar refeição a partir de favorita: " . $e->getMessage());

            header("Location: {$paginaRetorno}?erro=banco");
            exit;
        }
    }

    // OPERAÇÃO: SALVAR REFEIÇÃO POR CÓDIGO DE BARRAS
    if ($acao === 'salvar_barcode') {

        $descricao_texto = trim($_POST['descricao_comida'] ?? '');
        $quantidade = floatval($_POST['quantidade'] ?? 0);

        $kcal = floatval($_POST['kcal'] ?? 0);
        $proteina = floatval($_POST['proteina'] ?? 0);
        $carbo = floatval($_POST['carbo'] ?? 0);
        $gordura = floatval($_POST['gordura'] ?? 0);
        $acucar = floatval($_POST['acucar'] ?? 0);
        $fibra = floatval($_POST['fibra'] ?? 0);
        $sodio = floatval($_POST['sodio'] ?? 0);

        if (
            empty($descricao_texto) ||
            empty($data_consumo) ||
            empty($tipo_refeicao) ||
            $quantidade <= 0
        ) {
            header("Location: {$paginaRetorno}?erro=campos_vazios");
            exit;
        }

        try {

            $pdo->beginTransaction();

            $sqlRefeicao = "
            INSERT INTO refeicao
            (
                usuario_id,
                data_consumo,
                tipo_refeicao,
                descricao_texto
            )
            VALUES (?, ?, ?, ?)
        ";

            $stmtRef = $pdo->prepare($sqlRefeicao);

            $stmtRef->execute([
                $usuario_id,
                $data_consumo,
                $tipo_refeicao,
                $descricao_texto
            ]);

            $novoId = $pdo->lastInsertId();

            // Salva nutrientes

            $sqlNutrientes = "
            INSERT INTO nutriente
            (
                refeicao_id,
                calorias_obtidas,
                proteina_obtida,
                carbo_obtido,
                gordura_obtida,
                acucar_obtido,
                fibra_obtida,
                sodio_obtido
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ";

            $stmtNut = $pdo->prepare($sqlNutrientes);

            $stmtNut->execute([
                $novoId,
                $kcal,
                $proteina,
                $carbo,
                $gordura,
                $acucar,
                $fibra,
                $sodio
            ]);

            $pdo->commit();

            header("Location: {$paginaRetorno}?sucesso=1");
            exit;

        } catch (Exception $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log("Erro ao salvar refeição por código de barras: " . $e->getMessage());

            header("Location: {$paginaRetorno}?erro=banco");
            exit;
        }
    }

    // Validação básica de entradas para Salvar / Editar
    $descricao_texto = isset($_POST['descricao_comida']) ? trim($_POST['descricao_comida']) : '';
    if (empty($descricao_texto) || empty($data_consumo)) {
        header("Location: {$paginaRetorno}?erro=campos_vazios");
        exit;
    }

    //RECALCULAR COM A IA E VALIDAR SE É COMESTÍVEL
    $macrosEstimados = analisarRefeicaoComIA($descricao_texto);

    // Alimento não reconhecido como comestível
    if (isset($macrosEstimados['erro']) && $macrosEstimados['erro'] === 'nao_comivel') {
        header("Location: {$paginaRetorno}?erro=nao_comivel");
        exit;
    }

    if (isset($macrosEstimados['erro']) && $macrosEstimados['erro'] === 'timeout') {
        header("Location: {$paginaRetorno}?erro=timeout");
        exit;
    }

    // Outros erros da IA
    if (
        isset($macrosEstimados['erro']) ||
        !isset($macrosEstimados['kcal'])
    ) {
        header("Location: {$paginaRetorno}?erro=ia");
        exit;
    }

    // Se passou na validação, coleta os nutrientes mapeados corretamente com o banco
    $kcal = intval($macrosEstimados['kcal']);
    $carbo = floatval($macrosEstimados['carbo']);
    $proteina = floatval($macrosEstimados['proteina']);
    $gordura = floatval($macrosEstimados['gordura']);
    $acucar = floatval($macrosEstimados['acucar'] ?? 0.00);
    $fibra = floatval($macrosEstimados['fibra'] ?? 0.00);
    $sodio = floatval($macrosEstimados['sodio'] ?? 0.00);

    // SALVAR OU EDITAR NAS TABELAS
    try {
        $pdo->beginTransaction();

        if ($acao === 'editar' && !empty($refeicao_id)) {
            // OPERAÇÃO DE ATUALIZAÇÃO
            $sqlRefeicao = "UPDATE refeicao SET tipo_refeicao = ?, descricao_texto = ? WHERE id = ? AND usuario_id = ?";
            $stmtRef = $pdo->prepare($sqlRefeicao);
            $stmtRef->execute([$tipo_refeicao, $descricao_texto, $refeicao_id, $usuario_id]);

            $sqlNutrientes = "UPDATE nutriente SET calorias_obtidas = ?, carbo_obtido = ?, proteina_obtida = ?, gordura_obtida = ?, acucar_obtido = ?, fibra_obtida = ?, sodio_obtido = ? WHERE refeicao_id = ?";
            $stmtNut = $pdo->prepare($sqlNutrientes);
            $stmtNut->execute([$kcal, $carbo, $proteina, $gordura, $acucar, $fibra, $sodio, $refeicao_id]);

        } else {
            // OPERAÇÃO DE INCLUSÃO PADRÃO
            $sqlRefeicao = "INSERT INTO refeicao (usuario_id, data_consumo, tipo_refeicao, descricao_texto) VALUES (?, ?, ?, ?)";
            $stmtRef = $pdo->prepare($sqlRefeicao);
            $stmtRef->execute([$usuario_id, $data_consumo, $tipo_refeicao, $descricao_texto]);

            $refeicao_id = $pdo->lastInsertId();

            // Adicionado sodio_obtido no INSERT
            $sqlNutrientes = "INSERT INTO nutriente (refeicao_id, calorias_obtidas, proteina_obtida, carbo_obtido, gordura_obtida, acucar_obtido, fibra_obtida, sodio_obtido) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmtNut = $pdo->prepare($sqlNutrientes);
            $stmtNut->execute([$refeicao_id, $kcal, $proteina, $carbo, $gordura, $acucar, $fibra, $sodio]);
        }

        $pdo->commit();
        header("Location: {$paginaRetorno}?sucesso=1");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Erro crítico ao processar transação no banco: " . $e->getMessage());
    }
} else {
    header("Location: ../views/dashboard.php");
    exit;
}