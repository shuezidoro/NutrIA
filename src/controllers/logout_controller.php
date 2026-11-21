<?php
session_start();

$_SESSION = array();

//Destrói o cookie de sessão no navegador do usuário por completo 
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destrói a sessão ativa no servidor
session_destroy();

// Redireciona o usuário para a tela de login na pasta views
header("Location: ../views/login.php");
exit;