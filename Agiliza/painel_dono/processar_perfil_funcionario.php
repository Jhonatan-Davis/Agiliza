<?php
// Se a sessão não foi iniciada, inicia
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Define que esta página é para o DONO
$_SESSION['funcao_original'] = 'dono'; 

// Puxa o cérebro do funcionário
require '../painel_funcionario/processar_perfil_funcionario.php';
?>