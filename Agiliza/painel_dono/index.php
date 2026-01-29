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
date_default_timezone_set('America/Sao_Paulo');

try {
    // --- 1. Busca os dados do NEGÓCIO ---
    $stmt_negocio = $pdo->prepare("SELECT nome_negocio, foto_perfil_url FROM negocios WHERE id = :id_negocio");
    $stmt_negocio->execute(['id_negocio' => $id_negocio]);
    $negocio = $stmt_negocio->fetch(PDO::FETCH_ASSOC);

    // --- 2. Verifica se o Dono também é Funcionário ---
    $stmt_check_func = $pdo->prepare(
        "SELECT COUNT(*) FROM negocio_membros 
         WHERE id_usuario = :id_dono AND id_negocio = :id_negocio AND funcao = 'funcionario'"
    );
    $stmt_check_func->execute(['id_dono' => $id_dono, 'id_negocio' => $id_negocio]);
    $dono_e_funcionario = $stmt_check_func->fetchColumn() > 0;

    // --- 3. Busca os "Próximos Clientes" de HOJE ---
    $agora = date('Y-m-d H:i:s');
    $hoje_fim = date('Y-m-d 23:59:59');
    
    // [CORREÇÃO NA SQL] Adicionei 'f.id as funcionario_id'
    $stmt_ag = $pdo->prepare(
        "SELECT a.id, a.data_hora_inicio, 
                c.nome as cliente_nome, 
                f.nome as funcionario_nome, f.id as funcionario_id, -- PRECISAMOS DESTE ID
                s.nome_servico
         FROM agendamentos a
         JOIN usuarios c ON a.id_cliente = c.id
         JOIN usuarios f ON a.id_funcionario = f.id
         JOIN servicos s ON a.id_servico = s.id
         WHERE a.id_negocio = :id_negocio
           AND a.data_hora_inicio BETWEEN :agora AND :hoje_fim
           AND a.status = 'confirmado'
         ORDER BY a.data_hora_inicio ASC
         LIMIT 10"
    );
    $stmt_ag->execute(['id_negocio' => $id_negocio, 'agora' => $agora, 'hoje_fim' => $hoje_fim]);
    $proximos_clientes = $stmt_ag->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erro ao carregar dashboard: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Agiliza</title>
    <link rel="stylesheet" href="style_painel.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="painel-container">
        <header class="mobile-header">
            <button class="menu-toggle" id="menu-toggle">
                <i class="fas fa-bars"></i>
            </button>
            <h1>Agili<span>za</span></h1>
        </header>
        <h1>Agili<span>za</span></h1>
        <nav class="sidebar">
            <div class="sidebar-nav">
                <a href="index.php" class="active">Dashboard</a> 
                <a href="agenda_completa.php">Agenda Completa</a>
                <a href="meu_negocio.php">Meu Negócio</a>
                <a href="minha_equipe.php">Minha Equipe</a>
                <a href="meus_servicos.php">Meus Serviços</a>
                <a href="meus_horarios.php">Meus Horários</a>
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
            
            <div class="header-com-notificacao">
                <h2>Dashboard</h2>
                <div class="notification-icon-container" id="notification-bell-container">
                    <i class="fas fa-bell notification-bell" id="notification-bell"></i>
                    <span class="notification-badge" id="notification-badge" style="display: none;"></span>
                    <div class="notification-popup" id="notification-popup">
                        <div class="notification-header">Notificações</div>
                        <div class="notification-list" id="notification-list">
                            <div class="notification-empty">Carregando...</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <p>Olá, <strong><?php echo htmlspecialchars(explode(' ', $_SESSION['usuario_nome'])[0]); ?></strong>! Aqui está o resumo do seu negócio hoje.</p>

            <div class="dashboard-grid">
            
                <div class="dashboard-card card-proximos-clientes">
                    <h3>Próximos Clientes (Hoje)</h3>
                    <div class="lista-clientes-scroll">
                        <?php if (count($proximos_clientes) > 0): ?>
                            <?php foreach ($proximos_clientes as $ag): ?>
                                <div class="cliente-item">
                                    <div class="cliente-info">
                                        <strong><?php echo date('H:i', strtotime($ag['data_hora_inicio'])); ?></strong>
                                        <div>
                                            <span><?php echo htmlspecialchars($ag['cliente_nome']); ?></span>
                                            <small><?php echo htmlspecialchars($ag['nome_servico']); ?> com <strong><?php echo htmlspecialchars(explode(' ', $ag['funcionario_nome'])[0]); ?></strong></small>
                                        </div>
                                    </div>
                                    
                                    <?php if ($ag['funcionario_id'] == $id_dono): ?>
                                        <a href="../chat.php?id=<?php echo $ag['id']; ?>" class="icon-btn chat-link" title="Abrir Chat">
                                            <i class="fas fa-comment"></i>
                                        </a>
                                    <?php else: ?>
                                        <span class="icon-btn chat-link" style="color: #ccc; cursor: not-allowed;" title="Chat privado do funcionário">
                                            <i class="fas fa-comment-slash"></i>
                                        </span>
                                    <?php endif; ?>
                                    
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-message-card">
                                <i class="fas fa-coffee"></i>
                                <span>Sem agendamentos confirmados para hoje.</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="dashboard-card card-meu-perfil">
                    <h3>Meu Negócio</h3>
                    <div class="perfil-info-card">
                        <img src="../<?php echo htmlspecialchars($negocio['foto_perfil_url'] ? $negocio['foto_perfil_url'] : 'uploads/default_negocio.png'); ?>" alt="Logo do Negócio">
                        <strong><?php echo htmlspecialchars($negocio['nome_negocio']); ?></strong>
                        <span>Proprietário(a)</span>
                        <a href="../perfil_negocio.php?id=<?php echo $id_negocio; ?>" target="_blank" class="perfil-negocio-link" style="margin-top: 1rem;">
                            <span><i class="fas fa-external-link-alt"></i> Ver perfil público</span>
                        </a>
                        <a href="meu_negocio.php" class="form-button" style="width: 100%; margin-top: 1rem;">Editar Meu Negócio</a>
                    </div>
                </div>
                
                <div class="dashboard-card card-rendimento-mensal">
                    <h3>Rendimento (Últimos 6 meses)</h3>
                    <div class="grafico-wrapper">
                        <canvas id="graficoRendimentoMensal"></canvas>
                    </div>
                </div>
            </div>
        </main>
    </div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // --- Gráfico ---
        const corRoxa = '#7E57C2';
        const corRoxaClara = 'rgba(126, 87, 194, 0.2)';
        const ctxRendimento = document.getElementById('graficoRendimentoMensal').getContext('2d');
        
        fetch('buscar_dados_graficos.php?grafico=rendimento_mensal')
            .then(response => response.json())
            .then(data => {
                if (!data.labels || data.labels.length === 0) {
                    ctxRendimento.canvas.parentNode.innerHTML = '<div class="no-data-message" style="min-height: 200px;">Nenhum faturamento concluído encontrado nos últimos 6 meses.</div>';
                    return;
                }
                new Chart(ctxRendimento, {
                    type: 'bar',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: 'R$ Faturado',
                            data: data.valores,
                            backgroundColor: corRoxaClara,
                            borderColor: corRoxa,
                            borderWidth: 2
                        }]
                    },
                    options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
                });
            });

        // --- Notificações ---
        const bellContainer = document.getElementById('notification-bell-container');
        const bellIcon = document.getElementById('notification-bell');
        const badge = document.getElementById('notification-badge');
        const popup = document.getElementById('notification-popup');
        const notificationList = document.getElementById('notification-list');

        async function verificarNovasNotificacoes() {
            try {
                const response = await fetch('buscar_notificacoes_dono.php');
                const data = await response.json();
                if (data.sucesso && data.count > 0) { badge.style.display = 'block'; } 
                else { badge.style.display = 'none'; }
            } catch (error) { console.error(error); }
        }

        async function carregarNotificacoes() {
            notificationList.innerHTML = '<div class="notification-empty">Carregando...</div>';
            try {
                const response = await fetch('buscar_notificacoes_dono.php');
                const data = await response.json();
                if (data.sucesso && data.count > 0) {
                    notificationList.innerHTML = ''; 
                    data.mensagens.forEach(msg => {
                        const item = document.createElement('a');
                        item.className = 'notification-item';
                        // Dono não clica para ir ao chat (regra geral), a menos que queira ver detalhes
                        // Aqui mantemos sem link, ou você pode redirecionar se quiser.
                        item.href = "#"; 
                        item.onclick = (e) => e.preventDefault();
                        item.style.cursor = 'default';

                        const dataMsg = new Date(msg.data_envio);
                        let dataFormatada = dataMsg.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });

                        item.innerHTML = `<div><strong>${msg.remetente_nome}</strong><small style="float: right;">${dataFormatada}</small></div><p>Serviço: ${msg.nome_servico}</p><p>"${msg.mensagem}"</p>`;
                        notificationList.appendChild(item);
                    });
                } else if (data.sucesso && data.count === 0) {
                    notificationList.innerHTML = '<div class="notification-empty">Nenhuma nova notificação.</div>';
                }
                badge.style.display = 'none';
            } catch (error) { notificationList.innerHTML = `<div class="notification-empty" style="color: red;">Erro ao carregar.</div>`; }
        }

        bellIcon.addEventListener('click', (e) => {
            e.stopPropagation();
            const isShown = popup.classList.toggle('show');
            if (isShown) { carregarNotificacoes(); }
        });
        window.addEventListener('click', (e) => {
            if (popup.classList.contains('show') && !bellContainer.contains(e.target)) {
                popup.classList.remove('show');
            }
        });
        verificarNovasNotificacoes();
        setInterval(verificarNovasNotificacoes, 30000);
    });
</script>
<script src="script_painel.js"></script>
</body>
</html>