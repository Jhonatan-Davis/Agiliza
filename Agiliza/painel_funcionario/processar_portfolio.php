<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require '../conexao.php';

// --- Porteiro ---
if (!isset($_SESSION['usuario_id'])) {
    die("Acesso negado.");
}
// [CORREÇÃO] Verifica se é o dono ou o funcionário
$e_dono = ($_SESSION['funcao'] == 'dono');
$pagina_retorno = $e_dono ? '../painel_dono/meu_portfolio.php' : 'meu_portfolio.php';


$id_funcionario = $_SESSION['usuario_id']; // Pega o ID de quem está logado
$id_negocio = $_SESSION['id_negocio'];
$action = $_REQUEST['action'] ?? null;

// --- AÇÃO 1: ADICIONAR FOTO ---
if ($action == 'add' && $_SERVER["REQUEST_METHOD"] == "POST") {
    
    if (!isset($_FILES['foto_portfolio']) || $_FILES['foto_portfolio']['error'] != UPLOAD_ERR_OK) {
        header("Location: $pagina_retorno?erro=Nenhuma foto foi enviada.");
        exit();
    }
    
    $legenda = $_POST['legenda'] ?? 'Trabalho';
    $destaque = isset($_POST['destaque_popup']) ? 1 : 0;

    try {
        $upload_dir = '../uploads/';
        $file_name = uniqid('func_' . $id_funcionario . '_') . '.' . strtolower(pathinfo($_FILES['foto_portfolio']['name'], PATHINFO_EXTENSION));
        $target_file = $upload_dir . $file_name;
        $url_relativa = 'uploads/' . $file_name;

        if (!move_uploaded_file($_FILES['foto_portfolio']['tmp_name'], $target_file)) {
            throw new Exception("Erro ao mover o arquivo. Verifique as permissões da pasta 'uploads'.");
        }
        
        $stmt = $pdo->prepare(
            "INSERT INTO portfolio_fotos (id_funcionario, id_negocio, url_imagem, legenda, destaque_popup)
             VALUES (:id_func, :id_neg, :url, :legenda, :destaque)"
        );
        $stmt->execute([
            'id_func' => $id_funcionario,
            'id_neg' => $id_negocio,
            'url' => $url_relativa,
            'legenda' => $legenda,
            'destaque' => $destaque
        ]);
        
        header("Location: $pagina_retorno?sucesso=1");
        exit();

    } catch (Exception $e) {
        header("Location: $pagina_retorno?erro=" . urlencode($e->getMessage()));
        exit();
    }
}

// --- AÇÃO 2: DELETAR FOTO ---
else if ($action == 'delete' && isset($_GET['id'])) {
    
    $id_foto = $_GET['id'];
    
    try {
        $stmt_get = $pdo->prepare(
            "SELECT url_imagem FROM portfolio_fotos 
             WHERE id = :id_foto AND id_funcionario = :id_func"
        );
        $stmt_get->execute(['id_foto' => $id_foto, 'id_func' => $id_funcionario]);
        $url_imagem = $stmt_get->fetchColumn();

        if ($url_imagem) {
            $caminho_fisico = '../' . $url_imagem;
            if (file_exists($caminho_fisico)) {
                unlink($caminho_fisico);
            }
        }

        $stmt_del = $pdo->prepare(
            "DELETE FROM portfolio_fotos 
             WHERE id = :id_foto AND id_funcionario = :id_func"
        );
        $stmt_del->execute(['id_foto' => $id_foto, 'id_func' => $id_funcionario]);

        header("Location: $pagina_retorno?sucesso=1");
        exit();
        
    } catch (PDOException $e) {
        header("Location: $pagina_retorno?erro=" . urlencode($e->getMessage()));
        exit();
    }
}

header("Location: $pagina_retorno");
exit();
?>