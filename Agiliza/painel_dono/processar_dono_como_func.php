<?php
session_start();
require '../conexao.php'; // Sobe um nível para achar a conexão

// --- Porteiro ---
if (!isset($_SESSION['usuario_id']) || $_SESSION['funcao'] != 'dono') {
    die("Acesso negado.");
}

// Verifica se o formulário foi enviado (segurança)
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: minha_equipe.php");
    exit();
}

$id_dono = $_SESSION['usuario_id'];
$id_negocio = $_SESSION['id_negocio'];

try {
    // Tenta inserir o Dono como "funcionario"
    // Usamos 'INSERT IGNORE' para o caso de já existir (evita erros)
    $stmt = $pdo->prepare(
        "INSERT IGNORE INTO negocio_membros (id_usuario, id_negocio, funcao) 
         VALUES (:id_usuario, :id_negocio, 'funcionario')"
    );
    
    $stmt->execute([
        'id_usuario' => $id_dono,
        'id_negocio' => $id_negocio
    ]);

    // Sucesso! Volta para a página da equipe
    header("Location: minha_equipe.php?sucesso=dono_adicionado");
    exit();

} catch (PDOException $e) {
    header("Location: minha_equipe.php?erro=" . urlencode($e->getMessage()));
    exit();
}
?>