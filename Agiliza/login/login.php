<?php
session_start();
// Se o usuário já estiver logado, redireciona
if (isset($_SESSION['usuario_id'])) {
    if ($_SESSION['funcao'] == 'dono') {
        header("Location: ../painel_dono/index.php");
    } else if ($_SESSION['funcao'] == 'funcionario') {
        header("Location: ../painel_funcionario/index.php");
    } else {
        header("Location: ../index.php");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Agiliza</title>
    <link rel="stylesheet" href="style_login.css">
    <link rel="shortcut icon" href="../uploads/logo_agiliza.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <div style="text-align: center; margin-bottom: 10px;">
            <img src="../uploads/logo_agiliza.png" alt="Agiliza" style="width: 60px;">
        </div>
        
        <h1>Login</h1>
        
        <?php
            if (isset($_GET['erro'])) {
                echo '<div class="error-message">E-mail ou senha incorretos!</div>';
            }
            if (isset($_GET['status']) && $_GET['status'] == 'logout') {
                echo '<div class="error-message" style="background-color: #4CAF50;">Você saiu com sucesso!</div>';
            }
        ?>

        <form action="processar_login.php" method="POST" autocomplete="off">
            
            <div class="form-group">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" placeholder="Seu e-mail" required autocomplete="off">
            </div>
            
            <div class="form-group">
                <label for="senha">Senha</label>
                
                <div class="password-wrapper">
                    <input type="password" id="senha" name="senha" placeholder="Sua senha" required>
                    <i class="fas fa-eye toggle-password" onclick="toggleSenha('senha', this)"></i>
                </div>
            </div>
            
            <button type="submit" class="login-button">Entrar</button>
        </form>
        
        <div class="login-links">
            <a href="../cadastro/cadastro.php">Não tem uma conta? Cadastre-se</a>
            <br><br>
            <a href="../index.php" style="color: #555; font-weight: normal;">&larr; Voltar ao site</a>
        </div>
    </div>

    <script>
        function toggleSenha(inputId, iconElement) {
            const input = document.getElementById(inputId);
            
            if (input.type === "password") {
                // Mostra a senha
                input.type = "text";
                // Troca o ícone para "olho cortado"
                iconElement.classList.remove('fa-eye');
                iconElement.classList.add('fa-eye-slash');
            } else {
                // Esconde a senha
                input.type = "password";
                // Troca o ícone para "olho normal"
                iconElement.classList.remove('fa-eye-slash');
                iconElement.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>