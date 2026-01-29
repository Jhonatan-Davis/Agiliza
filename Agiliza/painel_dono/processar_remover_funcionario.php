<?php
session_start();
require '../conexao.php';

// --- Porteiro ---
if (!isset($_SESSION['usuario_id']) || $_SESSION['funcao'] != 'dono') {
    die("Acesso negado.");
}

// 1. Verificar se um ID foi enviado pela URL
if (!isset($_GET['id'])) {
    die("ID do funcionário não fornecido.");
}

$id_funcionario_para_remover = $_GET['id'];
$id_negocio = $_SESSION['id_negocio'];

try {
    // 2. A Mágica: Remover da tabela 'negocio_membros'
    // Nós NÃO deletamos o usuário da tabela 'usuarios' (ele pode ser cliente de outra loja)
    // Nós SÓ removemos a LIGAÇÃO dele com o NOSSO negócio.
    
    $stmt = $pdo->prepare(
        "DELETE FROM negocio_membros 
         WHERE id_usuario = :id_funcionario 
         AND id_negocio = :id_negocio 
         AND funcao = 'funcionario'"
    );
    
    $stmt->execute([
        'id_funcionario' => $id_funcionario_para_remover,
        'id_negocio' => $id_negocio
    ]);

    // 3. Sucesso! Voltar para a página da equipe
    header("Location: minha_equipe.php?removido=1");
    exit();

} catch (PDOException $e) {
    die("Erro ao remover funcionário: " . $e->getMessage());
}
?>