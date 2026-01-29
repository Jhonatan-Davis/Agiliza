<?php
header('Content-Type: application/json');
session_start();
require 'conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['sucesso' => false, 'erro' => 'Você precisa estar logado.']);
    exit();
}

$id_cliente = $_SESSION['usuario_id'];
$id_negocio = $_POST['id_negocio'] ?? 0;
$id_funcionario = $_POST['id_funcionario'] ?? 0;
$data = $_POST['data'] ?? '';

if (empty($id_negocio) || empty($data)) {
    echo json_encode(['sucesso' => false, 'erro' => 'Dados incompletos.']);
    exit();
}

try {
    // 1. Verifica se já está na lista para esse dia/profissional
    $stmt_check = $pdo->prepare(
        "SELECT id FROM lista_espera 
         WHERE id_cliente = :cli AND id_negocio = :neg AND data_desejada = :data AND id_funcionario = :func"
    );
    $stmt_check->execute(['cli' => $id_cliente, 'neg' => $id_negocio, 'data' => $data, 'func' => $id_funcionario]);
    
    if ($stmt_check->rowCount() > 0) {
        echo json_encode(['sucesso' => true, 'mensagem' => 'Você já está na lista!']);
        exit();
    }

    // 2. Insere na lista
    $stmt = $pdo->prepare(
        "INSERT INTO lista_espera (id_negocio, id_cliente, id_funcionario, data_desejada) 
         VALUES (:neg, :cli, :func, :data)"
    );
    $stmt->execute(['neg' => $id_negocio, 'cli' => $id_cliente, 'func' => $id_funcionario, 'data' => $data]);

    echo json_encode(['sucesso' => true, 'mensagem' => 'Pronto! Avisaremos você.']);

} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
}
?>