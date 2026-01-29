<?php
session_start();
require '../conexao.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['funcao'] != 'dono') { header("Location: ../login/login.php"); exit(); }

$id_negocio = $_SESSION['id_negocio'];
$id_cliente = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_cliente == 0) { die("Cliente não encontrado."); }

// Verifica sidebar
$id_dono = $_SESSION['usuario_id'];
$stmt_check = $pdo->prepare("SELECT COUNT(*) FROM negocio_membros WHERE id_usuario = :id AND funcao = 'funcionario'");
$stmt_check->execute(['id' => $id_dono]);
$dono_e_funcionario = $stmt_check->fetchColumn() > 0;

try {
    // 1. Dados do Cliente
    $stmt_cli = $pdo->prepare("SELECT * FROM usuarios WHERE id = :id");
    $stmt_cli->execute(['id' => $id_cliente]);
    $cliente = $stmt_cli->fetch(PDO::FETCH_ASSOC);

    // 2. Histórico de Agendamentos
    $stmt_hist = $pdo->prepare(
        "SELECT a.*, s.nome_servico, f.nome as nome_profissional 
         FROM agendamentos a
         JOIN servicos s ON a.id_servico = s.id
         JOIN usuarios f ON a.id_funcionario = f.id
         WHERE a.id_cliente = :cli AND a.id_negocio = :neg
         ORDER BY a.data_hora_inicio DESC"
    );
    $stmt_hist->execute(['cli' => $id_cliente, 'neg' => $id_negocio]);
    $historico = $stmt_hist->fetchAll(PDO::FETCH_ASSOC);

    // 3. Anotação Existente
    $stmt_nota = $pdo->prepare("SELECT anotacao FROM anotacoes_clientes WHERE id_cliente = :cli AND id_negocio = :neg");
    $stmt_nota->execute(['cli' => $id_cliente, 'neg' => $id_negocio]);
    $nota_atual = $stmt_nota->fetchColumn();

} catch (PDOException $e) { die("Erro: " . $e->getMessage()); }
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes do Cliente - Agiliza</title>
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
                <a href="meus_clientes.php" class="active">Meus Clientes</a>
                <a href="meus_clientes.php" style="margin-top: 2rem; border: 1px solid #ddd; text-align: center;">&larr; Voltar para Lista</a>
            </div>
            <div class="sidebar-logout"><a href="../logout.php">Sair</a></div>
        </nav>

        <main class="main-content">
            <div style="display: flex; align-items: center; gap: 1.5rem; margin-bottom: 2rem;">
                <img src="../<?php echo !empty($cliente['foto_perfil_url']) ? htmlspecialchars($cliente['foto_perfil_url']) : 'uploads/default_perfil.png'; ?>" 
                     style="width: 100px; height: 100px; border-radius: 50%; border: 4px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                <div>
                    <h2 style="margin: 0;"><?php echo htmlspecialchars($cliente['nome']); ?></h2>
                    <p style="margin: 0; color: #666;"><?php echo htmlspecialchars($cliente['email']); ?></p>
                    <p style="margin: 0; color: #666;"><?php echo htmlspecialchars($cliente['telefone']); ?></p>
                </div>
            </div>

            <div class="dashboard-grid">
                <div class="dashboard-card" style="grid-column: span 1;">
                    <h3><i class="fas fa-sticky-note" style="color: #FBC02D;"></i> Anotações Internas</h3>
                    <p style="font-size: 0.85rem; color: #888;">Só você pode ver isso. Use para anotar preferências, alergias, etc.</p>
                    
                    <form action="processar_cliente_nota.php" method="POST">
                        <input type="hidden" name="id_cliente" value="<?php echo $id_cliente; ?>">
                        <div class="form-group">
                            <textarea name="anotacao" rows="8" style="resize: vertical;" placeholder="Ex: Cliente prefere ser atendido pelo Bruno..."><?php echo htmlspecialchars($nota_atual ?? ''); ?></textarea>
                        </div>
                        <button type="submit" class="form-button">Salvar Anotação</button>
                    </form>
                </div>

                <div class="dashboard-card" style="grid-column: span 2;">
                    <h3>Histórico de Visitas</h3>
                    <div class="lista-clientes-scroll">
                        <?php if (count($historico) > 0): ?>
                            <?php foreach ($historico as $ag): ?>
                                <div class="cliente-item">
                                    <div class="cliente-info">
                                        <strong><?php echo date('d/m/Y', strtotime($ag['data_hora_inicio'])); ?></strong>
                                        <div>
                                            <span><?php echo htmlspecialchars($ag['nome_servico']); ?></span>
                                            <small>com <?php echo htmlspecialchars($ag['nome_profissional']); ?></small>
                                        </div>
                                    </div>
                                    <span class="status-badge" style="font-size: 0.8rem; background-color: #eee; color: #555;">
                                        <?php echo htmlspecialchars(ucfirst($ag['status'])); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="padding: 1rem; color: #888; text-align: center;">Nenhum histórico encontrado.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script src="script_painel.js"></script>
</body>
</html>