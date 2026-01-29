<?php
session_start();
require '../conexao.php'; // Sobe um nível para achar a conexão

// --- Porteiro ---
if (!isset($_SESSION['usuario_id']) || $_SESSION['funcao'] != 'dono') {
    die("Acesso negado.");
}

$id_dono = $_SESSION['usuario_id'];
$id_negocio = $_SESSION['id_negocio'];

try {
    // A MÁGICA: Deleta APENAS a linha onde o dono é 'funcionario'
    // A linha onde ele é 'dono' permanece intacta.
    $stmt = $pdo->prepare(
        "DELETE FROM negocio_membros 
         WHERE id_usuario = :id_dono 
           AND id_negocio = :id_negocio 
           AND funcao = 'funcionario'" // O mais importante!
    );
    
    $stmt->execute([
        'id_dono' => $id_dono,
        'id_negocio' => $id_negocio
    ]);

    // Sucesso! Volta para a página da equipe
    header("Location: minha_equipe.php?sucesso=dono_removido");
    exit();

} catch (PDOException $e) {
    header("Location: minha_equipe.php?erro=" . urlencode($e->getMessage()));
    exit();
}
?>