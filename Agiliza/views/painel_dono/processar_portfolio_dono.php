<?php
// Define que esta página é para o DONO
$_SESSION['funcao_original'] = 'dono'; 
// Puxa o cérebro do funcionário
require_once dirname(__DIR__) . '/painel_funcionario/processar_portfolio.php';
?>