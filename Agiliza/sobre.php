<?php
session_start();
require 'conexao.php'; // (Precisamos para o header)
date_default_timezone_set('America/Sao_Paulo');

// Tenta buscar os dados do usuário se ele estiver logado
$usuario = null;
if (isset($_SESSION['usuario_id'])) {
    try {
        $stmt_user = $pdo->prepare("SELECT nome, foto_perfil_url FROM usuarios WHERE id = :id");
        $stmt_user->execute(['id' => $_SESSION['usuario_id']]);
        $usuario = $stmt_user->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Erro silencioso, apenas não mostra o menu do usuário
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sobre o Agiliza - A sua plataforma de agendamentos</title>
    <link rel="stylesheet" href="style_site.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>

    <header class="site-header">
        <a href="index.php" class="logo">Agili<span>za</span></a>
        <nav>
            <a href="sobre.php">Sobre</a>
            
            <?php if ($usuario): ?>
                <div class="user-dropdown">
                    <button class="dropdown-btn">
                        <?php if (!empty($usuario['foto_perfil_url'])): ?>
                            <img src="<?php echo htmlspecialchars($usuario['foto_perfil_url']); ?>?v=<?php echo time(); ?>" alt="Perfil" class="header-perfil-foto">
                        <?php endif; ?>
                        Olá, <?php echo htmlspecialchars($usuario['nome']); ?>!
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="dropdown-content">
                        </div>
                </div>
            <?php else: ?>
                <a href="login/login.php">Fazer Login</a>
                <a href="cadastro/selecao.php">Cadastre-se</a>
            <?php endif; ?>
        </nav>
    </header>

    <div class="hero-section">
        <h1>Encontre o profissional perfeito, na hora que você precisa.</h1>
        <p>Chega de telefonemas e espera. No Agiliza, você encontra barbearias, clínicas, estúdios e muito mais, vê a agenda em tempo real e marca seu horário com 3 cliques.</p>
        <a href="index.php" class="form-button">Encontrar um profissional</a>
    </div>
    
    <main class="site-container">
        <div class="how-it-works-section">
            <h2>Como Funciona?</h2>
            <div class="how-it-works-grid">
                <div class="how-it-works-step">
                    <i class="fas fa-search"></i>
                    <h3>1. Busque e Filtre</h3>
                    <p>Filtre por nome, nicho (Barbearia, Dentista) ou tipo de serviço na sua cidade.</p>
                </div>
                <div class="how-it-works-step">
                    <i class="fas fa-calendar-check"></i>
                    <h3>2. Agende</h3>
                    <p>Veja o portfólio do profissional e escolha o horário que realmente está vago na agenda dele.</p>
                </div>
                <div class="how-it-works-step">
                    <i class="fas fa-store"></i>
                    <h3>3. Compareça</h3>
                    <p>Vá até o local e receba seu atendimento sem filas. Simples, rápido e grátis.</p>
                </div>
            </div>
        </div>
    </main>
    

    <?php require 'footer.php'; ?>

    <script>
        const dropdownBtn = document.querySelector('.dropdown-btn');
        const dropdownContent = document.querySelector('.dropdown-content');
        if (dropdownBtn) {
            dropdownBtn.addEventListener('click', (e) => {
                e.stopPropagation(); 
                dropdownContent.classList.toggle('show');
            });
            window.addEventListener('click', (e) => {
                if (dropdownContent && dropdownContent.classList.contains('show') && !e.target.closest('.user-dropdown')) {
                    dropdownContent.classList.remove('show');
                }
            });
        }
    </script>
</body>
</html>