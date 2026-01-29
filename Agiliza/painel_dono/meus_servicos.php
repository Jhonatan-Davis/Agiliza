<?php
session_start();
require '../conexao.php';

// --- O "Porteiro" do Dono ---
if (!isset($_SESSION['usuario_id']) || $_SESSION['funcao'] != 'dono') {
    header("Location: ../login/login.php");
    exit();
}

$id_negocio = $_SESSION['id_negocio'];
$id_dono = $_SESSION['usuario_id'];

// --- 1. Verifica se o Dono também é Funcionário (Para a Sidebar) ---
$stmt_check_func = $pdo->prepare(
    "SELECT COUNT(*) FROM negocio_membros 
     WHERE id_usuario = :id_dono AND id_negocio = :id_negocio AND funcao = 'funcionario'"
);
$stmt_check_func->execute(['id_dono' => $id_dono, 'id_negocio' => $id_negocio]);
$dono_e_funcionario = $stmt_check_func->fetchColumn() > 0;

// --- 2. Busca os serviços ATUAIS ---
try {
    $stmt = $pdo->prepare(
        "SELECT * FROM servicos WHERE id_negocio = :id_negocio ORDER BY nome_servico ASC"
    );
    $stmt->execute(['id_negocio' => $id_negocio]);
    $servicos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erro ao buscar serviços: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Serviços - Agiliza</title>
    <link rel="stylesheet" href="style_painel.css">
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
            <h1>Agili<span>za</span></h1>
            
            <div class="sidebar-nav">
                <a href="index.php">Dashboard</a> 
                <a href="agenda_completa.php">Agenda Completa</a>
                <a href="meu_negocio.php">Meu Negócio</a>
                <a href="minha_equipe.php">Minha Equipe</a>
                <a href="meus_servicos.php" class="active">Meus Serviços</a> <a href="meus_horarios.php">Meus Horários</a>
                <a href="historico.php">Histórico e Vendas</a>
                <a href="financeiro.php">Financeiro</a>
                <a href="meus_clientes.php">Meus Clientes</a>
                
                <?php if ($dono_e_funcionario): ?>
                    <a href="meu_perfil.php" class="link-extra">
                        <i class="fas fa-user-cog"></i> Meu Perfil (Pessoal)
                    </a>
                    <a href="meu_portfolio.php" class="link-extra">
                        <i class="fas fa-camera"></i> Meu Portfólio
                    </a>
                <?php endif; ?>
            </div>
            
            <div class="sidebar-logout">
                <a href="../logout.php">Sair</a>
            </div>
        </nav>

        <main class="main-content">
            <h2>Gerenciar Serviços</h2>
            
            <?php
                if (isset($_GET['sucesso'])) {
                    echo '<div class="error-message auto-dismiss-message" style="background-color: #4CAF50;">Ação realizada com sucesso!</div>';
                }
                if (isset($_GET['erro'])) {
                    echo '<div class="error-message auto-dismiss-message" style="background-color: #ff3333;">'.htmlspecialchars($_GET['erro']).'</div>';
                }
            ?>

            <div class="form-card" style="margin-bottom: 2rem;">
                <h3>Cadastrar Novo Serviço</h3>
                <form action="processar_servico.php" method="POST">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="form-group">
                        <label for="nome_servico">Nome do Serviço (Ex: Corte Masculino)</label>
                        <input type="text" id="nome_servico" name="nome_servico" required>
                    </div>

                    <div class="form-group">
                        <label for="preco">Preço (R$)</label>
                        <input type="number" step="0.01" id="preco" name="preco" placeholder="Ex: 35.50" required>
                    </div>

                    <div class="form-group">
                        <label for="duracao_minutos">Duração (em minutos)</label>
                        <input type="number" id="duracao_minutos" name="duracao_minutos" placeholder="Ex: 30" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="frequencia_retorno">
                            <i class="fas fa-sync-alt" style="color: #7E57C2;"></i> Lembrete de Retorno (dias)
                        </label>
                        <input type="number" id="frequencia_retorno" name="frequencia_retorno" 
                               placeholder="Ex: 30 (Deixe vazio para não lembrar)" min="1">
                    </div>
                    
                    <button type="submit" class="form-button">Cadastrar Serviço</button>
                </form>
            </div>

            <h3>Serviços Cadastrados</h3>
            <table class="tabela-agenda">
                <thead>
                    <tr>
                        <th>Nome do Serviço</th>
                        <th>Preço</th>
                        <th>Duração</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($servicos) > 0): ?>
                        <?php foreach ($servicos as $servico): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($servico['nome_servico']); ?></td>
                                <td>R$ <?php echo number_format($servico['preco'], 2, ',', '.'); ?></td>
                                <td><?php echo $servico['duracao_minutos']; ?> min</td>
                                <td class="actions-cell">
                                    <a href="editar_servico.php?id=<?php echo $servico['id']; ?>" class="icon-edit" title="Editar Serviço">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="processar_servico.php?action=delete&id=<?php echo $servico['id']; ?>" 
                                       class="icon-delete" title="Excluir Serviço"
                                       onclick="return confirm('Tem certeza que deseja excluir este serviço?');">
                                       <i class="fas fa-trash-alt"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center;">Nenhum serviço cadastrado.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </main>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', (event) => {
            const alertMessage = document.querySelector('.auto-dismiss-message');
            if (alertMessage) {
                setTimeout(() => {
                    alertMessage.classList.add('fade-out');
                }, 3000); 
            }
        });
    </script>
    <script src="script_painel.js"></script>
</body>
</html>