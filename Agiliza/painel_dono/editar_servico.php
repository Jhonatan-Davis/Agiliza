<?php
session_start();
require '../conexao.php'; // Sobe um nível

// --- Porteiro ---
if (!isset($_SESSION['usuario_id']) || $_SESSION['funcao'] != 'dono') {
    header("Location: ../login/login.php");
    exit();
}

// 1. Pegar o ID do serviço da URL
if (!isset($_GET['id'])) {
    die("ID do serviço não fornecido.");
}

$id_servico = $_GET['id'];
$id_negocio = $_SESSION['id_negocio'];

// --- [CORREÇÃO] Verifica se o Dono também é Funcionário ---
$stmt_check_func = $pdo->prepare(
    "SELECT COUNT(*) FROM negocio_membros 
     WHERE id_usuario = :id_dono AND id_negocio = :id_negocio AND funcao = 'funcionario'"
);
$stmt_check_func->execute(['id_dono' => $id_dono, 'id_negocio' => $id_negocio]);
$dono_e_funcionario = $stmt_check_func->fetchColumn() > 0;

// 2. Buscar os dados deste serviço no banco
try {
    $stmt = $pdo->prepare(
        "SELECT * FROM servicos WHERE id = :id_servico AND id_negocio = :id_negocio"
    );
    $stmt->execute(['id_servico' => $id_servico, 'id_negocio' => $id_negocio]);
    $servico = $stmt->fetch(PDO::FETCH_ASSOC);

    // Se o serviço não for encontrado ou não pertencer a este dono
    if (!$servico) {
        die("Serviço não encontrado ou acesso negado.");
    }
} catch (PDOException $e) {
    die("Erro ao buscar serviço: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Serviço - Agiliza</title>
    <link rel="stylesheet" href="style_painel.css">
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
                <a href="index.php">Agenda Completa</a>
                <a href="meu_negocio.php">Meu Negócio</a>
                <a href="minha_equipe.php">Minha Equipe</a>
                <a href="meus_servicos.php" class="active">Meus Serviços</a>
            </div>
            <div class="sidebar-logout">
                <a href="../logout.php">Sair</a>
            </div>
        </nav>

        <main class="main-content">
            <h2>Editar Serviço</h2>
            
            <div class="form-card">
                <form action="processar_servico.php" method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id_servico" value="<?php echo $servico['id']; ?>">
                    
                    <div class="form-group">
                        <label for="nome_servico">Nome do Serviço</label>
                        <input type="text" id="nome_servico" name="nome_servico" 
                               value="<?php echo htmlspecialchars($servico['nome_servico']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="preco">Preço (R$)</label>
                        <input type="number" step="0.01" id="preco" name="preco" 
                               value="<?php echo htmlspecialchars($servico['preco']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="duracao_minutos">Duração (em minutos)</label>
                        <input type="number" id="duracao_minutos" name="duracao_minutos" 
                               value="<?php echo htmlspecialchars($servico['duracao_minutos']); ?>" required>
                    </div>
                    
                    <button type="submit" class="form-button">Salvar Alterações</button>
                    <a href="meus_servicos.php" style="margin-left: 10px; color: #b0b0b0;">Cancelar</a>
                </form>
            </div>
        </main>
    </div>
    <script src="script_painel.js"></script>
    <?php require '../footer.php'; ?>
</body>
</html>