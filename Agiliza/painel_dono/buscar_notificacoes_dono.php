<?php
header('Content-Type: application/json');
session_start();
require '../conexao.php'; // Sobe um nível

// --- Porteiro ---
if (!isset($_SESSION['usuario_id']) || $_SESSION['funcao'] != 'dono') {
    echo json_encode(['sucesso' => false, 'erro' => 'Acesso negado']);
    exit();
}

$id_negocio = $_SESSION['id_negocio'];

try {
    // A consulta SQL do DONO:
    // Pega todas as mensagens não lidas ('sent')
    // Onde o remetente NÃO é o dono (ou seja, msgs de clientes OU funcionários)
    // E que sejam deste negócio.
    $stmt = $pdo->prepare(
        "SELECT 
            cm.id_agendamento,
            cm.mensagem, 
            cm.data_envio, 
            u_remetente.nome AS remetente_nome,
            s.nome_servico
         FROM chat_mensagens cm
         JOIN agendamentos a ON cm.id_agendamento = a.id
         JOIN usuarios u_remetente ON cm.id_remetente = u_remetente.id
         JOIN servicos s ON a.id_servico = s.id
         WHERE a.id_negocio = :id_negocio
           AND cm.status = 'sent' 
           AND cm.id_remetente != :id_dono -- Mensagens que o Dono não enviou
         ORDER BY cm.data_envio DESC
         LIMIT 10"
    );
    
    $stmt->execute([
        'id_negocio' => $id_negocio,
        'id_dono' => $_SESSION['usuario_id']
    ]);
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