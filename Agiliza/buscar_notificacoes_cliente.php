<?php
header('Content-Type: application/json');
session_start();
require 'conexao.php';
date_default_timezone_set('America/Sao_Paulo');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['sucesso' => false]);
    exit();
}

$id_cliente = $_SESSION['usuario_id'];
$notificacoes = [];

try {
    // 1. BUSCAR MENSAGENS DE CHAT NÃO LIDAS
    $stmt_chat = $pdo->prepare(
        "SELECT 
            cm.id_agendamento,
            cm.mensagem, 
            cm.data_envio,
            n.nome_negocio as titulo,
            'chat' as tipo
         FROM chat_mensagens cm
         JOIN agendamentos a ON cm.id_agendamento = a.id
         JOIN negocios n ON a.id_negocio = n.id
         WHERE a.id_cliente = :id_cliente
           AND cm.id_remetente != :id_cliente -- Mensagem que não fui eu que mandei
           AND cm.status = 'sent' -- Não lida
         ORDER BY cm.data_envio DESC"
    );
    $stmt_chat->execute(['id_cliente' => $id_cliente]);
    $chats = $stmt_chat->fetchAll(PDO::FETCH_ASSOC);

    // 2. BUSCAR "NOTIFICAÇÕES DE SISTEMA" (Agendamentos recentes)
    // Mostra os últimos 5 agendamentos criados ou atualizados recentemente
    $stmt_agenda = $pdo->prepare(
        "SELECT 
            a.id as id_agendamento,
            n.nome_negocio,
            s.nome_servico,
            a.status,
            a.data_hora_inicio as data_evento
         FROM agendamentos a
         JOIN negocios n ON a.id_negocio = n.id
         JOIN servicos s ON a.id_servico = s.id
         WHERE a.id_cliente = :id_cliente
         ORDER BY a.data_hora_inicio DESC
         LIMIT 5"
    );
    $stmt_agenda->execute(['id_cliente' => $id_cliente]);
    $agendas = $stmt_agenda->fetchAll(PDO::FETCH_ASSOC);

    // --- MONTAR A LISTA ---
    
    // Adiciona Chats
    foreach ($chats as $c) {
        $notificacoes[] = [
            'titulo' => 'Nova mensagem de ' . $c['titulo'],
            'texto' => $c['mensagem'],
            'data' => $c['data_envio'],
            'link' => 'chat.php?id=' . $c['id_agendamento'],
            'tipo' => 'chat'
        ];
    }

    // Adiciona Agendas (Simulando o Email)
    foreach ($agendas as $a) {
        $texto = "";
        $titulo = "";
        
        if ($a['status'] == 'confirmado') {
            $titulo = "Agendamento Confirmado";
            $texto = "Seu horário em " . $a['nome_negocio'] . " está marcado.";
        } else if ($a['status'] == 'cancelado') {
            $titulo = "Agendamento Cancelado";
            $texto = "O agendamento em " . $a['nome_negocio'] . " foi cancelado.";
        } else if ($a['status'] == 'concluido') {
            $titulo = "Serviço Concluído";
            $texto = "Avalie seu atendimento em " . $a['nome_negocio'] . ".";
        }

        $notificacoes[] = [
            'titulo' => $titulo,
            'texto' => $texto,
            'data' => $a['data_evento'], // Usamos a data do agendamento como referência
            'link' => 'meus_agendamentos.php',
            'tipo' => 'sistema'
        ];
    }

    // Conta apenas as de CHAT para o número vermelho (para não ficar chato)
    $nao_lidas = count($chats);

    echo json_encode([
        'sucesso' => true,
        'count' => $nao_lidas,
        'lista' => $notificacoes
    ]);

} catch (PDOException $e) {
    echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
}
?>