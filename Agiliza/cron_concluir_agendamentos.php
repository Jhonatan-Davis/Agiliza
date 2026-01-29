<?php
// Este script é o nosso "robô"
// Ele deve ser chamado automaticamente pelo servidor (Cron Job)

require 'conexao.php';
date_default_timezone_set('America/Sao_Paulo');

echo "Iniciando robô de conclusão de agendamentos...\n";

try {
    // Pega a data e hora ATUAL
    $agora = date('Y-m-d H:i:s');

    // 1. A LÓGICA PRINCIPAL:
    // Encontra todos os agendamentos que já terminaram
    // (data_hora_fim é ANTERIOR a agora)
    // E que AINDA estão com o status "confirmado"
    
    $stmt = $pdo->prepare(
        "UPDATE agendamentos SET status = 'concluido' 
         WHERE data_hora_fim < :agora 
         AND status = 'confirmado'"
    );
    
    $stmt->execute(['agora' => $agora]);

    // 2. Reporta o que fez
    $agendamentos_concluidos = $stmt->rowCount(); // Conta quantas linhas foram afetadas

    if ($agendamentos_concluidos > 0) {
        echo "Sucesso! $agendamentos_concluidos agendamentos foram marcados como 'concluido'.\n";
    } else {
        echo "Nenhum agendamento para concluir no momento.\n";
    }

} catch (PDOException $e) {
    echo "ERRO FATAL: " . $e->getMessage();
}

echo "Robô finalizado.";
?>