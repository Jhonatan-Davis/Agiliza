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

// Verifica se o Dono está "emprestando" esta página
$e_dono = ($_SESSION['funcao'] == 'dono');

$id_funcionario = $_SESSION['usuario_id']; // Pega o ID (seja do dono ou do func)
$id_negocio = $_SESSION['id_negocio'];

// --- Busca as fotos JÁ ENVIADAS ---
try {
    $stmt = $pdo->prepare(
        "SELECT * FROM portfolio_fotos 
         WHERE id_funcionario = :id_funcionario 
         ORDER BY id DESC"
    );
    $stmt->execute(['id_funcionario' => $id_funcionario]);
    $fotos_portfolio = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Esta variável é necessária para o menu do Dono (se ele for funcionário)
    $dono_e_funcionario = $e_dono; 

} catch (PDOException $e) {
    die("Erro ao buscar portfólio: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Portfólio - Agiliza</title>
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
                    <a href="meu_perfil.php" class="link-extra">
                        <i class="fas fa-user-cog"></i> Meu Perfil (Pessoal)
                    </a>
                    <a href="meu_portfolio.php" class="link-extra active"> <i class="fas fa-camera"></i> Meu Portfólio
                    </a>
                <?php else: ?>
                    <a href="index.php">Dashboard</a> 
                    <a href="agenda_completa.php">Agenda Completa</a>
                    <a href="meu_perfil.php">Meu Perfil</a>
                    <a href="meu_portfolio.php" class="active">Meu Portfólio</a> <a href="meus_bloqueios.php">Meus Bloqueios</a>
                <?php endif; ?>
            </div>
            <div class="sidebar-logout">
                <a href="../logout.php">Sair</a>
            </div>
        </nav>

        <main class="main-content">
            <h2>Meu Portfólio</h2>
            <p>Envie fotos dos seus trabalhos. As 4 melhores marcadas como "Destaque" aparecerão para o cliente no agendamento.</p>
            
            <?php
                if (isset($_GET['sucesso'])) {
                    echo '<div class="error-message auto-dismiss-message" style="background-color: #4CAF50;">Foto enviada com sucesso!</div>';
                }
                if (isset($_GET['erro'])) {
                    echo '<div class="error-message auto-dismiss-message" style="background-color: #ff3333;">'.htmlspecialchars($_GET['erro']).'</div>';
                }
            ?>

            <div class="form-card" style="margin-bottom: 2rem;">
                <h3>Adicionar Nova Foto</h3>
                
                <form action="<?php echo $e_dono ? '../painel_dono/processar_portfolio_dono.php' : 'processar_portfolio.php'; ?>" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="form-group">
                        <label>Foto do Trabalho (JPG ou PNG)</label>
                        <div class="form-group-file">
                            <input type="file" id="foto_portfolio" name="foto_portfolio" class="file-input-hidden" accept="image/jpeg, image/png" required
                                   onchange="updateFilename(this.id, 'filename-foto')">
                            <label for="foto_portfolio" class="file-input-label">Escolher arquivo</label>
                            <span class="file-input-filename" id="filename-foto">Nenhum arquivo escolhido</span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="legenda">Legenda (Ex: "Corte Degradê")</label>
                        <input type="text" id="legenda" name="legenda" placeholder="Descreva o trabalho" required>
                    </div>
                    
                    <div class="aberto-toggle" style="justify-content: flex-start;">
                        <span>Mostrar no pop-up do cliente?</span>
                        <label class="switch">
                            <input type="checkbox" name="destaque_popup" value="1">
                            <span class="slider"></span>
                        </label>
                        <span>Sim</span>
                    </div>

                    <button type="submit" class="form-button" style="margin-top: 1.5rem;">Enviar Foto</button>
                </form>
            </div>

            <h3>Minhas Fotos</h3>
            <div class="portfolio-grid-func">
                
                <?php if (count($fotos_portfolio) > 0): ?>
                    <?php foreach ($fotos_portfolio as $foto): ?>
                        <div class="portfolio-card-func">
                            <img src="../<?php echo htmlspecialchars($foto['url_imagem']); ?>" alt="<?php echo htmlspecialchars($foto['legenda']); ?>">
                            <div class="portfolio-card-body">
                                <p><?php echo htmlspecialchars($foto['legenda']); ?></p>
                            </div>
                            <div class="portfolio-card-actions">
                                <div class="destaque-toggle">
                                    <label class="switch">
                                        <input type="checkbox" <?php if ($foto['destaque_popup']) echo 'checked'; ?> disabled>
                                        <span class="slider"></span>
                                    </label>
                                    <span>Destaque</span>
                                </div>
                                <a href="<?php echo $e_dono ? '../painel_dono/processar_portfolio_dono.php' : 'processar_portfolio.php'; ?>?action=delete&id=<?php echo $foto['id']; ?>" 
                                   class="icon-delete" title="Excluir Foto"
                                   onclick="return confirm('Tem certeza que deseja excluir esta foto?');">
                                   <i class="fas fa-trash-alt"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #777; font-style: italic;">Você ainda não enviou nenhuma foto para o seu portfólio.</p>
                <?php endif; ?>
                
            </div>
        </main>
    </div>

<script>
    function updateFilename(inputId, spanId) {
        const input = document.getElementById(inputId);
        const span = document.getElementById(spanId);
        if (input.files && input.files.length > 0) {
            span.textContent = input.files[0].name;
        } else {
            span.textContent = 'Nenhum arquivo escolhido';
        }
    }
    document.addEventListener('DOMContentLoaded', (event) => {
        const alertMessage = document.querySelector('.auto-dismiss-message');
        if (alertMessage) {
            setTimeout(() => {
                alertMessage.classList.add('fade-out');
            }, 3000);
        }
    });
</script>
</body>
</html>