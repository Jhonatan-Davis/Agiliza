<?php
session_start();
require '../conexao.php';

// --- Porteiro ---
if (!isset($_SESSION['usuario_id']) || $_SESSION['funcao'] != 'dono') {
    die("Acesso negado.");
}

$id_negocio = $_SESSION['id_negocio'];

// --- ROTEADOR DE AÇÕES ---

// 1. Pegar a Ação (do formulário POST ou do link GET)
$action = $_REQUEST['action'] ?? null; // '??' é um atalho para isset

try {
    // --- AÇÃO 1: ADICIONAR (CREATE) ---
    // ... (dentro do try) ...

    // --- AÇÃO 1: ADICIONAR (CREATE) ---
    if ($action == 'add' && $_SERVER["REQUEST_METHOD"] == "POST") {
        
        $nome = $_POST['nome_servico'];
        $preco = $_POST['preco'];
        $duracao = $_POST['duracao_minutos'];
        // [NOVO] Pega a frequência (ou NULL se estiver vazio)
        $frequencia = !empty($_POST['frequencia_retorno']) ? $_POST['frequencia_retorno'] : NULL;

        $stmt = $pdo->prepare(
            "INSERT INTO servicos (id_negocio, nome_servico, preco, duracao_minutos, frequencia_retorno_dias) 
             VALUES (:id_negocio, :nome, :preco, :duracao, :freq)"
        );
        $stmt->execute([
            'id_negocio' => $id_negocio,
            'nome' => $nome,
            'preco' => $preco,
            'duracao' => $duracao,
            'freq' => $frequencia
        ]);
    
    // --- AÇÃO 2: EDITAR (UPDATE) ---
    } else if ($action == 'edit' && $_SERVER["REQUEST_METHOD"] == "POST") {

        $nome = $_POST['nome_servico'];
        $preco = $_POST['preco'];
        $duracao = $_POST['duracao_minutos'];
        // [NOVO]
        $frequencia = !empty($_POST['frequencia_retorno']) ? $_POST['frequencia_retorno'] : NULL;
        $id_servico = $_POST['id_servico'];

        $stmt = $pdo->prepare(
            "UPDATE servicos SET 
                nome_servico = :nome, 
                preco = :preco, 
                duracao_minutos = :duracao,
                frequencia_retorno_dias = :freq
             WHERE id = :id_servico AND id_negocio = :id_negocio"
        );
        $stmt->execute([
            'nome' => $nome,
            'preco' => $preco,
            'duracao' => $duracao,
            'freq' => $frequencia,
            'id_servico' => $id_servico,
            'id_negocio' => $id_negocio
        ]);
    // --- AÇÃO 3: DELETAR (DELETE) ---
    } else if ($action == 'delete' && isset($_GET['id'])) {
        
        $id_servico = $_GET['id']; // O ID veio do link (GET)

        $stmt = $pdo->prepare(
            "DELETE FROM servicos 
             WHERE id = :id_servico AND id_negocio = :id_negocio" // Dupla checagem
        );
        $stmt->execute([
            'id_servico' => $id_servico,
            'id_negocio' => $id_negocio
        ]);

    } else {
        // Nenhuma ação válida
        throw new Exception("Ação inválida.");
    }

    // --- SUCESSO ---
    // Se chegou até aqui, tudo deu certo. Volta para a página principal de serviços.
    header("Location: meus_servicos.php?sucesso=1");
    exit();

} catch (Exception $e) {
    // --- ERRO ---
    // (O erro 23000 é 'Integrity constraint violation', ou seja,
    // tentou deletar um serviço que já tem agendamentos marcados)
    if ($e->getCode() == 23000) {
         die("Erro: Você não pode excluir um serviço que já possui agendamentos. <br>
              <a href='meus_servicos.php'>Voltar</a>");
    }
    
    die("Ocorreu um erro: " . $e->getMessage());
}
?>