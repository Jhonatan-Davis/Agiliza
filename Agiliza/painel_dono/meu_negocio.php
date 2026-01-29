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

// ============================================================
// [A CORREÇÃO ESTÁ AQUI]
// Este bloco estava faltando. Ele verifica se o botão deve aparecer.
// ============================================================
$stmt_check_func = $pdo->prepare(
    "SELECT COUNT(*) FROM negocio_membros 
     WHERE id_usuario = :id_dono AND id_negocio = :id_negocio AND funcao = 'funcionario'"
);
$stmt_check_func->execute(['id_dono' => $id_dono, 'id_negocio' => $id_negocio]);
$dono_e_funcionario = $stmt_check_func->fetchColumn() > 0;
// ============================================================

// --- Busca os dados ATUAIS do negócio ---
try {
    $stmt = $pdo->prepare("SELECT * FROM negocios WHERE id = :id_negocio");
    $stmt->execute(['id_negocio' => $id_negocio]);
    $negocio = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao buscar negócio: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Negócio - Agiliza</title>
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
        <h1>Agili<span>za</span></h1>
        <nav class="sidebar">
            
            <div class="sidebar-nav">
                <a href="index.php">Dashboard</a> 
                <a href="agenda_completa.php">Agenda Completa</a>
                <a href="meu_negocio.php" class="active">Meu Negócio</a> <a href="minha_equipe.php">Minha Equipe</a>
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
            <h2>Meu Negócio</h2>
            <p>Edite as informações públicas do seu estabelecimento.</p>
            
            <?php
                if (isset($_SESSION['erro_negocio'])) {
                    echo '<div class="error-message auto-dismiss-message" style="background-color: #ff3333; color: white; padding: 0.75rem; border-radius: 6px; margin-bottom: 1rem;">'.$_SESSION['erro_negocio'].'</div>';
                    unset($_SESSION['erro_negocio']);
                }
                if (isset($_SESSION['sucesso_negocio'])) {
                    echo '<div class="error-message auto-dismiss-message" style="background-color: #4CAF50; color: white; padding: 0.75rem; border-radius: 6px; margin-bottom: 1rem;">'.$_SESSION['sucesso_negocio'].'</div>';
                    unset($_SESSION['sucesso_negocio']);
                }
            ?>

            <div class="form-card">
                <form action="processar_meu_negocio.php" method="POST" enctype="multipart/form-data">
                    
                    <div class="form-group">
                        <label for="nome_negocio">Nome do Negócio</label>
                        <input type="text" id="nome_negocio" name="nome_negocio" 
                               value="<?php echo htmlspecialchars($negocio['nome_negocio']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Foto de Perfil do Comércio (Quadrada)</label>
                        <div class="form-group-file">
                            <input type="file" id="foto_perfil" name="foto_perfil" class="file-input-hidden" accept="image/*" 
                                   onchange="updateFilename(this.id, 'filename-perfil')">
                            <label for="foto_perfil" class="file-input-label">Escolher arquivo</label>
                            <span class="file-input-filename" id="filename-perfil">Nenhum arquivo escolhido</span>
                        </div>
                        <?php if (!empty($negocio['foto_perfil_url'])): ?>
                            <div class="preview-container">
                                <img src="../<?php echo htmlspecialchars($negocio['foto_perfil_url']); ?>" alt="Foto de Perfil Atual" class="preview-perfil">
                                <small>Deixe em branco para manter a atual.</small>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>Imagem de Capa (Plano de Fundo)</label>
                        <div class="form-group-file">
                            <input type="file" id="capa" name="capa" class="file-input-hidden" accept="image/*" 
                                   onchange="updateFilename(this.id, 'filename-capa')">
                            <label for="capa" class="file-input-label">Escolher arquivo</label>
                            <span class="file-input-filename" id="filename-capa">Nenhum arquivo escolhido</span>
                        </div>
                        <?php if (!empty($negocio['capa_url'])): ?>
                            <div class="preview-container">
                                <img src="../<?php echo htmlspecialchars($negocio['capa_url']); ?>" alt="Capa Atual">
                                <small>Deixe em branco para manter a atual.</small>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="endereco_texto">Endereço (para o mapa)</label>
                        <input type="text" id="endereco_texto" name="endereco_texto" 
                               value="<?php echo htmlspecialchars($negocio['endereco_texto']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="ponto_referencia">Ponto de Referência (Opcional)</label>
                        <input type="text" id="ponto_referencia" name="ponto_referencia" 
                               value="<?php echo htmlspecialchars($negocio['ponto_referencia']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="link_maps"><i class="fas fa-map-marker-alt" style="color: #EA4335;"></i> Link Exato do Google Maps</label>
                        <input type="text" id="link_maps" name="link_maps" placeholder="Cole aqui o link de 'Compartilhar' do Google Maps"
                               value="<?php echo htmlspecialchars($negocio['link_maps'] ?? ''); ?>">
                        <small style="color: #777; font-size: 0.8rem;">Vá no Google Maps, ache sua loja, clique em "Compartilhar" e cole o link aqui.</small>
                    </div>

                    <h3 style="margin-top: 2rem; margin-bottom: 1rem; font-size: 1.2rem; color: #7E57C2;">Redes Sociais e Contato</h3>

                    <div class="form-group">
                        <label for="whatsapp"><i class="fab fa-whatsapp" style="color: #25D366;"></i> WhatsApp (Somente números)</label>
                        <input type="text" id="whatsapp" name="whatsapp" placeholder="Ex: 64999998888"
                               value="<?php echo htmlspecialchars($negocio['whatsapp'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="instagram"><i class="fab fa-instagram" style="color: #E1306C;"></i> Link do Instagram</label>
                        <input type="text" id="instagram" name="instagram" placeholder="https://instagram.com/sua_barbearia"
                               value="<?php echo htmlspecialchars($negocio['instagram'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="facebook"><i class="fab fa-facebook" style="color: #1877F2;"></i> Link do Facebook</label>
                        <input type="text" id="facebook" name="facebook" placeholder="https://facebook.com/sua_pagina"
                               value="<?php echo htmlspecialchars($negocio['facebook'] ?? ''); ?>">
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="form-button">Salvar Alterações</button>
                        <a href="../perfil_negocio.php?id=<?php echo $_SESSION['id_negocio']; ?>" target="_blank" class="preview-button">
                           Ver Pré-visualização
                        </a>
                    </div>

                </form>
            </div>
        </main>
    </div>

    <script>
        function updateFilename(inputId, spanId) {
            const input = document.getElementById(inputId);
            const span = document.getElementById(spanId);
            if (input.files && input.files.length > 0) {
                span.textContent = input.files[0].name;
            } else {
                span.textContent = 'Nenhum arquivo escolhido';
            }
        }
        document.addEventListener('DOMContentLoaded', (event) => {
            const alertMessage = document.querySelector('.auto-dismiss-message');
            if (alertMessage) {
                setTimeout(() => { alertMessage.classList.add('fade-out'); }, 3000);
            }
        });
    </script>
    <script src="script_painel.js"></script>
</body>
</html>