<?php
session_start();
require '../conexao.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['funcao'] != 'dono') { die("Acesso negado."); }

$id_negocio = $_SESSION['id_negocio'];
$action = $_REQUEST['action'] ?? '';

try {
    if ($action == 'add' && $_SERVER['REQUEST_METHOD'] == 'POST') {
        $desc = $_POST['descricao'];
        $valor = str_replace(',', '.', $_POST['valor']); // Garante formato decimal
        $data = $_POST['data_despesa'];

        $stmt = $pdo->prepare("INSERT INTO despesas (id_negocio, descricao, valor, data_despesa) VALUES (:neg, :desc, :val, :data)");
        $stmt->execute(['neg' => $id_negocio, 'desc' => $desc, 'val' => $valor, 'data' => $data]);
    }
    
    elseif ($action == 'delete' && isset($_GET['id'])) {
        $stmt = $pdo->prepare("DELETE FROM despesas WHERE id = :id AND id_negocio = :neg");
        $stmt->execute(['id' => $_GET['id'], 'neg' => $id_negocio]);
    }

    header("Location: financeiro.php?sucesso=1");

} catch (Exception $e) {
    die("Erro: " . $e->getMessage());
}
?>