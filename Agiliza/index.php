<?php
session_start();
require 'conexao.php'; // Inclui a conexão
date_default_timezone_set('America/Sao_Paulo');

// --- 1. Verifica se o usuário é um visitante (sem login) ---
$visitante = !isset($_SESSION['usuario_id']);

// --- 2. Prepara variáveis ---
$usuario = null;
$tipos_de_negocio = [];
$negocios = [];
$busca_atual = '';
$tipo_atual = 0;

if (!$visitante) {
    // ===========================================
    //  O USUÁRIO ESTÁ LOGADO
    // ===========================================
    try {
        // 1. Busca os dados do usuário
        $stmt_user = $pdo->prepare("SELECT nome, foto_perfil_url FROM usuarios WHERE id = :id");
        $stmt_user->execute(['id' => $_SESSION['usuario_id']]);
        $usuario = $stmt_user->fetch(PDO::FETCH_ASSOC);
        
        if($usuario) {
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_foto'] = $usuario['foto_perfil_url'];
        }

        // 2. Busca os Tipos de Negócio
        $stmt_tipos = $pdo->query("SELECT id, nome_tipo FROM tipos_negocio ORDER BY nome_tipo ASC");
        $tipos_de_negocio = $stmt_tipos->fetchAll(PDO::FETCH_ASSOC);

        // 3. Lógica de Busca
        $busca_atual = isset($_GET['busca']) ? htmlspecialchars($_GET['busca']) : '';
        $tipo_atual = isset($_GET['id_tipo']) ? (int)$_GET['id_tipo'] : 0; 
        
        $sql = "SELECT n.id, n.nome_negocio, n.foto_perfil_url, n.capa_url, n.endereco_texto,
                       (SELECT COUNT(*) FROM favoritos f WHERE f.id_negocio = n.id AND f.id_cliente = :id_usuario) as e_favorito
                FROM negocios n 
                WHERE 1=1";
        
        $params = [':id_usuario' => $_SESSION['usuario_id']];

        if (!empty($busca_atual)) {
            $sql .= " AND n.nome_negocio LIKE :busca";
            $params[':busca'] = '%' . $busca_atual . '%';
        }
        if (!empty($tipo_atual)) {
            $sql .= " AND n.id_tipo_negocio = :id_tipo";
            $params[':id_tipo'] = $tipo_atual;
        }
        
        $sql .= " ORDER BY e_favorito DESC, n.nome_negocio ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $negocios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        die("Erro ao buscar dados: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agiliza - Encontre e Agende Serviços</title>
    <link rel="stylesheet" href="style_site.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="shortcut icon" href="uploads/logo_agiliza.png" type="image/png">
</head>
<body>

    <header class="site-header">
        <a href="index.php" class="logo">Agili<span>za</span></a>
        
        <nav>
            <?php if (!$visitante && $usuario): ?>
                
                <div style="display: flex; align-items: center;">
                    
                    <div class="notification-container" id="notif-container">
                        <i class="fas fa-bell notification-icon"></i>
                        <span class="notification-badge" id="notif-badge" style="display: none;">0</span>
                        
                        <div class="notification-popup" id="notif-popup">
                            <div class="notification-header">Suas Notificações</div>
                            <div class="notification-list" id="notif-list">
                                <div class="notification-empty">Carregando...</div>
                            </div>
                        </div>
                    </div>
                    <div class="user-dropdown">
                        <button class="dropdown-btn">
                            <?php if (!empty($usuario['foto_perfil_url'])): ?>
                                <img src="<?php echo htmlspecialchars($usuario['foto_perfil_url']); ?>?v=<?php echo time(); ?>" alt="Perfil" class="header-perfil-foto">
                            <?php endif; ?>
                            Olá, <?php echo htmlspecialchars(explode(' ', $usuario['nome'])[0]); ?>!
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="dropdown-content">
                            <a href="meu_perfil.php">Meu Perfil</a>
                            <a href="meus_agendamentos.php">Meus Agendamentos</a>
                            <div class="dropdown-divider"></div>
                            <a href="logout.php" class="logout-link">Sair</a>
                        </div>
                    </div>
                
                </div> 

            <?php else: ?>
                <a href="login/login.php">Fazer Login</a>
                <a href="cadastro/selecao.php" class="form-button" style="padding: 0.5rem 1rem; margin-left: 1rem;">Cadastre-se</a>
            <?php endif; ?>
        </nav>
    </header>

    <?php if ($visitante): ?>
    
        <div class="hero-section">
            <h1>Encontre o profissional perfeito, na hora que você precisa.</h1>
            <p>Chega de telefonemas e espera. No Agiliza, você encontra barbearias, clínicas, estúdios e muito mais, vê a agenda em tempo real e marca seu horário com 3 cliques.</p>
            <a href="cadastro/selecao.php" class="form-button">Cadastre-se Gratuitamente</a>
        </div>
        
        <main class="site-container">
            <div class="how-it-works-section">
                <h2>Como Funciona?</h2>
                <div class="how-it-works-grid">
                    <div class="how-it-works-step">
                        <i class="fas fa-search"></i>
                        <h3>1. Busque</h3>
                        <p>Filtre por nome ou tipo de negócio.</p>
                    </div>
                    <div class="how-it-works-step">
                        <i class="fas fa-calendar-check"></i>
                        <h3>2. Agende</h3>
                        <p>Escolha o profissional e o melhor horário para você.</p>
                    </div>
                    <div class="how-it-works-step">
                        <i class="fas fa-store"></i>
                        <h3>3. Compareça</h3>
                        <p>Vá até o local e receba seu atendimento sem filas.</p>
                    </div>
                </div>
            </div>
        </main>
        
        <section class="testimonials-section">
            <div class="site-container" style="padding: 0; margin-top: 0; margin-bottom: 0;"> 
                <h2>O que nossos clientes dizem</h2>
                <div class="testimonials-grid">
                    <div class="testimonial-card">
                        <p>"Finalmente um app que funciona! Agendei meu corte na barbearia em 30 segundos. Recomendo muito."</p>
                        <div class="rating-stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                        <cite>Jhonatan D.</cite>
                    </div>
                    <div class="testimonial-card">
                        <p>"Consegui marcar minha manicure de última hora. O sistema de chat para confirmar com a profissional é ótimo."</p>
                        <div class="rating-stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i></div>
                        <cite>Maria S.</cite>
                    </div>
                    <div class="testimonial-card">
                        <p>"Excelente plataforma. Encontrei um fisioterapeuta perto de casa e vi todos os horários livres sem precisar ligar."</p>
                        <div class="rating-stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                        <cite>Carlos R.</cite>
                    </div>
                </div>
            </div>
        </section>
        
        <section class="support-cta-section">
            <h2>Pronto para começar?</h2>
            <p>Se tiver dúvidas, nossa equipe está pronta para ajudar.</p>
            <?php
                $numero_wpp = "5564992818272";
                $mensagem_wpp = urlencode("Olá! Vim pelo site Agiliza e gostaria de mais informações.");
                $link_wpp = "https://wa.me/" . $numero_wpp . "?text=" . $mensagem_wpp;
            ?>
            <a href="<?php echo $link_wpp; ?>" class="whatsapp-btn" target="_blank">
                <i class="fab fa-whatsapp"></i> Fale Conosco no WhatsApp
            </a>
        </section>
        
    <?php else: ?>

        <main class="site-container">
            <h2 style="margin-top: 1rem;">Encontre um estabelecimento</h2>
            
            <form action="index.php" method="GET" class="search-filter-bar">
                <div class="search-group">
                    <label for="busca">Nome do estabelecimento</label>
                    <input type="text" id="busca" name="busca" class="search-input" placeholder="Ex: Barbearia do Zé" value="<?php echo $busca_atual; ?>">
                </div>
                <div class="filter-group">
                    <label for="id_tipo">Filtrar por tipo de negócio</label>
                    <select id="id_tipo" name="id_tipo" class="filter-select">
                        <option value="">Todos os Tipos</option>
                        <?php foreach ($tipos_de_negocio as $tipo): ?>
                            <option value="<?php echo $tipo['id']; ?>" <?php if ($tipo['id'] == $tipo_atual) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($tipo['nome_tipo']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="search-button"><i class="fas fa-search"></i></button>
            </form>

            <div class="business-grid">
                <?php if (count($negocios) > 0): ?>
                    <?php foreach ($negocios as $negocio): ?>
                        <a href="perfil_negocio.php?id=<?php echo $negocio['id']; ?>" class="business-card">
                            <div class="card-capa" <?php if (!empty($negocio['capa_url'])): ?> style="background-image: url('<?php echo htmlspecialchars($negocio['capa_url']); ?>');" <?php endif; ?>>
                                <?php if ($negocio['e_favorito']): ?>
                                    <span class="card-favorite-badge" title="Favorito"><i class="fas fa-heart"></i></span>
                                <?php endif; ?>
                                <?php if (!empty($negocio['foto_perfil_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($negocio['foto_perfil_url']); ?>" alt="Perfil" class="card-perfil">
                                <?php endif; ?>
                            </div>
                            <div class="card-info">
                                <h3><?php echo htmlspecialchars($negocio['nome_negocio']); ?></h3>
                                <p style="font-size: 0.9rem; color: #777;"><?php echo htmlspecialchars($negocio['endereco_texto']); ?></p>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #777; grid-column: 1 / -1;">Nenhum negócio encontrado com esses filtros.</p>
                <?php endif; ?>
            </div>
        </main>

    <?php endif; ?>

    <?php require 'footer.php'; ?>
    
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // 1. Dropdown do Usuário
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

            // 2. Notificações (Só se o container existir)
            const notifContainer = document.getElementById('notif-container');
            const notifBadge = document.getElementById('notif-badge');
            const notifPopup = document.getElementById('notif-popup');
            const notifList = document.getElementById('notif-list');

            if (notifContainer) {
                async function checarNotificacoes() {
                    try {
                        const response = await fetch('buscar_notificacoes_cliente.php');
                        const data = await response.json();
                        if (data.sucesso) {
                            if (data.count > 0) {
                            notifBadge.style.display = 'block';
                            // O número foi removido, agora é só o ponto.
                        } else {
                                notifBadge.style.display = 'none';
                            }

                            if (data.lista && data.lista.length > 0) {
                                notifList.innerHTML = '';
                                data.lista.forEach(notif => {
                                    const dataObj = new Date(notif.data);
                                    const dataFormatada = dataObj.toLocaleDateString('pt-BR');
                                    const item = document.createElement('a');
                                    item.className = 'notification-item';
                                    item.href = notif.link;
                                    item.innerHTML = `<strong>${notif.titulo}</strong><p>${notif.texto}</p><small>${dataFormatada}</small>`;
                                    notifList.appendChild(item);
                                });
                            } else {
                                notifList.innerHTML = '<div class="notification-empty">Nenhuma notificação.</div>';
                            }
                        }
                    } catch (error) { console.error(error); }
                }

                notifContainer.addEventListener('click', (e) => {
                    e.stopPropagation();
                    notifPopup.classList.toggle('show');
                });
                window.addEventListener('click', (e) => {
                    if (!notifContainer.contains(e.target)) {
                        notifPopup.classList.remove('show');
                    }
                });

                checarNotificacoes();
                setInterval(checarNotificacoes, 30000);
            }
        });
    </script>
</body>
</html>