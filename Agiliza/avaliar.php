<?php
session_start();
require 'conexao.php';

// --- Porteiro ---
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login/login.php");
    exit();
}

$id_agendamento = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$id_cliente = $_SESSION['usuario_id'];

try {
    // 1. Busca dados do agendamento e verifica se pode avaliar
    $stmt = $pdo->prepare(
        "SELECT a.id, a.id_negocio, n.nome_negocio, s.nome_servico, f.nome as nome_profissional
         FROM agendamentos a
         JOIN negocios n ON a.id_negocio = n.id
         JOIN servicos s ON a.id_servico = s.id
         JOIN usuarios f ON a.id_funcionario = f.id
         WHERE a.id = :id_ag 
           AND a.id_cliente = :id_cli 
           AND a.status = 'concluido'"
    );
    $stmt->execute(['id_ag' => $id_agendamento, 'id_cli' => $id_cliente]);
    $agendamento = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$agendamento) {
        die("Agendamento não encontrado ou ainda não concluído.");
    }

    // 2. Verifica se JÁ avaliou
    $stmt_check = $pdo->prepare("SELECT id FROM avaliacoes WHERE id_agendamento = :id");
    $stmt_check->execute(['id' => $id_agendamento]);
    if ($stmt_check->rowCount() > 0) {
        die("Você já avaliou este serviço.");
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
    <title>Avaliar Serviço - Agiliza</title>
    <link rel="stylesheet" href="style_site.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <header class="site-header">
        <a href="index.php" class="logo">Agili<span>za</span></a>
        <nav><a href="meus_agendamentos.php">Voltar</a></nav>
    </header>

    <main class="site-container" style="max-width: 600px; text-align: center;">
        <div class="form-card" style="margin-top: 2rem;">
            <h2 style="margin-top: 0;">Como foi seu atendimento?</h2>
            <p>
                Você visitou <strong><?php echo htmlspecialchars($agendamento['nome_negocio']); ?></strong><br>
                Serviço: <?php echo htmlspecialchars($agendamento['nome_servico']); ?><br>
                Profissional: <?php echo htmlspecialchars($agendamento['nome_profissional']); ?>
            </p>

            <form action="processar_avaliacao.php" method="POST">
                <input type="hidden" name="id_agendamento" value="<?php echo $id_agendamento; ?>">
                <input type="hidden" name="id_negocio" value="<?php echo $agendamento['id_negocio']; ?>">

                <div class="rating-form">
                    <input type="radio" id="star5" name="nota" value="5" required><label for="star5" title="Excelente"><i class="fas fa-star"></i></label>
                    <input type="radio" id="star4" name="nota" value="4"><label for="star4" title="Muito Bom"><i class="fas fa-star"></i></label>
                    <input type="radio" id="star3" name="nota" value="3"><label for="star3" title="Bom"><i class="fas fa-star"></i></label>
                    <input type="radio" id="star2" name="nota" value="2"><label for="star2" title="Ruim"><i class="fas fa-star"></i></label>
                    <input type="radio" id="star1" name="nota" value="1"><label for="star1" title="Péssimo"><i class="fas fa-star"></i></label>
                </div>

                <div class="form-group">
                    <label for="comentario" style="text-align: left;">Deixe um comentário (Opcional):</label>
                    <textarea id="comentario" name="comentario" rows="4" class="search-input" placeholder="O que você achou do serviço?"></textarea>
                </div>

                <button type="submit" class="form-button" style="width: 100%;">Enviar Avaliação</button>
            </form>
        </div>
    </main>
</body>
</html>