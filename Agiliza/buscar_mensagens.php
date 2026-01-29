<?php
header('Content-Type: application/json');
session_start();
require 'conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['erro' => 'Acesso negado']);
    exit();
}

$id_agendamento = $_GET['id_agendamento'] ?? 0;
$id_usuario_logado = $_SESSION['usuario_id'];

try {
    // ===========================================
    //     [NOVA LÓGICA: MARCAR COMO "LIDO"]
    // ===========================================
    // 1. Marca como 'read' todas as mensagens QUE NÃO SÃO MINHAS
    //    e que ainda estão como 'sent'.
    $stmt_update = $pdo->prepare(
        "UPDATE chat_mensagens SET status = 'read' 
         WHERE id_agendamento = :id_agendamento 
           AND id_remetente != :id_usuario_logado 
           AND status = 'sent'"
    );
    $stmt_update->execute([
        'id_agendamento' => $id_agendamento,
        'id_usuario_logado' => $id_usuario_logado
    ]);

    // ===========================================
    //     [QUERY ATUALIZADA: BUSCAR O STATUS]
    // ===========================================
    // 2. Busca as mensagens E o nome E o novo status
    $stmt = $pdo->prepare(
        "SELECT cm.id_remetente, cm.mensagem, cm.data_envio, cm.status, u.nome as nome_remetente
         FROM chat_mensagens cm
         JOIN usuarios u ON cm.id_remetente = u.id
         WHERE cm.id_agendamento = :id_agendamento
         ORDER BY cm.data_envio ASC"
    );
    $stmt->execute(['id_agendamento' => $id_agendamento]);
    $mensagens = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($mensagens);

} catch (PDOException $e) {
    echo json_encode(['erro' => $e->getMessage()]);
}
?>