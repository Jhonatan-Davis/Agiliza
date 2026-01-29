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
// --- [CORREÇÃO] Verifica se o Dono também é Funcionário ---
$stmt_check_func = $pdo->prepare(
    "SELECT COUNT(*) FROM negocio_membros 
     WHERE id_usuario = :id_dono AND id_negocio = :id_negocio AND funcao = 'funcionario'"
);
$stmt_check_func->execute(['id_dono' => $id_dono, 'id_negocio' => $id_negocio]);
$dono_e_funcionario = $stmt_check_func->fetchColumn() > 0;

try {
    // 1. Busca a equipe ATUAL
    $stmt_equipe = $pdo->prepare(
        "SELECT u.id, u.nome, u.email 
         FROM usuarios u
         JOIN negocio_membros m ON u.id = m.id_usuario
         WHERE m.id_negocio = :id_negocio AND m.funcao = 'funcionario'"
    );
    $stmt_equipe->execute(['id_negocio' => $id_negocio]);
    $equipe = $stmt_equipe->fetchAll(PDO::FETCH_ASSOC);

    // 2. Verifica se o Dono JÁ ESTÁ na lista de funcionários
    $dono_ja_e_funcionario = false;
    foreach ($equipe as $membro) {
        if ($membro['id'] == $id_dono) {
            $dono_ja_e_funcionario = true;
            break;
        }
    }

} catch (PDOException $e) {
    die("Erro ao buscar equipe: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Equipe - Agiliza</title>
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
                <a href="agenda_completa.php" >Agenda Completa</a>
                <a href="meu_negocio.php" >Meu Negócio</a>
                <a href="minha_equipe.php" class="active">Minha Equipe</a>
                <a href="meus_servicos.php">Meus Serviços</a>
                <a href="meus_horarios.php" >Meus Horários</a>
                <a href="historico.php" >Histórico e Vendas</a>
                <a href="financeiro.php">Financeiro</a>
                <a href="meus_clientes.php">Meus Clientes</a>
                
                <?php if ($dono_e_funcionario): // A lógica PHP que já existe ?>
                    
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
            <h2>Gerenciar Equipe</h2>
            
            <?php
                if (isset($_GET['sucesso']) && $_GET['sucesso'] == 'dono_adicionado') {
                    echo '<div class="error-message auto-dismiss-message" style="background-color: #4CAF50;">Você foi adicionado à lista de funcionários com sucesso!</div>';
                }
                if (isset($_GET['sucesso']) && $_GET['sucesso'] == 'dono_removido') {
                    echo '<div class="error-message auto-dismiss-message" style="background-color: #4CAF50;">Você foi removido da lista de funcionários.</div>';
                }
                if (isset($_GET['erro'])) {
                    echo '<div class="error-message auto-dismiss-message" style="background-color: #ff3333;">'.htmlspecialchars($_GET['erro']).'</div>';
                }
            ?>

            <?php if (!$dono_ja_e_funcionario): // Só mostra se ele AINDA NÃO é funcionário ?>
                <div class="form-card assistente-rapido-card" style="margin-bottom: 2rem;">
                    <h3><i class="fas fa-star" style="margin-right: 10px;"></i>Dono Visível na Agenda</h3>
                    <p>Você também atende clientes? Adicione seu próprio perfil (<?php echo htmlspecialchars($_SESSION['usuario_nome']); ?>) à lista de funcionários para que os clientes possam agendar com você.</p>
                    <form action="processar_dono_como_func.php" method="POST">
                        <button type="submit" class="form-button">Sim, me adicionar à lista</button>
                    </form>
                </div>
            <?php endif; ?>

            <div class="form-card" style="margin-bottom: 2rem;">
                <h3>Cadastrar Novo Funcionário</h3>
                <form action="processar_add_funcionario.php" method="POST">
                    <div class="form-group">
                        <label for="nome">Nome do Funcionário</label>
                        <input type="text" id="nome" name="nome" required>
                    </div>
                    <div class="form-group">
                        <label for="email">E-mail (para login)</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="senha">Senha Provisória</label>
                        <input type="password" id="senha" name="senha" required>
                    </div>
                    <button type="submit" class="form-button">Adicionar à Equipe</button>
                </form>
            </div>

            <h3>Equipe Atual</h3>
            <table class="tabela-agenda">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>E-mail (Login)</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($equipe as $membro): ?>
                        <tr>
                            <td>
                                <?php echo htmlspecialchars($membro['nome']); ?>
                                <?php if ($membro['id'] == $id_dono) echo ' <strong>(Você)</strong>'; ?>
                            </td>
                            <td><?php echo htmlspecialchars($membro['email']); ?></td>
                            <td class="actions-cell">
                                
                                <?php if ($membro['id'] == $id_dono): ?>
                                    <a href="processar_remover_dono_como_func.php" 
                                       class="btn-remover-dono" 
                                       onclick="return confirm('Tem certeza que deseja se remover da lista de funcionários? Você não poderá mais receber agendamentos.');">
                                       Deixar de ser funcionário
                                    </a>
                                <?php else: ?>
                                    <a href="processar_remover_funcionario.php?id=<?php echo $membro['id']; ?>" 
                                       class="icon-delete" title="Remover Funcionário"
                                       onclick="return confirm('Tem certeza?');">
                                       <i class="fas fa-trash-alt"></i>
                                    </a>
                                <?php endif; ?>
                                
                            </td>
                        </tr>
                    <?php endforeach; ?>
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
    <?php require '../footer.php'; ?>
</body>
</html>