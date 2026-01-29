<?php
// Define o tipo de resposta como JSON
header('Content-Type: application/json');

session_start();
require 'conexao.php'; // Inclui a conexão

// --- Porteiro (Só usuários logados) ---
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['sucesso' => false, 'erro' => 'Usuário não logado.']);
    exit();
}

$id_cliente = $_SESSION['usuario_id'];
$id_negocio = $_POST['id_negocio'] ?? 0;
$action = $_POST['action'] ?? ''; // 'adicionar' ou 'remover'

if ($id_negocio == 0 || !in_array($action, ['adicionar', 'remover'])) {
    echo json_encode(['sucesso' => false, 'erro' => 'Parâmetros inválidos.']);
    exit();
}

try {
    if ($action == 'adicionar') {
        // Tenta inserir, mas se já existir, não dá erro (IGNORE)
        $stmt = $pdo->prepare(
            "INSERT IGNORE INTO favoritos (id_cliente, id_negocio) 
             VALUES (:id_cliente, :id_negocio)"
        );
    } else { // action == 'remover'
        $stmt = $pdo->prepare(
            "DELETE FROM favoritos 
             WHERE id_cliente = :id_cliente AND id_negocio = :id_negocio"
        );
    }
    
    $stmt->execute([
        'id_cliente' => $id_cliente,
        'id_negocio' => $id_negocio
    ]);

    echo json_encode(['sucesso' => true]);

} catch (PDOException $e) {
    echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
}
?>