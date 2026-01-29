<?php
session_start();
require '../conexao.php';
date_default_timezone_set('America/Sao_Paulo');

// --- Porteiro ---
if (!isset($_SESSION['usuario_id']) || $_SESSION['funcao'] != 'funcionario') {
    die("Acesso negado.");
}

$id_funcionario = $_SESSION['usuario_id'];
$action = $_REQUEST['action'] ?? null;

try {
    // --- AÇÃO 1: ADICIONAR BLOQUEIO ---
    if ($action == 'add' && $_SERVER["REQUEST_METHOD"] == "POST") {
        
        $motivo = $_POST['motivo'];
        $data = $_POST['data_bloqueio'];
        $hora_inicio = $_POST['hora_inicio'];
        $hora_fim = $_POST['hora_fim'];

        // Validação básica
        if (empty($motivo) || empty($data) || empty($hora_inicio) || empty($hora_fim)) {
            header("Location: meus_bloqueios.php?erro=Preencha todos os campos.");
            exit();
        }
        
        // Combina data e hora para o formato DATETIME do banco
        $data_hora_inicio_str = $data . ' ' . $hora_inicio . ':00';
        $data_hora_fim_str = $data . ' ' . $hora_fim . ':00';

        // Validação de tempo
        if (new DateTime($data_hora_fim_str) <= new DateTime($data_hora_inicio_str)) {
            header("Location: meus_bloqueios.php?erro=A hora de fim deve ser maior que a hora de início.");
            exit();
        }

        // Salva no banco
        $stmt = $pdo->prepare(
            "INSERT INTO horarios_bloqueados (id_funcionario, id_negocio, motivo, data_hora_inicio, data_hora_fim)
             VALUES (:id_func, :id_neg, :motivo, :inicio, :fim)"
        );
        $stmt->execute([
            'id_func' => $id_funcionario,
            'id_neg' => $_SESSION['id_negocio'], // Pega o ID do negócio da sessão
            'motivo' => $motivo,
            'inicio' => $data_hora_inicio_str,
            'fim' => $data_hora_fim_str
        ]);
    }

    // --- AÇÃO 2: DELETAR BLOQUEIO ---
    else if ($action == 'delete' && isset($_GET['id'])) {
        
        $id_bloqueio = $_GET['id'];
        
        // Segurança: Só pode deletar se o bloqueio for DELE
        $stmt = $pdo->prepare(
            "DELETE FROM horarios_bloqueados 
             WHERE id = :id_bloqueio AND id_funcionario = :id_funcionario"
        );
        $stmt->execute([
            'id_bloqueio' => $id_bloqueio,
            'id_funcionario' => $id_funcionario
        ]);
    }
    
    // Se chegou aqui, deu certo
    header("Location: meus_bloqueios.php?sucesso=1");
    exit();

} catch (Exception $e) {
    header("Location: meus_bloqueios.php?erro=" . urlencode($e->getMessage()));
    exit();
}
?>