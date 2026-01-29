<?php
// Se a sessão não foi iniciada, inicia
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- Porteiro (Segurança) ---
// Define que esta página é para o DONO
$_SESSION['funcao_original'] = 'dono'; 

// --- Puxa o arquivo do funcionário ---
// (O arquivo do funcionário vai ler o $_SESSION['usuario_id'] do dono)
require '../painel_funcionario/meu_perfil.php';
?>