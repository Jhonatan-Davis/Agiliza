<?php
session_start();
require '../conexao.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['funcao'] != 'dono') { die("Acesso negado."); }

$id_negocio = $_SESSION['id_negocio'];
$id_cliente = $_POST['id_cliente'];
$anotacao = $_POST['anotacao'];

try {
    // Salva ou Atualiza (ON DUPLICATE KEY UPDATE)
    $stmt = $pdo->prepare(
        "INSERT INTO anotacoes_clientes (id_negocio, id_cliente, anotacao) 
         VALUES (:neg, :cli, :nota)
         ON DUPLICATE KEY UPDATE anotacao = :nota"
    );
    
    $stmt->execute([
        'neg' => $id_negocio,
        'cli' => $id_cliente,
        'nota' => $anotacao
    ]);

    header("Location: detalhe_cliente.php?id=$id_cliente&sucesso=1");

} catch (PDOException $e) {
    die("Erro ao salvar: " . $e->getMessage());
}
?>