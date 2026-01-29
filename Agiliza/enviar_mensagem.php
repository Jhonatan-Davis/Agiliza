<?php
session_start();
require 'conexao.php';

if (!isset($_SESSION['usuario_id']) || $_SERVER["REQUEST_METHOD"] != "POST") {
    die("Acesso negado.");
}

$id_agendamento = $_POST['id_agendamento'] ?? 0;
$id_remetente = $_SESSION['usuario_id'];
$mensagem = trim($_POST['mensagem'] ?? '');

if (empty($id_agendamento) || empty($mensagem)) {
    die("Dados inválidos.");
}

try {
    // TODO: Adicionar checagem de 3 dias aqui também (segurança)

    $stmt = $pdo->prepare(
        "INSERT INTO chat_mensagens (id_agendamento, id_remetente, mensagem)
         VALUES (:id_agendamento, :id_remetente, :mensagem)"
    );
    $stmt->execute([
        'id_agendamento' => $id_agendamento,
        'id_remetente' => $id_remetente,
        'mensagem' => $mensagem
    ]);
    
    // Retorna um JSON de sucesso (o JS não usa, mas é bom)
    echo json_encode(['sucesso' => true]);
    
    // TODO: Enviar notificação por e-mail para o "outro" usuário
    
} catch (PDOException $e) {
    echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
}
?>