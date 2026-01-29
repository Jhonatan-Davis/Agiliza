<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escolha seu Perfil - Agiliza</title>
    <link rel="stylesheet" href="../login/style_login.css">
    
    <style>
        .login-container {
            /* Um pouco maior para caber os botões */
            max-width: 450px; 
        }
        .selecao-botao {
            display: block; /* Faz o link ocupar a linha inteira */
            text-decoration: none;
            text-align: center;
            padding: 1rem;
            border-radius: 6px;
            font-size: 1.1rem;
            font-weight: bold;
            margin-bottom: 1rem;
            transition: transform 0.2s ease;
        }
        .selecao-botao:hover {
            transform: scale(1.03); /* Efeito de "levantar" */
        }
        
        /* O botão "Cliente" (cor principal) */
        .btn-cliente {
            background-color: #7E57C2; /* Nosso Roxo */
            color: white;
        }
        
        /* O botão "Dono" (cor secundária) */
        .btn-dono {
            background-color: #333; /* Um cinza escuro */
            color: #e0e0e0;
            border: 1px solid #7E57C2; /* Borda com a cor principal */
        }
        
        .login-links p {
            color: #b0b0b0; /* Texto de ajuda */
        }
    </style>
</head>
<body>

    <div class="login-container">
        <h1>Como você quer usar o Agiliza?</h1>

        <p style="text-align:center; color: #b0b0b0; margin-top: -1rem; margin-bottom: 2rem;">
            Escolha seu tipo de conta para começarmos.
        </p>
        
        <a href="cadastro.php?tipo=cliente" class="selecao-botao btn-cliente">
            Sou Cliente
            <span style="display:block; font-size: 0.8rem; font-weight: normal;">Quero agendar horários em negócios.</span>
        </a>
        
        <a href="cadastro.php?tipo=dono" class="selecao-botao btn-dono">
            Sou Dono(a) de um Negócio
            <span style="display:block; font-size: 0.8rem; font-weight: normal;">Quero gerenciar minha agenda e clientes.</span>
        </a>
        
        <div class="login-links">
            <p>Já tem uma conta? <a href="../login/login.php">Faça login</a></p>
        </div>
    </div>

</body>
</html>