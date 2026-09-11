<?php require_once __DIR__ . '/../config/bootstrap.php'; ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escolha seu Perfil - Agiliza</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/views/login/style_selecao.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="selection-container">

    <!-- Área Principal com Divisão Diagonal -->
    <div class="selection-main">

        <!-- LADO CLIENTE -->
        <div class="side-client">
            <div class="side-content">
                <div class="illustration-container">
                    <img src="<?= PUBLIC_URL ?>/uploads/cliente-selecao.png" alt="Ilustração de cliente agendando serviço" class="card-img">
                </div>

                <h1 class="side-title">Sou Cliente</h1>
                <p class="side-description">Encontre profissionais, serviços e agende seus horários. <strong>Tudo ao seu alcance.</strong></p>

                <div class="card-actions">
                    <a href="cadastro.php?tipo=cliente" class="btn-action btn-primary-custom">
                        Continuar como Cliente
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- LADO DONO DE NEGÓCIO -->
        <div class="side-business">
            <div class="side-content">
                <div class="illustration-container">
                    <img src="<?= PUBLIC_URL ?>/uploads/dono-selecao.png" alt="Ilustração de dono de negócio gerenciando agenda" class="card-img">
                </div>

                <h1 class="side-title">Sou Dono de Negócio</h1>
                <p class="side-description">Cadastre seu negócio, gerencie clientes e organize sua agenda. <strong>Simplify sua rotina.</strong></p>

                <div class="card-actions">
                    <a href="cadastro.php?tipo=dono" class="btn-action btn-primary-custom">
                        Cadastrar Meu Negócio
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>

    </div>

    <!-- Footer com pílula flutuante -->
    <div class="footer-login-container">
        <span class="footer-text">Já possui uma conta? <a href="<?= BASE_URL ?>/views/login/login.php">Entrar</a></span>
    </div>

</div>

</body>
</html>