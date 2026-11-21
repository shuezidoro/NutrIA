<?php
require_once __DIR__ . '/../config/conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $novaSenha = $_POST['senha'] ?? '';

    // Busca o token válido e já traz o usuario_id vinculado a ele
    $stmt = $pdo->prepare("
        SELECT rs.id, rs.usuario_id, u.email 
        FROM reset_senha rs
        INNER JOIN usuarios u ON u.id = rs.usuario_id
        WHERE rs.token = ? AND rs.usado = 0 AND rs.expira_em > NOW()
    ");
    $stmt->execute([$token]);
    $reset = $stmt->fetch();

    if (!$reset) {
        die("Link inválido, expirado ou já utilizado. Solicite uma nova redefinição.");
    }

    // Atualiza a senha exatamente do usuario_id vinculado ao token
    $senhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);

    $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?")
        ->execute([$senhaHash, $reset['usuario_id']]);

    //Invalida o token pra não poder ser reaproveitado
    $pdo->prepare("UPDATE reset_senha SET usado = 1 WHERE id = ?")
        ->execute([$reset['id']]);

    header("Location: ../views/login.php?senha_redefinida=1");
    exit;

} else {
    header("Location: ../../index.php");
    exit;
}