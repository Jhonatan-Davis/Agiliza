<?php
header('Content-Type: application/json');
session_start();
require '../conexao.php'; // Sobe um nível para achar a conexão

// --- Porteiro ---
if (!isset($_SESSION['usuario_id']) || $_SESSION['funcao'] != 'funcionario') {
    echo json_encode(['sucesso' => false, 'erro' => 'Acesso negado']);
    exit();
}

$id_funcionario = $_SESSION['usuario_id'];
$id_negocio = $_SESSION['id_negocio'];

try {
    // [CORREÇÃO] A consulta SQL agora também busca o "cm.id_agendamento"
    $stmt = $pdo->prepare(
        "SELECT 
            cm.id_agendamento, -- <-- A LINHA QUE FALTAVA
            cm.mensagem, 
            cm.data_envio, 
            u.nome AS cliente_nome, 
            s.nome_servico
         FROM chat_mensagens cm
         JOIN agendamentos a ON cm.id_agendamento = a.id
         JOIN usuarios u ON a.id_cliente = u.id
         JOIN servicos s ON a.id_servico = s.id
         WHERE a.id_funcionario = :id_funcionario
           AND cm.status = 'sent' 
           AND cm.id_remetente != :id_funcionario
         ORDER BY cm.data_envio DESC
         LIMIT 10"
    );
    
    $stmt->execute(['id_funcionario' => $id_funcionario]);
    $notificacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'sucesso' => true,
        'count' => count($notificacoes),
        'mensagens' => $notificacoes
    ]);

} catch (PDOException $e) {
    echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
    exit();
}
?>