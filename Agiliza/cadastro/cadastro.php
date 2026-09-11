<?php
require_once __DIR__ . '/../config/bootstrap.php';
session_start();

$tipo_cadastro = $_GET['tipo'] ?? '';
if (!in_array($tipo_cadastro, ['cliente', 'dono'], true)) {
    header("Location: " . BASE_URL . "/cadastro/selecao.php");
    exit();
}

// Se já estiver logado, redireciona
if (isset($_SESSION['usuario_id'])) {
    header("Location: " . PUBLIC_URL . "/index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Conta - Agiliza</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <!-- NOVO: CSS específico do wizard de cadastro -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/views/login/style_cadastro.css">
    
    <style>
        /* Estilo extra para os requisitos de senha - MANTIDO COMPATÍVEL */
        #requisitos-senha {
            display: none;
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
            color: #ff5555;
            transition: color 0.3s ease;
        }
        #requisitos-senha li.valido {
            color: #4CAF50;
            text-decoration: line-through;
            opacity: 0.7;
        }
    </style>
</head>
<body>

<!-- Container do Wizard de Cadastro -->
<div class="business-setup">
    
    <!-- Header com kicker e progresso -->
    <div class="setup-kicker">
        <i class="fas fa-user"></i> 
        <?php 
        $titulo_kicker = $tipo_cadastro === 'dono' ? 'Configuração inicial' : 'Bem-vindo';
        $subtitulo = $tipo_cadastro === 'dono' ? 'Vamos configurar sua empresa.' : 'Vamos começar criando sua conta.';
        echo $titulo_kicker;
        ?>
    </div>
    
    <h1>Criar Conta</h1>
    <p class="setup-subtitle">
        <?php 
        $subtitulo_desc = $tipo_cadastro === 'dono' 
            ? 'Preencha seus dados para começar a gerenciar seu negócio.' 
            : 'Preencha seus dados para começar a usar o Agiliza.';
        echo $subtitulo_desc;
        ?>
    </p>

    <div class="setup-progress" aria-label="Etapas do cadastro">
        <div class="setup-progress-step active" data-progress="1">
            <span class="setup-progress-dot">1</span><small>Dados Pessoais</small>
        </div>
        <span class="setup-progress-line"></span>
        <div class="setup-progress-step" data-progress="2">
            <span class="setup-progress-dot">2</span><small>Segurança</small>
        </div>
    </div>

    <!-- Container do Formulário com Painéis de Etapas -->
    <form class="business-setup-form" action="processar_cadastro.php" method="POST" autocomplete="off">
        <input type="hidden" name="tipo_usuario" value="<?= htmlspecialchars($tipo_cadastro) ?>">
        
        <section class="setup-panel active" data-step="1">
            <span class="setup-panel-kicker">Etapa 1 de 2</span>
            <h2>Vamos começar pelo básico</h2>
            <p class="setup-panel-description">Como seu nome e contato devem aparecer no Agiliza.</p>
            
            <!-- Mensagens de Erro -->
            <?php
                $erro_cadastro = $_SESSION['erro_cadastro'] ?? null;
                unset($_SESSION['erro_cadastro']);
                
                if ($erro_cadastro) {
                    echo '<div class="error-message">' . htmlspecialchars($erro_cadastro) . '</div>';
                }
                if (isset($_GET['erro'])) {
                    $erro = $_GET['erro'];
                    $msg = "Erro ao cadastrar!";
                    if ($erro == 'email_existe') $msg = "Este e-mail já está em uso.";
                    if ($erro == 'senhas_diferentes') $msg = "As senhas não coincidem.";
                    if ($erro == 'senha_curta') $msg = "A senha é muito fraca.";
                    echo '<div class="error-message">' . $msg . '</div>';
                }
            ?>
            
            <div class="form-group">
                <label for="nome">Nome Completo</label>
                <input type="text" id="nome" name="nome" placeholder="Seu nome completo" required autocomplete="off">
            </div>
            
            <div class="form-group">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" placeholder="seu@email.com" required autocomplete="off">
            </div>
            
            <div class="form-group">
                <label for="telefone">Telefone (com DDD)</label>
                <input type="text" id="telefone" name="telefone" placeholder="(11) 99999-9999" required autocomplete="off">
            </div>
            
            <div class="setup-actions">
                <button type="button" class="login-button setup-next" data-next-step="2">
                    Continuar <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </section>

        <section class="setup-panel" data-step="2" hidden>
            <span class="setup-panel-kicker">Etapa 2 de 2</span>
            <h2>Criar uma senha segura</h2>
            <p class="setup-panel-description">Escolha uma senha forte para proteger sua conta.</p>
            
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
            
            <div class="setup-actions">
                <button type="button" class="setup-back" data-previous-step="1">
                    <i class="fas fa-arrow-left"></i> Voltar
                </button>
                <button type="submit" class="login-button setup-submit">
                    <i class="fas fa-check"></i> Finalizar Cadastro
                </button>
            </div>
        </section>
        
    </form>
</div>

<!-- JavaScript existente para funcionalidades de senha/telefone + NEW: Lógica do Wizard -->
<script>
    // 1. Mostrar/Esconder Senha (existente)
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

    // 2. Validação de Senha em Tempo Real (existente)
    const inputSenha = document.getElementById('senha');
    const requisitosBox = document.getElementById('requisitos-senha');
    const reqComprimento = document.getElementById('req-comprimento');
    const reqMaiuscula = document.getElementById('req-maiuscula');
    const reqNumero = document.getElementById('req-numero');

    inputSenha.addEventListener('focus', () => {
        requisitosBox.style.display = 'block';
    });

    inputSenha.addEventListener('input', () => {
        const valor = inputSenha.value;

        if (valor.length >= 6) {
            reqComprimento.classList.add('valido');
        } else {
            reqComprimento.classList.remove('valido');
        }

        if (/[A-Z]/.test(valor)) {
            reqMaiuscula.classList.add('valido');
        } else {
            reqMaiuscula.classList.remove('valido');
        }

        if (/[0-9]/.test(valor)) {
            reqNumero.classList.add('valido');
        } else {
            reqNumero.classList.remove('valido');
        }
    });

    // 3. Máscara de Telefone (existente)
    const inputTel = document.getElementById('telefone');
    inputTel.addEventListener('input', (e) => {
        let x = e.target.value.replace(/\D/g, '').match(/(\d{0,2})(\d{0,5})(\d{0,4})/);
        e.target.value = !x[2] ? x[1] : '(' + x[1] + ') ' + x[2] + (x[3] ? '-' + x[3] : '');
    });

    // 4. NOVO: Lógica do Wizard de Cadastro (Baseada em criar_negocio.php)
    const setupPanels = [...document.querySelectorAll('.setup-panel')];
    const setupProgressSteps = [...document.querySelectorAll('.setup-progress-step')];
    let currentStep = 1;

    function showSetupStep(step) {
        currentStep = step;
        setupPanels.forEach((panel) => {
            const isActive = Number(panel.dataset.step) === step;
            panel.hidden = !isActive;
            panel.classList.toggle('active', isActive);
        });
        
        // Atualizar indicador de progresso
        setupProgressSteps.forEach((progressStep) => {
            const progress = Number(progressStep.dataset.progress);
            progressStep.classList.toggle('active', progress === step);
            progressStep.classList.toggle('completed', progress < step);
        });

        // Atualizar revisão no step 3
        if (step === 3) {
            document.getElementById('review-nome').textContent = document.getElementById('nome').value || '...';
            document.getElementById('review-email').textContent = document.getElementById('email').value || '...';
            document.getElementById('review-telefone').textContent = document.getElementById('telefone').value || '...';
        }
    }

    // Navegação entre etapas
    document.querySelectorAll('[data-next-step]').forEach((button) => {
        button.addEventListener('click', () => {
            const panel = button.closest('.setup-panel');
            const requiredFields = [...panel.querySelectorAll('input[required], select[required]')];
            const valid = requiredFields.every((field) => {
                if (!field.checkValidity()) {
                    field.reportValidity();
                    return false;
                }
                return true;
            });

            if (valid) {
                showSetupStep(Number(button.dataset.nextStep));
            }
        });
    });

    document.querySelectorAll('[data-previous-step]').forEach((button) => {
        button.addEventListener('click', () => showSetupStep(Number(button.dataset.previousStep)));
    });

    // Mostrar primeira etapa
    showSetupStep(currentStep);
</script>
</body>
</html>