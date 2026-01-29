<?php
session_start();
require '../conexao.php';

// --- Porteiro ---
if (!isset($_SESSION['usuario_id']) || $_SESSION['funcao'] != 'funcionario') {
    header("Location: ../login/login.php");
    exit();
}

$id_funcionario = $_SESSION['usuario_id'];
$id_negocio = $_SESSION['id_negocio'];
date_default_timezone_set('America/Sao_Paulo');

try {
    // --- 1. Busca os dados do FUNCIONÁRIO ---
    $stmt_user = $pdo->prepare("SELECT nome, email, foto_perfil_url FROM usuarios WHERE id = :id");
    $stmt_user->execute(['id' => $id_funcionario]);
    $funcionario = $stmt_user->fetch(PDO::FETCH_ASSOC);

    // --- 2. [NOVO] Busca o nome do NEGÓCIO ---
    $stmt_negocio = $pdo->prepare("SELECT nome_negocio FROM negocios WHERE id = :id_negocio");
    $stmt_negocio->execute(['id_negocio' => $id_negocio]);
    $negocio = $stmt_negocio->fetch(PDO::FETCH_ASSOC);

    // --- 3. Busca os "Próximos Clientes" de HOJE ---
    $agora = date('Y-m-d H:i:s');
    $hoje_fim = date('Y-m-d 23:59:59');
    $stmt_ag = $pdo->prepare(
        "SELECT a.id, a.data_hora_inicio, c.nome as cliente_nome, s.nome_servico
         FROM agendamentos a
         JOIN usuarios c ON a.id_cliente = c.id
         JOIN servicos s ON a.id_servico = s.id
         WHERE a.id_funcionario = :id_func
           AND a.data_hora_inicio BETWEEN :agora AND :hoje_fim
           AND a.status = 'confirmado'
         ORDER BY a.data_hora_inicio ASC LIMIT 5"
    );
    $stmt_ag->execute(['id_func' => $id_funcionario, 'agora' => $agora, 'hoje_fim' => $hoje_fim]);
    $proximos_clientes = $stmt_ag->fetchAll(PDO::FETCH_ASSOC);

    // --- 4. Busca os "Últimos Chats" ---
    $stmt_chats = $pdo->prepare(
        "SELECT 
            a.id as agendamento_id,
            u.nome as cliente_nome,
            u.foto_perfil_url as cliente_foto,
            (SELECT cm.mensagem FROM chat_mensagens cm 
             WHERE cm.id_agendamento = a.id 
             ORDER BY cm.data_envio DESC LIMIT 1) as ultima_mensagem,
            (SELECT cm.data_envio FROM chat_mensagens cm 
             WHERE cm.id_agendamento = a.id 
             ORDER BY cm.data_envio DESC LIMIT 1) as data_ultima_mensagem,
            (SELECT COUNT(*) FROM chat_mensagens cm 
             WHERE cm.id_agendamento = a.id 
             AND cm.status = 'sent' 
             AND cm.id_remetente != :id_funcionario) as novas_mensagens_count
        FROM agendamentos a
        JOIN usuarios u ON a.id_cliente = u.id
        WHERE a.id_funcionario = :id_funcionario
          AND EXISTS (SELECT 1 FROM chat_mensagens cm WHERE cm.id_agendamento = a.id)
        ORDER BY data_ultima_mensagem DESC
        LIMIT 5"
    );
    $stmt_chats->execute(['id_funcionario' => $id_funcionario]);
    $ultimos_chats = $stmt_chats->fetchAll(PDO::FETCH_ASSOC);

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
                <a href="index.php" class="active">Dashboard</a> 
                <a href="agenda_completa.php">Agenda Completa</a>
                <a href="meu_perfil.php">Meu Perfil</a>
                <a href="meu_portfolio.php">Meu Portfólio</a>
                <a href="meus_bloqueios.php">Meus Bloqueios</a>
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
                        <div class="notification-header">Notificações de Chat</div>
                        <div class="notification-list" id="notification-list">
                            </div>
                    </div>
                </div>
            </div>
            
            <p>Olá, <strong><?php echo htmlspecialchars(explode(' ', $funcionario['nome'])[0]); ?></strong>! Aqui está um resumo rápido do seu dia.</p>

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
                                            <small><?php echo htmlspecialchars($ag['nome_servico']); ?></small>
                                        </div>
                                    </div>
                                    <a href="../chat.php?id=<?php echo $ag['id']; ?>" class="icon-btn chat-link" title="Abrir Chat">
                                        <i class="fas fa-comment"></i>
                                    </a>
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
                    <h3>Meu Perfil</h3>
                    <div class="perfil-info-card">
                        <div>
                            <img src="../<?php echo htmlspecialchars($funcionario['foto_perfil_url'] ? $funcionario['foto_perfil_url'] : 'uploads/default_perfil.png'); ?>" alt="Sua Foto">
                            <strong><?php echo htmlspecialchars($funcionario['nome']); ?></strong>
                            <span><?php echo htmlspecialchars($funcionario['email']); ?></span>
                        </div>

                        <a href="../perfil_negocio.php?id=<?php echo $id_negocio; ?>" target="_blank" class="perfil-negocio-link">
                            <small>Você trabalha em:</small>
                            <strong><?php echo htmlspecialchars($negocio['nome_negocio']); ?></strong>
                            <span><i class="fas fa-external-link-alt"></i> Ver perfil público</span>
                        </a>

                        <a href="meu_perfil.php" class="form-button" style="width: 100%;">Editar Perfil</a>
                    </div>
                </div>
                
                <div class="dashboard-card card-ultimos-chats">
                    <h3>Últimas Mensagens</h3>
                    <div class="lista-chats-scroll">
                        <?php if (count($ultimos_chats) > 0): ?>
                            <?php foreach ($ultimos_chats as $chat): ?>
                                <a href="../chat.php?id=<?php echo $chat['agendamento_id']; ?>" class="chat-item">
                                    <div class="chat-item-foto">
                                        <img src="../<?php echo htmlspecialchars($chat['cliente_foto'] ? $chat['cliente_foto'] : 'uploads/default_perfil.png'); ?>" alt="Foto Cliente">
                                    </div>
                                    <div class="chat-item-info">
                                        <strong><?php echo htmlspecialchars($chat['cliente_nome']); ?></strong>
                                        <p><?php echo htmlspecialchars($chat['ultima_mensagem']); ?></p>
                                    </div>
                                    <?php if ($chat['novas_mensagens_count'] > 0): ?>
                                        <span class="chat-item-nova-badge"><?php echo $chat['novas_mensagens_count']; ?></span>
                                    <?php endif; ?>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-message-card">
                                <i class="fas fa-comments"></i>
                                <span>Nenhuma conversa ativa.</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
            </div>
        </main>
    </div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const bellContainer = document.getElementById('notification-bell-container');
        const bellIcon = document.getElementById('notification-bell');
        const badge = document.getElementById('notification-badge');
        const popup = document.getElementById('notification-popup');
        const notificationList = document.getElementById('notification-list');

        async function verificarNovasNotificacoes() {
            try {
                const response = await fetch('buscar_notificacoes.php');
                const data = await response.json();
                if (data.sucesso && data.count > 0) { badge.style.display = 'block'; } 
                else { badge.style.display = 'none'; }
            } catch (error) { console.error("Erro ao verificar notificações:", error); }
        }

        async function carregarNotificacoes() {
            notificationList.innerHTML = '<div class="notification-empty">Carregando...</div>';
            try {
                const response = await fetch('buscar_notificacoes.php');
                const data = await response.json();
                if (data.sucesso && data.count > 0) {
                    notificationList.innerHTML = ''; 
                    data.mensagens.forEach(msg => {
                        const item = document.createElement('a');
                        item.className = 'notification-item';
                        item.href = `../chat.php?id=${msg.id_agendamento}`; 
                        const dataMsg = new Date(msg.data_envio);
                        const hoje = new Date();
                        let dataFormatada = dataMsg.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
                        if (dataMsg.toDateString() !== hoje.toDateString()) {
                            dataFormatada = dataMsg.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' });
                        }
                        item.innerHTML = `
                            <div>
                                <strong>${msg.cliente_nome}</strong>
                                <small style="float: right;">${dataFormatada}</small>
                            </div>
                            <p>Serviço: ${msg.nome_servico}</p>
                            <p>"${msg.mensagem}"</p>
                        `;
                        notificationList.appendChild(item);
                    });
                } else if (data.sucesso && data.count === 0) {
                    notificationList.innerHTML = '<div class="notification-empty">Nenhuma nova notificação.</div>';
                } else {
                    notificationList.innerHTML = `<div class="notification-empty" style="color: red;">Erro: ${data.erro || 'Desconhecido'}</div>`;
                }
                badge.style.display = 'none';
            } catch (error) {
                console.error("Erro ao carregar notificações:", error);
                notificationList.innerHTML = `<div class="notification-empty" style="color: red;">Erro ao carregar (arquivo não encontrado ou falha de rede).</div>`;
            }
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
<?php require '../footer.php'; ?>
</body>
</html>