<?php
// Define o tipo de resposta como JSON
header('Content-Type: application/json');
require '../conexao.php'; // Garante que ele ache a conexão na pasta raiz

$id_categoria = $_GET['id_categoria'] ?? 0;

if ($id_categoria == 0) {
    echo json_encode(['sucesso' => false, 'erro' => 'Categoria não fornecida']);
    exit();
}

try {
    $stmt = $pdo->prepare(
        "SELECT id, nome_tipo 
         FROM tipos_negocio 
         WHERE id_categoria = :id_categoria 
         ORDER BY nome_tipo ASC"
    );
    $stmt->execute(['id_categoria' => $id_categoria]);
    $tipos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['sucesso' => true, 'dados' => $tipos]);

} catch (PDOException $e) {
    echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
}
?>