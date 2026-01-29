<?php
// Se a sessão não foi iniciada, inicia
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require '../conexao.php';

// --- Porteiro (Flexível) ---
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../login/login.php");
    exit();
}

// [NOVO] Verifica se o Dono está "emprestando" esta página
$e_dono = ($_SESSION['funcao'] == 'dono');

$id_funcionario = $_SESSION['usuario_id']; // Pega o ID de quem está logado

// --- Busca os dados ATUAIS ---
try {
    $stmt = $pdo->prepare("SELECT nome, email, foto_perfil_url FROM usuarios WHERE id = :id");
    $stmt->execute(['id' => $id_funcionario]);
    $funcionario = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao buscar dados: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - Agiliza</title>
    <link rel="stylesheet" href="../painel_dono/style_painel.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="painel-container">
        <header class="mobile-header">
            <button class="menu-toggle" id="menu-toggle">
                <i class="fas fa-bars"></i>
            </button>
            <h1>Agili<span>za</span></h1>
        </header>
        <nav class="sidebar">
            <div class="sidebar-nav">
                <?php if ($e_dono): ?>
                    <a href="index.php">Dashboard</a> 
                    <a href="agenda_completa.php">Agenda Completa</a>
                    <a href="meu_negocio.php">Meu Negócio</a>
                    <a href="minha_equipe.php">Minha Equipe</a>
                    <a href="meus_servicos.php">Meus Serviços</a>
                    <a href="meus_horarios.php">Meus Horários</a>
                    <a href="historico.php">Histórico e Vendas</a>
                    <a href="financeiro.php">Financeiro</a>
                    <a href="meus_clientes.php">Meus Clientes</a>
                    <a href="meu_perfil.php" class="link-extra active"> <i class="fas fa-user-cog"></i> Meu Perfil (Pessoal)
                    </a>
                    <a href="meu_portfolio.php" class="link-extra">
                        <i class="fas fa-camera"></i> Meu Portfólio
                    </a>
                <?php else: ?>
                    <a href="index.php">Dashboard</a> 
                    <a href="agenda_completa.php">Agenda Completa</a>
                    <a href="meu_perfil.php" class="active">Meu Perfil</a> <a href="meu_portfolio.php">Meu Portfólio</a>
                    <a href="meus_bloqueios.php">Meus Bloqueios</a>
                <?php endif; ?>
            </div>
            <div class="sidebar-logout">
                <a href="../logout.php">Sair</a>
            </div>
        </nav>

        <main class="main-content">
            <h2>Meu Perfil Pessoal</h2>
            <p>Atualize suas informações pessoais. Estes dados são usados para seu login e para o cliente identificar você.</p>

            <div class="form-card" style="max-width: 700px;">
                <h3>Editar Informações</h3>
                
                <?php
                    if (isset($_SESSION['erro_perfil'])) {
                        echo '<div class="error-message auto-dismiss-message" style="background-color: #ff3333;">'.$_SESSION['erro_perfil'].'</div>';
                        unset($_SESSION['erro_perfil']);
                    }
                    if (isset($_GET['sucesso']) && !isset($_SESSION['sucesso_senha'])) {
                        echo '<div class="error-message auto-dismiss-message" style="background-color: #4CAF50;">Perfil atualizado com sucesso!</div>';
                    }
                ?>
                
                <form action="<?php echo $e_dono ? 'processar_perfil_funcionario.php' : 'processar_perfil_funcionario.php'; ?>" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="salvar_perfil">
                    
                    <div class="form-group">
                        <label>Sua Foto de Perfil (Opcional)</label>
                        <div class="form-group-file">
                            <input type="file" id="foto_perfil" name="foto_perfil" class="file-input-hidden" accept="image/*" onchange="updateFilename(this.id, 'filename-perfil')">
                            <label for="foto_perfil" class="file-input-label">Escolher arquivo</label>
                            <span class="file-input-filename" id="filename-perfil">Nenhum arquivo escolhido</span>
                        </div>
                        <?php if (!empty($funcionario['foto_perfil_url'])): ?>
                            <div class="preview-container">
                                <img src="../<?php echo htmlspecialchars($funcionario['foto_perfil_url']); ?>?v=<?php echo time(); ?>" alt="Foto Atual" class="preview-perfil">
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="nome">Nome Completo</label>
                        <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($funcionario['nome']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">E-mail (para login)</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($funcionario['email']); ?>" required>
                    </div>
                    
                    <button type="submit" class="form-button">Salvar Alterações</button>
                </form>
            </div>

            <div class="form-card" style="max-width: 700px; margin-top: 2rem;">
                <h3>Alterar Senha</h3>
                
                <?php
                    if (isset($_SESSION['erro_senha'])) {
                        echo '<div class="error-message auto-dismiss-message" style="background-color: #ff3333;">'.$_SESSION['erro_senha'].'</div>';
                        unset($_SESSION['erro_senha']);
                    }
                    if (isset($_SESSION['sucesso_senha'])) {
                        echo '<div class="error-message auto-dismiss-message" style="background-color: #4CAF50;">'.$_SESSION['sucesso_senha'].'</div>';
                    }
                ?>
                
                <form action="<?php echo $e_dono ? 'processar_perfil_funcionario.php' : 'processar_perfil_funcionario.php'; ?>" method="POST">
                    <input type="hidden" name="action" value="mudar_senha">
                
                    <div class="form-group">
                        <label for="senha_atual">Sua Senha Atual</label>
                        <input type="password" id="senha_atual" name="senha_atual" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="nova_senha">Nova Senha</label>
                        <input type="password" id="nova_senha" name="nova_senha" required>
                        
                        <div id="requisitos-senha">
                            <h4>A nova senha deve conter:</h4>
                            <ul>
                                <li id="req-comprimento">Pelo menos 6 caracteres</li>
                                <li id="req-maiuscula">Pelo menos 1 letra maiúscula (A-Z)</li>
                                <li id="req-numero">Pelo menos 1 número (0-9)</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirmar_nova_senha">Confirmar Nova Senha</label>
                        <input type="password" id="confirmar_nova_senha" name="confirmar_nova_senha" required>
                    </div>
                    
                    <button type="submit" class="form-button">Alterar Senha</button>
                </form>
            </div>
        </main>
    </div>

<script>
    // 1. Script para mostrar o nome do arquivo
    function updateFilename(inputId, spanId) {
        const input = document.getElementById(inputId);
        const span = document.getElementById(spanId);
        if (input.files && input.files.length > 0) {
            span.textContent = input.files[0].name;
        } else {
            span.textContent = 'Nenhum arquivo escolhido';
        }
    }

    // 2. Script da Mensagem que desaparece
    document.addEventListener('DOMContentLoaded', (event) => {
        document.querySelectorAll('.auto-dismiss-message').forEach(function(alertMessage) {
            if (alertMessage) {
                setTimeout(() => {
                    alertMessage.classList.add('fade-out');
                }, 3000);
            }
        });
    });

    // 3. Script dos Requisitos de Senha
    const inputNovaSenha = document.getElementById('nova_senha');
    const requisitosBox = document.getElementById('requisitos-senha');
    if (inputNovaSenha) {
        const reqComprimento = document.getElementById('req-comprimento');
        const reqMaiuscula = document.getElementById('req-maiuscula');
        const reqNumero = document.getElementById('req-numero');

        inputNovaSenha.addEventListener('focus', () => { requisitosBox.style.display = 'block'; });
        inputNovaSenha.addEventListener('blur', () => { requisitosBox.style.display = 'none'; });
        inputNovaSenha.addEventListener('input', () => {
            const senha = inputNovaSenha.value;
            if (senha.length >= 6) { reqComprimento.classList.add('valido'); } else { reqComprimento.classList.remove('valido'); }
            if (/[A-Z]/.test(senha)) { reqMaiuscula.classList.add('valido'); } else { reqMaiuscula.classList.remove('valido'); }
            if (/[0-9]/.test(senha)) { reqNumero.classList.add('valido'); } else { reqNumero.classList.remove('valido'); }
        });
    }
</script>
</body>
</html>