<?php
// --- Porteiro (Segurança) ---
// Define que esta página é para o DONO
$_SESSION['funcao_original'] = 'dono'; 

// --- Puxa o arquivo do funcionário ---
// O código abaixo "puxa" o arquivo do painel do funcionário
// e o executa como se estivesse aqui.
// (O arquivo do funcionário vai ler o $_SESSION['usuario_id'] do dono)
require '../painel_funcionario/meu_portfolio.php';
?>