<?php
session_start();
require 'conexao.php';

if (!isset($_SESSION['usuario_id']) || $_SERVER["REQUEST_METHOD"] != "POST") {
    die("Acesso negado.");
}

$id_cliente = $_SESSION['usuario_id'];
$id_agendamento = $_POST['id_agendamento'];
$id_negocio = $_POST['id_negocio'];
$nota = (int)$_POST['nota'];
$comentario = $_POST['comentario'];

if ($nota < 1 || $nota > 5) { die("Nota inválida."); }

try {
    $stmt = $pdo->prepare(
        "INSERT INTO avaliacoes (id_agendamento, id_negocio, id_cliente, nota, comentario)
         VALUES (:id_ag, :id_neg, :id_cli, :nota, :comentario)"
    );
    
    $stmt->execute([
        'id_ag' => $id_agendamento,
        'id_neg' => $id_negocio,
        'id_cli' => $id_cliente,
        'nota' => $nota,
        'comentario' => $comentario
    ]);

    header("Location: meus_agendamentos.php?sucesso=avaliado");
    exit();

} catch (PDOException $e) {
    die("Erro ao salvar avaliação: " . $e->getMessage());
}
?>