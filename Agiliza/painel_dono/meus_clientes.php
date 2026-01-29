<?php
session_start();
require '../conexao.php';

// --- Porteiro ---
if (!isset($_SESSION['usuario_id']) || $_SESSION['funcao'] != 'dono') {
    header("Location: ../login/login.php");
    exit();
}

$id_dono = $_SESSION['usuario_id'];
$id_negocio = $_SESSION['id_negocio'];

// --- Lógica da Sidebar ---
$stmt_check_func = $pdo->prepare("SELECT COUNT(*) FROM negocio_membros WHERE id_usuario = :id_dono AND id_negocio = :id_negocio AND funcao = 'funcionario'");
$stmt_check_func->execute(['id_dono' => $id_dono, 'id_negocio' => $id_negocio]);
$dono_e_funcionario = $stmt_check_func->fetchColumn() > 0;

try {
    // --- A CONSULTA INTELIGENTE ---
    // 1. Busca todo mundo que já teve um agendamento neste negócio
    // 2. Agrupa por cliente para não repetir
    // 3. Calcula a data da última visita (MAX)
    // 4. Conta quantas vezes ele veio (COUNT)
    
    $sql = "
        SELECT 
            u.id, u.nome, u.email, u.telefone, u.foto_perfil_url,
            MAX(a.data_hora_inicio) as ultima_visita,
            COUNT(a.id) as total_visitas
        FROM agendamentos a
        JOIN usuarios u ON a.id_cliente = u.id
        WHERE a.id_negocio = :id_negocio
        GROUP BY u.id
        ORDER BY ultima_visita DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id_negocio' => $id_negocio]);
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erro: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Clientes - Agiliza</title>
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
                <a href="financeiro.php">Financeiro</a>
                <a href="meus_clientes.php" class="active">Meus Clientes</a> 
                
                <?php if ($dono_e_funcionario): ?>
                    <a href="meu_perfil.php" class="link-extra"><i class="fas fa-user-cog"></i> Meu Perfil</a>
                    <a href="meu_portfolio.php" class="link-extra"><i class="fas fa-camera"></i> Meu Portfólio</a>
                <?php endif; ?>
            </div>
            <div class="sidebar-logout"><a href="../logout.php">Sair</a></div>
        </nav>

        <main class="main-content">
            <h2>Carteira de Clientes</h2>
            <p>Gerencie o relacionamento com seus clientes.</p>

            <div class="dashboard-card" style="padding: 0; overflow: hidden;">
                <table class="tabela-agenda" style="margin-top: 0; border: none; box-shadow: none;">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Contato</th>
                            <th>Última Visita</th>
                            <th>Frequência</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($clientes) > 0): ?>
                            <?php foreach ($clientes as $cli): ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <img src="../<?php echo !empty($cli['foto_perfil_url']) ? htmlspecialchars($cli['foto_perfil_url']) : 'uploads/default_perfil.png'; ?>" 
                                                 style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                            <strong><?php echo htmlspecialchars($cli['nome']); ?></strong>
                                        </div>
                                    </td>
                                    <td>
                                        <small style="display: block; color: #666;"><?php echo htmlspecialchars($cli['email']); ?></small>
                                        <small style="display: block; color: #666;"><?php echo htmlspecialchars($cli['telefone']); ?></small>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($cli['ultima_visita'])); ?></td>
                                    <td>
                                        <span class="status-badge" style="background-color: #e6e6fa; color: #7E57C2;">
                                            <?php echo $cli['total_visitas']; ?> visitas
                                        </span>
                                    </td>
                                    <td class="actions-cell">
                                        <a href="detalhe_cliente.php?id=<?php echo $cli['id']; ?>" class="icon-view" title="Ver Detalhes e Anotações">
                                            <i class="fas fa-address-card"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align: center; padding: 2rem;">Nenhum cliente encontrado.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    <script src="script_painel.js"></script>
</body>
</html> 