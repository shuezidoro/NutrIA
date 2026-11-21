<?php
// Controller das refeições favoritas (responde sempre em JSON)
ini_set('display_errors', '0');
session_start();

header('Content-Type: application/json; charset=utf-8');

function responder(array $dados, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

// Impede acessos de usuários deslogados
if (!isset($_SESSION['usuario_id'])) {
    responder(['ok' => false, 'erro' => 'nao_autenticado'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(['ok' => false, 'erro' => 'metodo_invalido'], 405);
}

require_once __DIR__ . '/../config/conexao.php';

$usuario_id = $_SESSION['usuario_id'];
$acao = $_POST['acao'] ?? '';

try {

    // ==========================================
    // ALTERNAR: favorita se ainda não é, remove se já é
    // ==========================================
    if ($acao === 'alternar') {
        $refeicao_id = intval($_POST['refeicao_id'] ?? 0);

        if ($refeicao_id <= 0) {
            responder(['ok' => false, 'erro' => 'refeicao_invalida'], 400);
        }

        // Busca a refeição (somente do usuário logado) com os nutrientes já calculados
        $stmt = $pdo->prepare("
            SELECT r.descricao_texto,
                   COALESCE(n.calorias_obtidas, 0) AS calorias,
                   COALESCE(n.proteina_obtida, 0)  AS proteina,
                   COALESCE(n.carbo_obtido, 0)     AS carbo,
                   COALESCE(n.gordura_obtida, 0)   AS gordura,
                   COALESCE(n.acucar_obtido, 0)    AS acucar,
                   COALESCE(n.fibra_obtida, 0)     AS fibra,
                   COALESCE(n.sodio_obtido, 0)     AS sodio
            FROM refeicao r
            LEFT JOIN nutriente n ON n.refeicao_id = r.id
            WHERE r.id = ? AND r.usuario_id = ?
            LIMIT 1
        ");
        $stmt->execute([$refeicao_id, $usuario_id]);
        $ref = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ref) {
            responder(['ok' => false, 'erro' => 'refeicao_nao_encontrada'], 404);
        }

        $descricao = trim($ref['descricao_texto']);
        if ($descricao === '') {
            responder(['ok' => false, 'erro' => 'sem_descricao'], 400);
        }

        // Já é favorita? Então remove
        $stmtEx = $pdo->prepare("SELECT id FROM refeicoes_favoritas WHERE usuario_id = ? AND descricao_comida = ? LIMIT 1");
        $stmtEx->execute([$usuario_id, $descricao]);
        $existente = $stmtEx->fetch(PDO::FETCH_ASSOC);

        if ($existente) {
            $stmtDel = $pdo->prepare("DELETE FROM refeicoes_favoritas WHERE id = ? AND usuario_id = ?");
            $stmtDel->execute([$existente['id'], $usuario_id]);

            responder([
                'ok' => true,
                'favorita' => false,
                'removido_id' => (int) $existente['id']
            ]);
        }

        // Senão, cria a favorita guardando os nutrientes
        $nome = mb_substr($descricao, 0, 100);

        $stmtIns = $pdo->prepare("
            INSERT INTO refeicoes_favoritas
                (usuario_id, nome_favorito, descricao_comida, calorias, proteina, carbo, gordura, acucar, fibra, sodio)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmtIns->execute([
            $usuario_id,
            $nome,
            $descricao,
            $ref['calorias'],
            $ref['proteina'],
            $ref['carbo'],
            $ref['gordura'],
            $ref['acucar'],
            $ref['fibra'],
            $ref['sodio']
        ]);

        responder([
            'ok' => true,
            'favorita' => true,
            'favorito' => [
                'id'        => (int) $pdo->lastInsertId(),
                'nome'      => $nome,
                'descricao' => $descricao,
                'calorias'  => (float) $ref['calorias'],
                'proteina'  => (float) $ref['proteina'],
                'carbo'     => (float) $ref['carbo'],
                'gordura'   => (float) $ref['gordura'],
                'acucar'    => (float) $ref['acucar'],
                'fibra'     => (float) $ref['fibra'],
                'sodio'     => (float) $ref['sodio']
            ]
        ]);
    }

    // ==========================================
    // REMOVER: apaga uma favorita pelo id
    // ==========================================
    if ($acao === 'remover') {
        $favorita_id = intval($_POST['favorita_id'] ?? 0);

        if ($favorita_id <= 0) {
            responder(['ok' => false, 'erro' => 'favorita_invalida'], 400);
        }

        $stmtDel = $pdo->prepare("DELETE FROM refeicoes_favoritas WHERE id = ? AND usuario_id = ?");
        $stmtDel->execute([$favorita_id, $usuario_id]);

        if ($stmtDel->rowCount() === 0) {
            responder(['ok' => false, 'erro' => 'favorita_nao_encontrada'], 404);
        }

        responder(['ok' => true, 'removido_id' => $favorita_id]);
    }

    responder(['ok' => false, 'erro' => 'acao_invalida'], 400);

} catch (Exception $e) {
    error_log("Erro no favorita_controller: " . $e->getMessage());
    responder(['ok' => false, 'erro' => 'banco'], 500);
}