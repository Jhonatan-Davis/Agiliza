<?php
// --- conexao.php ---
$host = 'localhost'; // ou o host do seu servidor
$dbname = 'agiliza';
$user = 'root'; // seu usuário do MySQL
$pass = ''; // sua senha do MySQL

try {
    // A conexão usa PDO - é mais seguro contra SQL Injection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro ao conectar ao banco de dados: " . $e->getMessage());
}
?>