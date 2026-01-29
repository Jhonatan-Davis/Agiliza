<?php
session_start();
require 'conexao.php';

// --- Porteiro ---
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login/login.php");
    exit();
}

$id_cliente = $_SESSION['usuario_id'];
date_default_timezone_set('America/Sao_Paulo');

// Tradutores manuais
$dias_traduzidos = [0 => 'Domingo', 1 => 'Segunda-feira', 2 => 'Terça-feira', 3 => 'Quarta-feira', 4 => 'Quinta-feira', 5 => 'Sexta-feira', 6 => 'Sábado'];
$meses_traduzidos = [1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril', 5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto', 9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'];

try {
    // 1. Busca dados do usuário
    $stmt_user = $pdo->prepare("SELECT nome, foto_perfil_url FROM usuarios WHERE id = :id");
    $stmt_user->execute(['id' => $id_cliente]);
    $usuario = $stmt_user->fetch(PDO::FETCH_ASSOC);
    $_SESSION['usuario_nome'] = $usuario['nome'];
    $_SESSION['usuario_foto'] = $usuario['foto_perfil_url'];

    // 2. Busca Próximos Agendamentos
    $stmt_proximos = $pdo->prepare(
        "SELECT a.id, a.data_hora_inicio, a.id_negocio,
            n.nome_negocio, n.endereco_texto,
            f.nome AS funcionario_nome, s.nome_servico, a.status
        FROM agendamentos a
        JOIN negocios n ON a.id_negocio = n.id
        JOIN usuarios f ON a.id_funcionario = f.id
        JOIN servicos s ON a.id_servico = s.id
        WHERE a.id_cliente = :id_cliente 
          AND a.data_hora_inicio >= NOW() 
          AND a.status = 'confirmado'
        ORDER BY a.data_hora_inicio ASC"
    );
    $stmt_proximos->execute(['id_cliente' => $id_cliente]);
    $proximos_agendamentos = $stmt_proximos->fetchAll(PDO::FETCH_ASSOC);

    // 3. Busca Histórico
    $stmt_historico = $pdo->prepare(
        "SELECT a.id, a.data_hora_inicio, a.id_negocio,
            n.nome_negocio,
            f.nome AS funcionario_nome, s.nome_servico, a.status
        FROM agendamentos a
        JOIN negocios n ON a.id_negocio = n.id
        JOIN usuarios f ON a.id_funcionario = f.id
        JOIN servicos s ON a.id_servico = s.id
        WHERE a.id_cliente = :id_cliente 
          AND a.data_hora_inicio < NOW()
        ORDER BY a.data_hora_inicio DESC"
    );
    $stmt_historico->execute(['id_cliente' => $id_cliente]);
    $historico_agendamentos = $stmt_historico->fetchAll(PDO::FETCH_ASSOC);
    
    // --- [NOVO] 4. Verifica quais já foram avaliados ---
    // Cria uma lista simples de IDs que já têm avaliação
    $avaliados = [];
    if (count($historico_agendamentos) > 0) {
        // Pega todos os IDs do histórico
        $ids = array_column($historico_agendamentos, 'id');
        // Transforma em string para o SQL (ex: "1, 5, 9")
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        
        $stmt_av = $pdo->prepare("SELECT id_agendamento FROM avaliacoes WHERE id_cliente = ? AND id_agendamento IN ($placeholders)");
        // O primeiro parâmetro é o cliente, o resto são os IDs
        $params = array_merge([$id_cliente], $ids);
        $stmt_av->execute($params);
        
        // Cria um array onde a CHAVE é o ID do agendamento (para busca rápida)
        $avaliados = array_flip($stmt_av->fetchAll(PDO::FETCH_COLUMN));
    }

} catch (PDOException $e) {
    die("Erro ao buscar dados: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Agendamentos - Agiliza</title>
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
                        <img src="<?php echo htmlspecialchars($usuario['foto_perfil_url']); ?>?v=<?php echo time(); ?>" alt="Perfil" class="header-perfil-foto">
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
        <h1>Meus Agendamentos</h1>
        
        <?php
            if (isset($_GET['sucesso']) && $_GET['sucesso'] == 'cancelado') {
                echo '<div class="error-message auto-dismiss-message" style="background-color: #4CAF50; color: white; padding: 0.75rem; border-radius: 6px; margin-bottom: 1rem;">Agendamento cancelado com sucesso!</div>';
            }
            if (isset($_GET['sucesso']) && $_GET['sucesso'] == 'avaliado') {
                echo '<div class="error-message auto-dismiss-message" style="background-color: #4CAF50; color: white; padding: 0.75rem; border-radius: 6px; margin-bottom: 1rem;">Obrigado pela sua avaliação!</div>';
            }
        ?>
        
        <h2 class="section-title">Próximos Agendamentos</h2>
        <div class="appointments-list">
            <?php if (count($proximos_agendamentos) > 0): ?>
                <?php foreach ($proximos_agendamentos as $ag): ?>
                    <?php
                        $ts = strtotime($ag['data_hora_inicio']);
                        $dia_s = date('w', $ts); $d = date('d', $ts); $m = date('n', $ts); $y = date('Y', $ts);
                    ?>
                    <div class="appointment-card">
                        <div class="card-header">
                            <h3><?php echo $dias_traduzidos[$dia_s]; ?>, <?php echo $d; ?> de <?php echo $meses_traduzidos[$m]; ?> de <?php echo $y; ?></h3>
                            <span>às <?php echo date('H:i', $ts); ?></span>
                        </div>
                        <div class="card-body">
                            <strong><?php echo htmlspecialchars($ag['nome_negocio']); ?></strong>
                            <p><?php echo htmlspecialchars($ag['nome_servico']); ?> com <?php echo htmlspecialchars($ag['funcionario_nome']); ?></p>
                            <p class="endereco"><?php echo htmlspecialchars($ag['endereco_texto']); ?></p>
                        </div>
                        <div class="card-actions">
                            <a href="chat.php?id=<?php echo $ag['id']; ?>" class="action-btn chat-btn"><i class="fas fa-comment"></i> Chat</a>
                            <a href="cancelar_agendamento.php?id=<?php echo $ag['id']; ?>" 
                               class="action-btn cancel-btn"
                               onclick="return confirm('Tem certeza?');">
                               <i class="fas fa-times"></i> Cancelar
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="empty-message">Você não possui nenhum agendamento futuro.</p>
            <?php endif; ?>
        </div>

        <h2 class="section-title">Histórico de Agendamentos</h2>
        <div class="appointments-list">
            <?php if (count($historico_agendamentos) > 0): ?>
                <?php foreach ($historico_agendamentos as $ag): ?>
                    <?php
                        $ts = strtotime($ag['data_hora_inicio']);
                        $dia_s = date('w', $ts); $d = date('d', $ts); $m = date('n', $ts); $y = date('Y', $ts);
                        
                        // [LÓGICA DO BOTÃO]
                        // Verifica se o ID deste agendamento existe no array $avaliados
                        $ja_avaliado = isset($avaliados[$ag['id']]);
                        
                        // Se foi concluído E ainda não avaliou, pode avaliar
                        $pode_avaliar = ($ag['status'] == 'concluido' && !$ja_avaliado);
                    ?>
                    <div class="appointment-card is-past">
                        <div class="card-header">
                            <h3><?php echo $dias_traduzidos[$dia_s]; ?>, <?php echo $d; ?> de <?php echo $meses_traduzidos[$m]; ?> de <?php echo $y; ?></h3>
                            <span>às <?php echo date('H:i', $ts); ?></span>
                        </div>
                        <div class="card-body">
                            <strong><?php echo htmlspecialchars($ag['nome_negocio']); ?></strong>
                            <p><?php echo htmlspecialchars($ag['nome_servico']); ?> com <?php echo htmlspecialchars(explode(' ', $ag['funcionario_nome'])[0]); ?></p>
                            
                            <?php if ($ja_avaliado): ?>
                                <p class="status-badge avaliado"><i class="fas fa-star"></i> Já Avaliado</p>
                            <?php else: ?>
                                <p class="status-badge"><?php echo htmlspecialchars(ucfirst($ag['status'])); ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="card-actions">
                            <?php if ($pode_avaliar): ?>
                                <a href="avaliar.php?id=<?php echo $ag['id']; ?>" class="action-btn review-btn">
                                    <i class="fas fa-star"></i> Avaliar
                                </a>
                            <?php else: ?>
                                <a href="agendamento.php?id_negocio=<?php echo $ag['id_negocio']; ?>" class="action-btn rebook-btn">
                                    Agendar Novamente
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="empty-message">Histórico vazio.</p>
            <?php endif; ?>
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
        document.addEventListener('DOMContentLoaded', (event) => {
            const alertMessage = document.querySelector('.auto-dismiss-message');
            if (alertMessage) {
                setTimeout(() => {
                    alertMessage.style.transition = 'opacity 0.5s ease';
                    alertMessage.style.opacity = '0';
                    setTimeout(() => alertMessage.style.display = 'none', 500);
                }, 3000);
            }
        });
    </script>
</body>
</html>