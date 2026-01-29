<?php
// 1. Iniciar a sessão e conexão
session_start();
require 'conexao.php';

// 2. O "Porteiro" (Auth-Guard)
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login/login.php");
    exit();
}

$id_usuario = $_SESSION['usuario_id'];

try {
    // 3. Pergunta ao banco: "Quem é esse usuário?"
    
    // [A CORREÇÃO ESTÁ AQUI]
    // Esta nova consulta SQL ORDENA por prioridade.
    // 'dono' SEMPRE virá antes de 'funcionario'.
    $stmt = $pdo->prepare(
        "SELECT * FROM negocio_membros 
         WHERE id_usuario = :id_usuario
         ORDER BY FIELD(funcao, 'dono', 'funcionario') 
         LIMIT 1" // Pega o primeiro (que agora é o de maior prioridade)
    );
    $stmt->execute(['id_usuario' => $id_usuario]);
    $membro = $stmt->fetch(PDO::FETCH_ASSOC);

    // 4. A Decisão (O Roteamento)
    
    if ($membro) {
        // --- O USUÁRIO É MEMBRO DE UM NEGÓCIO ---
        
        $_SESSION['funcao'] = $membro['funcao'];
        $_SESSION['id_negocio'] = $membro['id_negocio'];

        // Como 'dono' sempre vem primeiro, esta lógica agora é 100% segura.
        if ($membro['funcao'] == 'dono') {
            header("Location: painel_dono/index.php");
            exit();
            
        } else if ($membro['funcao'] == 'funcionario') {
            header("Location: painel_funcionario/index.php");
            exit();
        }

    } else {
        // --- O USUÁRIO É UM CLIENTE ---
        header("Location: index.php");
        exit();
    }

} catch (PDOException $e) {
    session_unset();
    session_destroy();
    header("Location: login/login.php?erro=db");
    exit();
}
?>