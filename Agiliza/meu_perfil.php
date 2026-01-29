<?php
session_start();
require 'conexao.php'; // Inclui a conexão

// --- Porteiro ---
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login/login.php");
    exit();
}

$id_usuario = $_SESSION['usuario_id'];

// --- Busca os dados ATUAIS do usuário ---
try {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = :id");
    $stmt->execute(['id' => $id_usuario]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Atualiza a sessão para garantir
    $_SESSION['usuario_nome'] = $usuario['nome'];
    $_SESSION['usuario_foto'] = $usuario['foto_perfil_url'];

} catch (PDOException $e) {
    die("Erro ao buscar dados do usuário: " . $e->getMessage());
}

// Pega informações do "Dispositivo Conectado"
$ip_address = $_SERVER['REMOTE_ADDR'];
$user_agent = $_SERVER['HTTP_USER_AGENT'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - Agiliza</title>
    <link rel="stylesheet" href="style_site.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="shortcut icon" href="uploads/logo_agiliza.png" type="image/png">
</head>
<body>

    <header class="site-header">
        <a href="index.php" class="logo">Agili<span>za</span></a>
        
        <nav>
            <div class="user-dropdown">
                <button class="dropdown-btn">
                    <?php if (!empty($usuario['foto_perfil_url'])): ?>
                        <img src="<?php echo htmlspecialchars($usuario['foto_perfil_url']); ?>?v=<?php echo time(); ?>" 
                             alt="Perfil" class="header-perfil-foto">
                    <?php endif; ?>
                    Olá, <?php echo htmlspecialchars($usuario['nome']); ?>!
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="dropdown-content">
                    <a href="meu_perfil.php">Meu Perfil</a>
                    <a href="meus_agendamentos.php">Meus Agendamentos</a>
                    <div class="dropdown-divider"></div>
                    <a href="logout.php" class="logout-link">Sair</a>
                </div>
            </div>
        </nav>
    </header>

    <main class="site-container">
        <h1>Meu Perfil</h1>
        
        <div class="profile-layout">
        
            <div class="profile-card">
                <h3>Editar Informações</h3>
                
                <?php
                    // Mostra mensagens de SUCESSO ou ERRO do perfil
                    if (isset($_SESSION['erro_perfil'])) {
                        echo '<div class="error-message auto-dismiss-message" style="background-color: #ff3333; color: white; padding: 0.75rem; border-radius: 6px; margin-bottom: 1rem;">'.$_SESSION['erro_perfil'].'</div>';
                        unset($_SESSION['erro_perfil']);
                    }
                    if (isset($_GET['sucesso']) && !isset($_SESSION['sucesso_senha'])) {
                        echo '<div class="error-message auto-dismiss-message" style="background-color: #4CAF50; color: white; padding: 0.75rem; border-radius: 6px; margin-bottom: 1rem;">Perfil atualizado com sucesso!</div>';
                    }
                ?>
                
                <form action="processar_perfil.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="salvar_perfil">
                    
                    <div class="form-group">
                        <label>Foto de Perfil (Opcional)</label>
                        <div class="form-group-file">
                            <input type="file" id="foto_perfil" name="foto_perfil" class="file-input-hidden" accept="image/*" 
                                   onchange="updateFilename(this.id, 'filename-perfil')">
                            <label for="foto_perfil" class="file-input-label">Escolher arquivo</label>
                            <span class="file-input-filename" id="filename-perfil">Nenhum arquivo escolhido</span>
                        </div>
                        
                        <?php if (!empty($usuario['foto_perfil_url'])): ?>
                            <div class="preview-container">
                                <img src="<?php echo htmlspecialchars($usuario['foto_perfil_url']); ?>?v=<?php echo time(); ?>" 
                                     alt="Foto Atual" class="preview-perfil">
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="nome">Nome</label>
                        <input type="text" id="nome" name="nome" class="search-input" 
                               value="<?php echo htmlspecialchars($usuario['nome']); ?>" required>
                    </div>
                    
                    <button type="submit" class="form-button">Salvar Alterações</button>
                </form>
            </div>
            
            <div class="profile-card">
                <h3>Dispositivos Conectados</h3>
                <p>Esta é a sessão que você está usando agora.</p>
                <div class="device-info">
                    <i class="fas fa-desktop"></i>
                    <div class="device-details">
                        <strong>Seu navegador atual</strong>
                        <span><?php echo htmlspecialchars($user_agent); ?></span>
                        <span class="ip-address">IP: <?php echo htmlspecialchars($ip_address); ?></span>
                    </div>
                </div>
            </div>
            
        </div> <div class="profile-card" style="margin-top: 2rem;">
            <h3>Alterar Senha</h3>
            <?php
                // Mostra mensagens de SUCESSO ou ERRO da troca de senha
                if (isset($_SESSION['erro_senha'])) {
                    echo '<div class="error-message auto-dismiss-message" style="background-color: #ff3333;">'.$_SESSION['erro_senha'].'</div>';
                    unset($_SESSION['erro_senha']);
                }
                if (isset($_SESSION['sucesso_senha'])) {
                    echo '<div class="error-message auto-dismiss-message" style="background-color: #4CAF50;">'.$_SESSION['sucesso_senha'].'</div>';
                    unset($_SESSION['sucesso_senha']);
                }
            ?>
            <form action="processar_perfil.php" method="POST">
                <input type="hidden" name="action" value="mudar_senha">
                <div class="form-group">
                    <label for="senha_atual">Senha Atual</label>
                    <input type="password" id="senha_atual" name="senha_atual" class="search-input" required>
                </div>
                <div class="form-group">
                    <label for="nova_senha">Nova Senha</label>
                    <input type="password" id="nova_senha" name="nova_senha" class="search-input" required>
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
                    <input type="password" id="confirmar_nova_senha" name="confirmar_nova_senha" class="search-input" required>
                </div>
                <button type="submit" class="form-button">Alterar Senha</button>
            </form>
        </div>
    </main>

    <?php require 'footer.php'; ?>

<script>
    // 1. Script do Dropdown do Header
    const dropdownBtn = document.querySelector('.dropdown-btn');
    const dropdownContent = document.querySelector('.dropdown-content');
    if (dropdownBtn) {
        dropdownBtn.addEventListener('click', (e) => {
            e.stopPropagation(); 
            dropdownContent.classList.toggle('show');
        });
        window.addEventListener('click', (e) => {
            if (dropdownContent.classList.contains('show') && !e.target.closest('.user-dropdown')) {
                dropdownContent.classList.remove('show');
            }
        });
    }

    // 2. Script para mostrar o nome do arquivo no upload
    function updateFilename(inputId, spanId) {
        const input = document.getElementById(inputId);
        const span = document.getElementById(spanId);
        if (input.files && input.files.length > 0) {
            span.textContent = input.files[0].name;
        } else {
            span.textContent = 'Nenhum arquivo escolhido';
        }
    }

    // 3. Script da Mensagem que desaparece
    document.addEventListener('DOMContentLoaded', (event) => {
        document.querySelectorAll('.auto-dismiss-message').forEach(function(alertMessage) {
            if (alertMessage) {
                setTimeout(() => {
                    alertMessage.style.transition = 'opacity 0.5s ease';
                    alertMessage.style.opacity = '0';
                    setTimeout(() => alertMessage.style.display = 'none', 500);
                }, 3000);
            }
        });
    });

    // 4. Script dos Requisitos de Senha
    const inputNovaSenha = document.getElementById('nova_senha');
    const requisitosBox = document.getElementById('requisitos-senha');
    if (inputNovaSenha) {
        const reqComprimento = document.getElementById('req-comprimento');
        const reqMaiuscula = document.getElementById('req-maiuscula');
        const reqNumero = document.getElementById('req-numero');

        inputNovaSenha.addEventListener('focus', () => {
            requisitosBox.style.display = 'block';
        });
        inputNovaSenha.addEventListener('blur', () => {
            requisitosBox.style.display = 'none';
        });
        inputNovaSenha.addEventListener('input', () => {
            const senha = inputNovaSenha.value;
            if (senha.length >= 6) { reqComprimento.classList.add('valido'); } 
            else { reqComprimento.classList.remove('valido'); }
            if (/[A-Z]/.test(senha)) { reqMaiuscula.classList.add('valido'); } 
            else { reqMaiuscula.classList.remove('valido'); }
            if (/[0-9]/.test(senha)) { reqNumero.classList.add('valido'); } 
            else { reqNumero.classList.remove('valido'); }
        });
    }
</script>
</body>
</html>