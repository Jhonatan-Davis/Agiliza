<?php
session_start();
require '../conexao.php';

// --- Porteiro ---
if (!isset($_SESSION['usuario_id']) || $_SESSION['funcao'] != 'dono') {
    die("Acesso negado.");
}

$id_negocio = $_SESSION['id_negocio'];

// Pega a Ação (do formulário POST ou do link GET)
$action = $_REQUEST['action'] ?? null;

try {

    // --- AÇÃO 1: SALVAR A CONFIGURAÇÃO (Sim/Não para feriados nacionais) ---
    if ($action == 'salvar_config' && $_SERVER["REQUEST_METHOD"] == "POST") {
        
        // Verifica se o checkbox "bloquear_feriados" foi marcado
        $bloquear = isset($_POST['bloquear_feriados']) ? 1 : 0;
        
        $stmt = $pdo->prepare("UPDATE negocios SET bloquear_feriados_auto = :bloquear WHERE id = :id_negocio");
        $stmt->execute([
            'bloquear' => $bloquear,
            'id_negocio' => $id_negocio
        ]);
    }

    // --- AÇÃO 2: ADICIONAR FERIADO LOCAL ---
    else if ($action == 'add_feriado' && $_SERVER["REQUEST_METHOD"] == "POST") {

        $data = $_POST['data_feriado'];
        $descricao = $_POST['descricao_feriado'];

        // Garante que a descrição não esteja vazia
        if (empty($data) || empty($descricao)) {
            die("Erro: Data e Descrição são obrigatórios. <a href='meus_horarios.php'>Voltar</a>");
        }

        $stmt = $pdo->prepare("INSERT INTO feriados_personalizados (id_negocio, data, descricao) VALUES (:id_negocio, :data, :desc)");
        $stmt->execute([
            'id_negocio' => $id_negocio,
            'data' => $data,
            'desc' => $descricao
        ]);
    }
    
    // --- AÇÃO 3: DELETAR FERIADO LOCAL ---
    else if ($action == 'del_feriado' && isset($_GET['id'])) {
        
        $id_feriado = $_GET['id'];
        
        // Checagem de segurança: só deleta se o feriado for DESTE negócio
        $stmt = $pdo->prepare("DELETE FROM feriados_personalizados WHERE id = :id AND id_negocio = :id_negocio");
        $stmt->execute([
            'id' => $id_feriado,
            'id_negocio' => $id_negocio
        ]);
    }
    
    // Se nenhuma ação válida foi passada
    else {
        die("Ação inválida. <a href='meus_horarios.php'>Voltar</a>");
    }

    // Sucesso! Volta para a página de horários
    header("Location: meus_horarios.php?sucesso=1");
    exit();

} catch (PDOException $e) {
    // Erro 23000 (Integrity constraint) = já cadastrou essa data
    if ($e->getCode() == 23000) {
         die("Erro: Você já cadastrou um feriado para esta data. <br>
              <a href='meus_horarios.php'>Voltar</a>");
    }
    die("Ocorreu um erro: " . $e->getMessage());
}
?>