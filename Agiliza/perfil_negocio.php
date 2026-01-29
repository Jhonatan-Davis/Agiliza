<?php
session_start();
require 'conexao.php';
date_default_timezone_set('America/Sao_Paulo');

$id_negocio_da_pagina = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_negocio_da_pagina == 0) {
    die("ID do negócio não fornecido.");
}

// --- Lógica de Permissão (Dono/Funcionário vs Cliente) ---
$id_usuario_logado = $_SESSION['usuario_id'] ?? null;
$funcao_usuario_logado = $_SESSION['funcao'] ?? null;
$id_negocio_usuario_logado = $_SESSION['id_negocio'] ?? null;

$e_proprietario = false;
if ($id_usuario_logado) {
    if (($funcao_usuario_logado == 'dono' || $funcao_usuario_logado == 'funcionario') && $id_negocio_usuario_logado == $id_negocio_da_pagina) {
        $e_proprietario = true;
    }
}

try {
    // 1. Buscar dados do negócio
    $stmt_negocio = $pdo->prepare("SELECT * FROM negocios WHERE id = :id_negocio");
    $stmt_negocio->execute(['id_negocio' => $id_negocio_da_pagina]);
    $negocio = $stmt_negocio->fetch(PDO::FETCH_ASSOC);

    if (!$negocio) {
        die("Negócio não encontrado.");
    }

    // 2. Buscar Serviços
    $stmt_servicos = $pdo->prepare("SELECT id, nome_servico, preco, duracao_minutos FROM servicos WHERE id_negocio = :id_negocio AND ativo = 1 ORDER BY nome_servico ASC");
    $stmt_servicos->execute(['id_negocio' => $id_negocio_da_pagina]);
    $servicos = $stmt_servicos->fetchAll(PDO::FETCH_ASSOC);

    // 3. Buscar Equipe
    $stmt_equipe = $pdo->prepare("SELECT u.id, u.nome, u.foto_perfil_url FROM usuarios u JOIN negocio_membros nm ON u.id = nm.id_usuario WHERE nm.id_negocio = :id_negocio AND nm.funcao = 'funcionario' ORDER BY u.nome ASC");
    $stmt_equipe->execute(['id_negocio' => $id_negocio_da_pagina]);
    $equipe = $stmt_equipe->fetchAll(PDO::FETCH_ASSOC);

    // 4. Buscar Portfólio
    $stmt_portfolio = $pdo->prepare("SELECT id, url_imagem, legenda FROM portfolio_fotos WHERE id_negocio = :id_negocio ORDER BY id DESC");
    $stmt_portfolio->execute(['id_negocio' => $id_negocio_da_pagina]);
    $portfolio = $stmt_portfolio->fetchAll(PDO::FETCH_ASSOC);

    // 5. Buscar Média de Avaliações
    $stmt_media = $pdo->prepare("SELECT AVG(nota) as media, COUNT(id) as total FROM avaliacoes WHERE id_negocio = :id");
    $stmt_media->execute(['id' => $id_negocio_da_pagina]);
    $stats = $stmt_media->fetch(PDO::FETCH_ASSOC);
    $nota_media = $stats['media'] ? round($stats['media'], 1) : 0;
    $total_avaliacoes = $stats['total'];

    // 6. Buscar as Últimas Avaliações (Comentários)
    $stmt_reviews = $pdo->prepare(
        "SELECT a.nota, a.comentario, a.data_avaliacao, u.nome 
         FROM avaliacoes a
         JOIN usuarios u ON a.id_cliente = u.id
         WHERE a.id_negocio = :id 
         ORDER BY a.data_avaliacao DESC LIMIT 5"
    );
    $stmt_reviews->execute(['id' => $id_negocio_da_pagina]);
    $reviews = $stmt_reviews->fetchAll(PDO::FETCH_ASSOC);
    
    // 7. Dados do Usuário Logado e Favorito
    $favoritado = false;
    $usuario = null;
    if ($id_usuario_logado) {
        $stmt_user = $pdo->prepare("SELECT nome, foto_perfil_url FROM usuarios WHERE id = :id");
        $stmt_user->execute(['id' => $id_usuario_logado]);
        $usuario = $stmt_user->fetch(PDO::FETCH_ASSOC);
        $_SESSION['usuario_nome'] = $usuario['nome'];
        $_SESSION['usuario_foto'] = $usuario['foto_perfil_url'];

        if (!$e_proprietario) {
            $stmt_favorito = $pdo->prepare("SELECT COUNT(*) FROM favoritos WHERE id_cliente = :id_cliente AND id_negocio = :id_negocio");
            $stmt_favorito->execute(['id_cliente' => $id_usuario_logado, 'id_negocio' => $id_negocio_da_pagina]);
            $favoritado = $stmt_favorito->fetchColumn() > 0;
        }
    }

} catch (PDOException $e) {
    die("Erro: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($negocio['nome_negocio']); ?> - Agiliza</title>
    <link rel="stylesheet" href="style_site.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="shortcut icon" href="uploads/logo_agiliza.png" type="image/png">
</head>
<body class="<?php if ($e_proprietario) echo 'in-preview-mode'; ?>">

    <header class="site-header">
        <a href="index.php" class="logo">Agili<span>za</span></a>
        <nav>
            <?php if (isset($_SESSION['usuario_id']) && $usuario): ?>
                <div class="user-dropdown">
                    <button class="dropdown-btn">
                        <?php if (!empty($usuario['foto_perfil_url'])): ?>
                            <img src="<?php echo htmlspecialchars($usuario['foto_perfil_url']); ?>?v=<?php echo time(); ?>" alt="Perfil" class="header-perfil-foto">
                        <?php endif; ?>
                        Olá, <?php echo htmlspecialchars($usuario['nome']); ?>!
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="dropdown-content">
                        <?php if ($e_proprietario): ?>
                            <?php if ($_SESSION['funcao'] == 'dono'): ?>
                                <a href="painel_dono/index.php">Ir para meu Painel</a>
                            <?php else: ?>
                                <a href="painel_funcionario/index.php">Ir para meu Painel</a>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="meu_perfil.php">Meu Perfil</a>
                            <a href="meus_agendamentos.php">Meus Agendamentos</a>
                        <?php endif; ?>
                        <div class="dropdown-divider"></div>
                        <a href="logout.php" class="logout-link">Sair</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="login/login.php">Fazer Login</a>
                <a href="cadastro/selecao.php" class="form-button" style="padding: 0.5rem 1rem; margin-left: 1rem;">Cadastre-se</a>
            <?php endif; ?>
        </nav>
    </header>

    <?php if ($e_proprietario): ?>
        <div class="preview-banner">
            <i class="fas fa-eye"></i> Esta é uma pré-visualização de como os clientes veem seu perfil.
        </div>
    <?php endif; ?>

    <main class="site-container">
        
        <div class="perfil-header-card">
            
            <div class="perfil-action-icons">
                <?php if (isset($_SESSION['usuario_id']) && !$e_proprietario): ?>
                    <button id="favorite-btn" class="icon-btn <?php echo $favoritado ? 'favorited' : ''; ?>" data-id-negocio="<?php echo $id_negocio_da_pagina; ?>">
                        <i class="<?php echo $favoritado ? 'fas' : 'far'; ?> fa-heart"></i>
                    </button>
                <?php endif; ?>
                
                <?php if (!empty($negocio['whatsapp'])): 
                    $wpp_num = preg_replace('/[^0-9]/', '', $negocio['whatsapp']);
                    $wpp_link = "https://wa.me/55" . $wpp_num;
                ?>
                    <a href="<?php echo $wpp_link; ?>" target="_blank" class="icon-link whatsapp-icon"><i class="fab fa-whatsapp"></i></a>
                <?php endif; ?>

                <?php if (!empty($negocio['instagram'])): ?>
                    <a href="<?php echo htmlspecialchars($negocio['instagram']); ?>" target="_blank" class="icon-link instagram-icon"><i class="fab fa-instagram"></i></a>
                <?php endif; ?>

                <?php if (!empty($negocio['facebook'])): ?>
                    <a href="<?php echo htmlspecialchars($negocio['facebook']); ?>" target="_blank" class="icon-link facebook-icon"><i class="fab fa-facebook-f"></i></a>
                <?php endif; ?>
                
                <?php 
                    if (!empty($negocio['link_maps'])) {
                        $google_maps_url = $negocio['link_maps'];
                    } else {
                        $endereco_maps = urlencode($negocio['endereco_texto']);
                        $google_maps_url = "https://www.google.com/maps/search/?api=1&query=" . $endereco_maps;
                    }
                ?>
                <a href="<?php echo $google_maps_url; ?>" target="_blank" class="icon-link" title="Ver no Google Maps">
                    <i class="fas fa-map-marker-alt"></i>
                </a>
            </div>

            <div class="perfil-capa" <?php if (!empty($negocio['capa_url'])): ?> style="background-image: url('<?php echo htmlspecialchars($negocio['capa_url']); ?>');" <?php endif; ?>>
            </div>
            
            <div class="perfil-info-bar">
                <div class="perfil-foto-wrapper">
                    <img src="<?php echo !empty($negocio['foto_perfil_url']) ? htmlspecialchars($negocio['foto_perfil_url']) : 'uploads/default_negocio.png'; ?>" alt="Logo do Negócio">
                </div>
                <div class="perfil-details">
                    <h1><?php echo htmlspecialchars($negocio['nome_negocio']); ?></h1>
                    <p><?php echo htmlspecialchars($negocio['endereco_texto']); ?></p>
                    
                    <div class="rating" style="color: #FFC107; margin-top: 0.5rem;">
                        <?php 
                            for($i=1; $i<=5; $i++) {
                                if ($i <= $nota_media) echo '<i class="fas fa-star"></i>';
                                elseif ($i - 0.5 <= $nota_media) echo '<i class="fas fa-star-half-alt"></i>';
                                else echo '<i class="far fa-star"></i>';
                            }
                        ?>
                        <span style="color: #666; font-size: 0.9rem;">(<?php echo $nota_media; ?> • <?php echo $total_avaliacoes; ?> avaliações)</span>
                    </div>
                </div>
            </div>
        </div>

        <section class="perfil-section">
            <div class="perfil-section-header">
                <h2>Serviços</h2>
                <?php if (!$e_proprietario): ?>
                    <a href="agendamento.php?id_negocio=<?php echo $id_negocio_da_pagina; ?>" class="form-button">Agendar Agora</a>
                <?php endif; ?>
            </div>
            <div class="perfil-section-body">
                <?php if (count($servicos) > 0): ?>
                    <?php foreach ($servicos as $servico): ?>
                        <div class="servico-item-card">
                            <div class="servico-info">
                                <h3><?php echo htmlspecialchars($servico['nome_servico']); ?></h3>
                                <span><?php echo htmlspecialchars($servico['duracao_minutos']); ?> minutos</span>
                            </div>
                            <span class="servico-preco">R$ <?php echo number_format($servico['preco'], 2, ',', '.'); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Nenhum serviço disponível no momento.</p>
                <?php endif; ?>
            </div>
        </section>

        <section class="perfil-section">
            <div class="perfil-section-header">
                <h2>Nossa Equipe</h2>
            </div>
            <div class="perfil-section-body">
                <?php if (count($equipe) > 0): ?>
                    <div class="equipe-grid">
                        <?php foreach ($equipe as $membro): ?>
                            <div class="equipe-membro">
                                <img src="<?php echo !empty($membro['foto_perfil_url']) ? htmlspecialchars($membro['foto_perfil_url']) : 'uploads/default_perfil.png'; ?>" alt="<?php echo htmlspecialchars($membro['nome']); ?>">
                                <strong><?php echo htmlspecialchars(explode(' ', $membro['nome'])[0]); ?></strong>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>Nenhum profissional cadastrado.</p>
                <?php endif; ?>
            </div>
        </section>

        <section class="perfil-section">
            <div class="perfil-section-header">
                <h2>Portfólio</h2>
            </div>
            <div class="perfil-section-body">
                <?php if (count($portfolio) > 0): ?>
                    <div class="portfolio-gallery">
                        <?php foreach ($portfolio as $item): ?>
                            <div class="portfolio-item">
                                <img src="<?php echo htmlspecialchars($item['url_imagem']); ?>?v=<?php echo time(); ?>" 
                                     alt="<?php echo htmlspecialchars($item['legenda']); ?>"
                                     class="portfolio-item-img"
                                     data-src="<?php echo htmlspecialchars($item['url_imagem']); ?>?v=<?php echo time(); ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>Nenhuma foto no portfólio.</p>
                <?php endif; ?>
            </div>
        </section>

        <section class="perfil-section">
            <div class="perfil-section-header">
                <h2>Avaliações Recentes</h2>
            </div>
            <div class="perfil-section-body">
                <?php if (count($reviews) > 0): ?>
                    <div class="reviews-list">
                        <?php foreach ($reviews as $rev): ?>
                            <div class="review-item" style="border-bottom: 1px solid #eee; padding: 1rem 0;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <strong><?php echo htmlspecialchars($rev['nome']); ?></strong>
                                    <span style="color: #FFC107; font-size: 0.9rem;">
                                        <?php for($i=0; $i<$rev['nota']; $i++) echo '<i class="fas fa-star"></i>'; ?>
                                    </span>
                                </div>
                                <?php if (!empty($rev['comentario'])): ?>
                                    <p style="margin: 0.5rem 0; color: #555; font-style: italic;">"<?php echo htmlspecialchars($rev['comentario']); ?>"</p>
                                <?php endif; ?>
                                <small style="color: #999;"><?php echo date('d/m/Y', strtotime($rev['data_avaliacao'])); ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="color: #777; font-style: italic;">Este negócio ainda não tem avaliações.</p>
                <?php endif; ?>
            </div>
        </section>
        
    </main>

    <?php require 'footer.php'; ?>

    <div id="portfolio-lightbox" class="lightbox">
        <span class="close-lightbox">&times;</span>
        <img class="lightbox-content" id="lightbox-img">
    </div>

    <script>
        // Dropdown
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

        // Botão Favoritar
        const favoriteBtn = document.getElementById('favorite-btn');
        if (favoriteBtn) {
            favoriteBtn.addEventListener('click', async () => {
                const idNegocio = favoriteBtn.dataset.idNegocio;
                const isFavorited = favoriteBtn.classList.contains('favorited');
                try {
                    const response = await fetch('favoritar_negocio.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `id_negocio=${idNegocio}&action=${isFavorited ? 'remover' : 'adicionar'}`
                    });
                    const data = await response.json();
                    if (data.sucesso) {
                        favoriteBtn.classList.toggle('favorited');
                        const icon = favoriteBtn.querySelector('i');
                        if (favoriteBtn.classList.contains('favorited')) {
                            icon.classList.remove('far'); icon.classList.add('fas');
                        } else {
                            icon.classList.remove('fas'); icon.classList.add('far');
                        }
                    } else {
                        if (data.erro === 'Usuário não logado.') { window.location.href = 'login/login.php'; }
                    }
                } catch (error) { console.error('Erro na requisição:', error); }
            });
        }

        // [NOVO] Script do Lightbox
        const lightbox = document.getElementById('portfolio-lightbox');
        const lightboxImg = document.getElementById('lightbox-img');
        const closeBtn = document.querySelector('.close-lightbox');
        const portfolioImages = document.querySelectorAll('.portfolio-item-img');

        if (lightbox && portfolioImages.length > 0) {
            portfolioImages.forEach(img => {
                img.addEventListener('click', function() {
                    lightbox.classList.add('show');
                    lightboxImg.src = this.dataset.src;
                });
            });

            closeBtn.addEventListener('click', function() {
                lightbox.classList.remove('show');
            });

            lightbox.addEventListener('click', function(e) {
                if (e.target === this) {
                    lightbox.classList.remove('show');
                }
            });
        }
    </script>
</body>
</html>