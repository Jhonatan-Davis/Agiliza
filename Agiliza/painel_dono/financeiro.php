<?php
session_start();
require '../conexao.php';

// --- Porteiro ---
if (!isset($_SESSION['usuario_id']) || $_SESSION['funcao'] != 'dono') {
    header("Location: ../login/login.php");
    exit();
}

$id_negocio = $_SESSION['id_negocio'];
$id_dono = $_SESSION['usuario_id'];

// Verifica se o Dono é funcionário (para a Sidebar)
$stmt_check = $pdo->prepare("SELECT COUNT(*) FROM negocio_membros WHERE id_usuario = :id AND funcao = 'funcionario'");
$stmt_check->execute(['id' => $id_dono]);
$dono_e_funcionario = $stmt_check->fetchColumn() > 0;

// --- LISTAR DESPESAS (do mês atual) ---
try {
    $inicio_mes = date('Y-m-01');
    $fim_mes = date('Y-m-t');
    
    $stmt = $pdo->prepare(
        "SELECT * FROM despesas 
         WHERE id_negocio = :id 
         AND data_despesa BETWEEN :inicio AND :fim 
         ORDER BY data_despesa DESC"
    );
    $stmt->execute(['id' => $id_negocio, 'inicio' => $inicio_mes, 'fim' => $fim_mes]);
    $despesas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcula o total
    $total_despesas = 0;
    foreach($despesas as $d) { $total_despesas += $d['valor']; }

} catch (PDOException $e) {
    die("Erro: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financeiro - Agiliza</title>
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
            <div class="sidebar-nav">
                <a href="index.php">Dashboard</a> 
                <a href="agenda_completa.php">Agenda Completa</a>
                <a href="meu_negocio.php">Meu Negócio</a>
                <a href="minha_equipe.php">Minha Equipe</a>
                <a href="meus_servicos.php">Meus Serviços</a>
                <a href="meus_horarios.php">Meus Horários</a>
                <a href="historico.php">Histórico e Vendas</a>
                <a href="financeiro.php" class="active">Financeiro</a> <?php if ($dono_e_funcionario): ?>
                    <a href="meus_clientes.php">Meus Clientes</a>
                    <a href="meu_perfil.php" class="link-extra"><i class="fas fa-user-cog"></i> Meu Perfil (Pessoal)</a>
                    <a href="meu_portfolio.php" class="link-extra"><i class="fas fa-camera"></i> Meu Portfólio</a>
                <?php endif; ?>
            </div>
            <div class="sidebar-logout"><a href="../logout.php">Sair</a></div>
        </nav>

        <main class="main-content">
            <h2>Controle Financeiro (Mês Atual)</h2>
            
            <div class="dashboard-grid" style="grid-template-columns: 1fr; margin-bottom: 2rem;">
                <div class="dashboard-card" style="background-color: #ffebee; border-left: 5px solid #ff5555;">
                    <h3 style="color: #c62828; margin-bottom: 0.5rem;">Total de Despesas</h3>
                    <span style="font-size: 2rem; font-weight: bold; color: #c62828;">
                        R$ -<?php echo number_format($total_despesas, 2, ',', '.'); ?>
                    </span>
                </div>
            </div>

            <div class="form-card" style="margin-bottom: 2rem;">
                <h3>Lançar Nova Despesa</h3>
                <form action="processar_financeiro.php" method="POST" class="horario-inputs" style="grid-template-columns: 2fr 1fr 1fr auto; align-items: end;">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="form-group" style="margin-bottom:0;">
                        <label>Descrição</label>
                        <input type="text" name="descricao" placeholder="Ex: Conta de Luz" required>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label>Valor (R$)</label>
                        <input type="number" step="0.01" name="valor" placeholder="0,00" required>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label>Data</label>
                        <input type="date" name="data_despesa" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <button type="submit" class="form-button" style="background-color: #ff5555; border-color: #ff5555;">Lançar Saída</button>
                </form>
            </div>

            <h3>Histórico de Despesas</h3>
            <table class="tabela-agenda">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Descrição</th>
                        <th>Valor</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($despesas) > 0): ?>
                        <?php foreach ($despesas as $d): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($d['data_despesa'])); ?></td>
                                <td><?php echo htmlspecialchars($d['descricao']); ?></td>
                                <td style="color: #dc3545; font-weight: bold;">- R$ <?php echo number_format($d['valor'], 2, ',', '.'); ?></td>
                                <td class="actions-cell">
                                    <a href="processar_financeiro.php?action=delete&id=<?php echo $d['id']; ?>" class="icon-delete" onclick="return confirm('Apagar esta despesa?');">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align:center;">Nenhuma despesa lançada neste mês.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </main>
    </div>
    <script src="script_painel.js"></script>
</body>
</html>