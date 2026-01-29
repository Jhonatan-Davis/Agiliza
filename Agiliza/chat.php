<?php
session_start();
require 'conexao.php';
date_default_timezone_set('America/Sao_Paulo');

// --- 1. Porteiro ---
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login/login.php");
    exit();
}

// --- 2. Validar Agendamento ---
$id_agendamento = $_GET['id'] ?? 0;
$id_usuario_logado = $_SESSION['usuario_id'];

try {
    // Busca os dados (já estava correto)
    $stmt = $pdo->prepare(
        "SELECT a.*, n.nome_negocio, 
                c.nome as cliente_nome, c.foto_perfil_url as cliente_foto, 
                f.nome as func_nome, f.foto_perfil_url as func_foto
         FROM agendamentos a
         JOIN negocios n ON a.id_negocio = n.id
         JOIN usuarios c ON a.id_cliente = c.id
         JOIN usuarios f ON a.id_funcionario = f.id
         WHERE a.id = :id_agendamento 
           AND (a.id_cliente = :id_usuario OR a.id_funcionario = :id_usuario)"
    );
    $stmt->execute([
        'id_agendamento' => $id_agendamento,
        'id_usuario' => $id_usuario_logado
    ]);
    $agendamento = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$agendamento) {
        die("Agendamento não encontrado ou você não tem permissão para ver este chat.");
    }
    
    // --- 3. A REGRA DOS 3 DIAS ---
    $data_agendamento = new DateTime($agendamento['data_hora_inicio']);
    $data_limite = (clone $data_agendamento)->modify('+3 days');
    $data_hoje = new DateTime();
    $chat_expirado = ($data_hoje > $data_limite);
    
    // Define quem é "Eu" e quem é "Outro"
    $eu_sou_cliente = ($id_usuario_logado == $agendamento['id_cliente']);
    $nome_outro = $eu_sou_cliente ? $agendamento['func_nome'] : $agendamento['cliente_nome'];
    $foto_outro = $eu_sou_cliente ? $agendamento['func_foto'] : $agendamento['cliente_foto'];

} catch (PDOException $e) {
    die("Erro ao carregar chat: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat do Agendamento - Agiliza</title>
    <link rel="stylesheet" href="style_site.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>

    <header class="site-header">
        <a href="index.php" class="logo">Agili<span>za</span></a>
        <nav>
            <div class="user-dropdown">
                <button class="dropdown-btn">
                    </button>
            </div>
        </nav>
    </header>

    <main class="chat-container">
        
        <div class="chat-header">
            <div class="chat-header-info">
                <h1>Conversando com: <strong><?php echo htmlspecialchars($nome_outro); ?></strong></h1>
                <p>Chat: <?php echo htmlspecialchars($agendamento['nome_negocio']); ?></p>
            </div>
            <div class="chat-header-actions">
                
                <?php if ($eu_sou_cliente): ?>
                    <a href="meus_agendamentos.php" class="wizard-back-btn">&larr; Voltar</a>
                <?php else: ?>
                    <a href="painel_funcionario/index.php" class="wizard-back-btn">&larr; Voltar</a>
                <?php endif; ?>
                
                <img src="<?php echo htmlspecialchars($foto_outro ? $foto_outro : 'uploads/default_perfil.png'); ?>" 
                     alt="<?php echo htmlspecialchars($nome_outro); ?>" class="chat-header-foto">
            </div>
        </div>

        <div class="chat-window" id="chat-window">
            </div>

        <?php if ($chat_expirado): ?>
            <div class="chat-form disabled">
                Este chat expirou (3 dias após o agendamento).
            </div>
        <?php else: ?>
            <form class="chat-form" id="chat-form">
                <input type="hidden" name="id_agendamento" value="<?php echo $id_agendamento; ?>">
                <input type="text" id="mensagem-input" name="mensagem" placeholder="Digite sua mensagem..." autocomplete="off" required>
                <button type="submit"><i class="fas fa-paper-plane"></i></button>
            </form>
        <?php endif; ?>
        
    </main>

<script>
    // --- (Script do Dropdown do Header - Opcional, mas bom ter) ---
    const dropdownBtn = document.querySelector('.dropdown-btn');
    const dropdownContent = document.querySelector('.dropdown-content');
    if (dropdownBtn) {
        dropdownBtn.addEventListener('click', (e) => { e.stopPropagation(); dropdownContent.classList.toggle('show'); });
        window.addEventListener('click', (e) => { if (dropdownContent && dropdownContent.classList.contains('show') && !e.target.closest('.user-dropdown')) { dropdownContent.classList.remove('show'); }});
    }

    // --- Script do Chat ---
    const chatWindow = document.getElementById('chat-window');
    const chatForm = document.getElementById('chat-form');
    const mensagemInput = document.getElementById('mensagem-input');
    const idAgendamento = <?php echo $id_agendamento; ?>;
    const idUsuarioLogado = <?php echo $id_usuario_logado; ?>;
    let autoScroll = true; // Variável para controlar o scroll

    function rolarParaFim() {
        if(autoScroll) {
            chatWindow.scrollTop = chatWindow.scrollHeight;
        }
    }
    
    // Detecta se o usuário rolou para cima (para não forçar o scroll)
    chatWindow.addEventListener('scroll', () => {
        if (chatWindow.scrollTop + chatWindow.clientHeight < chatWindow.scrollHeight - 50) {
            autoScroll = false;
        } else {
            autoScroll = true;
        }
    });

    // --- 2. Função para BUSCAR Novas Mensagens (Atualizada) ---
    async function buscarMensagens() {
        try {
            const response = await fetch(`buscar_mensagens.php?id_agendamento=${idAgendamento}`);
            const mensagens = await response.json();
            
            chatWindow.innerHTML = ''; 

            if (mensagens.erro) {
                chatWindow.innerHTML = `<p style="color:red;">${mensagens.erro}</p>`;
                return;
            }

            mensagens.forEach(msg => {
                const eMinha = (msg.id_remetente == idUsuarioLogado);
                const classeCSS = eMinha ? 'bubble-mine' : 'bubble-theirs';
                
                // [CORREÇÃO] Pega a data e formata
                const dataEnvio = new Date(msg.data_envio);
                const horaFormatada = dataEnvio.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });

                let statusHtml = '';
                // [CORREÇÃO] Adiciona os ícones de status (só nas minhas)
                if (eMinha) {
                    if (msg.status === 'read') {
                        // Visto (Azul)
                        statusHtml = '<span class="message-status read"><i class="fas fa-check-double"></i></span>';
                    } else {
                        // Enviado (Cinza)
                        statusHtml = '<span class="message-status"><i class="fas fa-check"></i></span>';
                    }
                }

                const bubble = document.createElement('div');
                bubble.className = `message-bubble ${classeCSS}`;
                bubble.innerHTML = `
                    <strong>${msg.nome_remetente}</strong>
                    ${msg.mensagem}
                    <span class="message-timestamp">
                        ${horaFormatada}
                        ${statusHtml}
                    </span>
                `;
                chatWindow.appendChild(bubble);
            });
            
            rolarParaFim();
            
        } catch (error) {
            console.error('Erro ao buscar mensagens:', error);
        }
    }

    // --- 3. Função para ENVIAR Mensagem (Atualizada) ---
    if (chatForm) {
        chatForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const mensagem = mensagemInput.value;
            if (mensagem.trim() === '') return;
            
            // Força o scroll para o fim, pois o usuário enviou
            autoScroll = true; 
            mensagemInput.value = '';

            const formData = new FormData();
            formData.append('id_agendamento', idAgendamento);
            formData.append('mensagem', mensagem);

            try {
                // (O enviar_mensagem.php já salva como "sent" por padrão)
                await fetch('enviar_mensagem.php', {
                    method: 'POST',
                    body: formData
                });
                
                // Busca as mensagens imediatamente após enviar
                buscarMensagens();
                
            } catch (error) {
                console.error('Erro ao enviar mensagem:', error);
            }
        });
    }

    // --- 4. Iniciar o Chat ---
    buscarMensagens();
    setInterval(buscarMensagens, 5000); // Atualiza a cada 5 segundos
</script>
<?php require 'footer.php'; ?>
</body>
</html>