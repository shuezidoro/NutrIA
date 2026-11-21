<?php
session_start();
require_once __DIR__ . '/../config/conexao.php';

if (!isset($_SESSION['email_pendente_verificacao'])) {
    header("Location: ../views/login.php");
    exit;
}

$email = $_SESSION['email_pendente_verificacao'];
$codigoDigitado = trim($_POST['codigo'] ?? '');

$stmt = $pdo->prepare("SELECT id, nome, token_verificacao, codigo_expira_em FROM usuarios WHERE email = ?");
$stmt->execute([$email]);
$usuario = $stmt->fetch();

if ($usuario && $usuario['token_verificacao'] === $codigoDigitado && strtotime($usuario['codigo_expira_em']) > time()) {

    // Código correto e dentro do prazo
    $pdo->prepare("UPDATE usuarios SET email_verificado = 1, token_verificacao = NULL, codigo_expira_em = NULL WHERE id = ?")
        ->execute([$usuario['id']]);

    // Loga o usuário automaticamente após confirmar
    $_SESSION['usuario_id'] = $usuario['id'];
    $_SESSION['usuario_nome'] = $usuario['nome'];
    unset($_SESSION['email_pendente_verificacao']);

    header("Location: ../views/metas.php");
    exit;

} else {
    header("Location: ../views/verificar_email.php?erro=1");
    exit;
}