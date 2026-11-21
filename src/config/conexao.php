<?php
$servidor = "localhost";
$usuario  = "root";
$senha    = "";
$banco    = "nutria";

try {
    $pdo = new PDO("mysql:host=$servidor;dbname=$banco;charset=utf8mb4", $usuario, $senha);
    
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
} catch (PDOException $e) {
    die("Conexão falhou: " . $e->getMessage());
}
?>