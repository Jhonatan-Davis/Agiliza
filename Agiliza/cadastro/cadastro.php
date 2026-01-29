<?php
session_start();
// Se já estiver logado, redireciona
if (isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Conta - Agiliza</title>
    <link rel="stylesheet" href="../login/style_login.css"> 
    <link rel="shortcut icon" href="../uploads/logo_agiliza.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Estilo extra para os requisitos de senha */
        #requisitos-senha {
            display: none; /* Escondido por padrão */
            background-color: #f9f9f9;
            padding: 10px;
            margin-top: 5px;
            border-radius: 6px;
            border: 1px solid #eee;
            font-size: 0.85rem;
        }
        #requisitos-senha h4 {
            margin: 0 0 5px 0;
            color: #333;
            font-size: 0.9rem;
        }
        #requisitos-senha ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        #requisitos-senha li {
            margin-bottom: 3px;
            color: #ff5555; /* Vermelho (inválido) */
            transition: color 0.3s ease;
        }
        #requisitos-senha li.valido {
            color: #4CAF50; /* Verde (válido) */
            text-decoration: line-through;
            opacity: 0.7;
        }
    </style>
</head>
<body>
    <div class="login-container" style="max-width: 400px;">
        <div style="text-align: center; margin-bottom: 10px;">
            <img src="../uploads/logo_agiliza.png" alt="Agiliza" style="width: 60px;">
        </div>

        <h1>Crie sua Conta</h1>
        
        <?php
            if (isset($_GET['erro'])) {
                $erro = $_GET['erro'];
                $msg = "Erro ao cadastrar!";
                if ($erro == 'email_existe') $msg = "Este e-mail já está em uso.";
                if ($erro == 'senhas_diferentes') $msg = "As senhas não coincidem.";
                if ($erro == 'senha_curta') $msg = "A senha é muito fraca.";
                echo '<div class="error-message">'.$msg.'</div>';
            }
        ?>

        <form action="processar_cadastro.php" method="POST" autocomplete="off">
            
            <div class="form-group">
                <label for="nome">Nome Completo</label>
                <input type="text" id="nome" name="nome" placeholder="Seu nome" required autocomplete="off">
            </div>

            <div class="form-group">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" placeholder="Seu melhor e-mail" required autocomplete="off">
            </div>

            <div class="form-group">
                <label for="telefone">Telefone (com DDD)</label>
                <input type="text" id="telefone" name="telefone" placeholder="(11) 99999-9999" required autocomplete="off">
            </div>
            
            <div class="form-group">
                <label for="senha">Senha</label>
                <div class="password-wrapper">
                    <input type="password" id="senha" name="senha" placeholder="Crie uma senha forte" required>
                    <i class="fas fa-eye toggle-password" onclick="toggleSenha('senha', this)"></i>
                </div>
                
                <div id="requisitos-senha">
                    <h4>A senha deve conter:</h4>
                    <ul>
                        <li id="req-comprimento">Pelo menos 6 caracteres</li>
                        <li id="req-maiuscula">Pelo menos 1 letra maiúscula (A-Z)</li>
                        <li id="req-numero">Pelo menos 1 número (0-9)</li>
                    </ul>
                </div>
            </div>

            <div class="form-group">
                <label for="confirma_senha">Confirme sua Senha</label>
                <div class="password-wrapper">
                    <input type="password" id="confirma_senha" name="confirma_senha" placeholder="Repita a senha" required>
                    <i class="fas fa-eye toggle-password" onclick="toggleSenha('confirma_senha', this)"></i>
                </div>
            </div>
            
            <button type="submit" class="login-button">Cadastrar</button>
        </form>
        
        <div class="login-links">
            <a href="../login/login.php">Já tem uma conta? Faça Login</a>
        </div>
    </div>

    <script>
        // 1. Mostrar/Esconder Senha
        function toggleSenha(inputId, iconElement) {
            const input = document.getElementById(inputId);
            if (input.type === "password") {
                input.type = "text";
                iconElement.classList.remove('fa-eye');
                iconElement.classList.add('fa-eye-slash');
            } else {
                input.type = "password";
                iconElement.classList.remove('fa-eye-slash');
                iconElement.classList.add('fa-eye');
            }
        }

        // 2. Validação de Senha em Tempo Real
        const inputSenha = document.getElementById('senha');
        const requisitosBox = document.getElementById('requisitos-senha');
        const reqComprimento = document.getElementById('req-comprimento');
        const reqMaiuscula = document.getElementById('req-maiuscula');
        const reqNumero = document.getElementById('req-numero');

        // Mostra a caixa ao clicar
        inputSenha.addEventListener('focus', () => {
            requisitosBox.style.display = 'block';
        });

        // Valida enquanto digita
        inputSenha.addEventListener('input', () => {
            const valor = inputSenha.value;

            // Comprimento
            if (valor.length >= 6) {
                reqComprimento.classList.add('valido');
            } else {
                reqComprimento.classList.remove('valido');
            }

            // Letra Maiúscula
            if (/[A-Z]/.test(valor)) {
                reqMaiuscula.classList.add('valido');
            } else {
                reqMaiuscula.classList.remove('valido');
            }

            // Número
            if (/[0-9]/.test(valor)) {
                reqNumero.classList.add('valido');
            } else {
                reqNumero.classList.remove('valido');
            }
        });
        
        // 3. Máscara de Telefone (Bônus)
        const inputTel = document.getElementById('telefone');
        inputTel.addEventListener('input', (e) => {
            let x = e.target.value.replace(/\D/g, '').match(/(\d{0,2})(\d{0,5})(\d{0,4})/);
            e.target.value = !x[2] ? x[1] : '(' + x[1] + ') ' + x[2] + (x[3] ? '-' + x[3] : '');
        });
    </script>
</body>
</html>