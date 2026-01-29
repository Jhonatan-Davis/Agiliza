<?php
session_start();
require '../conexao.php'; // Sobe um nível para achar a conexão

// --- O "Porteiro" ---
if (!isset($_SESSION['usuario_id']) || $_SESSION['funcao'] != 'funcionario') {
    header("Location: ../login/login.php");
    exit();
}

$id_funcionario = $_SESSION['usuario_id'];
date_default_timezone_set('America/Sao_Paulo');

// Tradutor de dias
$dias_traduzidos = [0 => 'Domingo', 1 => 'Segunda-feira', 2 => 'Terça-feira', 3 => 'Quarta-feira', 4 => 'Quinta-feira', 5 => 'Sexta-feira', 6 => 'Sábado'];

// --- [NOVO] Lógica de Paginação de Semana ---
// 1. Pega a data da URL (ou usa 'hoje' se não houver)
$data_base = new DateTime($_GET['semana'] ?? 'today');
$dia_semana_base = $data_base->format('w'); // 0=Domingo, 1=Segunda...

// 2. Calcula o início e o fim da semana selecionada
$data_inicio_semana = (clone $data_base)->modify('-' . $dia_semana_base . ' days');
$data_fim_semana = (clone $data_inicio_semana)->modify('+6 days');

// 3. Calcula os links para os botões "<" e ">"
$link_semana_anterior = 'agenda_completa.php?semana=' . (clone $data_inicio_semana)->modify('-7 days')->format('Y-m-d');
$link_semana_proxima = 'agenda_completa.php?semana=' . (clone $data_inicio_semana)->modify('+7 days')->format('Y-m-d');
// ---------------------------------------------

try {
    // [NOVO] A consulta SQL agora usa as datas dinâmicas
    $agendamentos = [];
    $stmt_ag = $pdo->prepare(
        "SELECT a.*, c.nome AS cliente_nome, s.nome_servico 
         FROM agendamentos a
         JOIN usuarios c ON a.id_cliente = c.id
         JOIN servicos s ON a.id_servico = s.id
         WHERE a.id_funcionario = :id_funcionario_logado 
           AND a.data_hora_inicio BETWEEN :inicio AND :fim
           AND a.status = 'confirmado' 
         ORDER BY a.data_hora_inicio ASC" // Ordena por hora
    );
    $stmt_ag->execute([
        'id_funcionario_logado' => $id_funcionario,
        'inicio' => $data_inicio_semana->format('Y-m-d 00:00:00'),
        'fim' => $data_fim_semana->format('Y-m-d 23:59:59')
    ]);
    $agendamentos = $stmt_ag->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erro ao buscar dados do calendário: ". $e->getMessage());
}

$dia_atual_loop = ""; // Variável para agrupar os dias
$data_hoje_obj = new DateTime(); // Para checar se o agendamento já passou
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agenda Completa - Agiliza</title>
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
                <a href="index.php">Dashboard</a>
                <a href="agenda_completa.php" class="active">Agenda Completa</a>
                <a href="meu_perfil.php">Meu Perfil</a>
                <a href="meu_portfolio.php">Meu Portfólio</a>
                <a href="meus_bloqueios.php">Meus Bloqueios</a>
            </div>
            <div class="sidebar-logout">
                <a href="../logout.php">Sair</a>
            </div>
        </nav>

        <main class="main-content">
            
            <div class="agenda-paginacao">
                <div class="agenda-paginacao-titulo">
                    Semana de <?php echo $data_inicio_semana->format('d/m'); ?> à <?php echo $data_fim_semana->format('d/m'); ?>
                </div>
                <div class="agenda-paginacao-nav">
                    <a href="<?php echo $link_semana_anterior; ?>">&larr; Semana Anterior</a>
                    <a href="<?php echo $link_semana_proxima; ?>">Próxima Semana &rarr;</a>
                </div>
            </div>

            <div class="agenda-lista">
                
                <?php if (count($agendamentos) > 0): ?>
                    
                    <?php foreach ($agendamentos as $ag): ?>
                        
                        <?php
                            $data_ag = new DateTime($ag['data_hora_inicio']);
                            $dia_ag_formatado = $data_ag->format('Y-m-d');
                            $passado_class = ($data_ag < $data_hoje_obj) ? 'passado' : '';
                        ?>

                        <?php
                        // Se o dia mudou, imprime um novo cabeçalho de dia
                        if ($dia_ag_formatado != $dia_atual_loop):
                            $dia_atual_loop = $dia_ag_formatado; // Atualiza o "dia atual"
                            $dia_semana_num = $data_ag->format('w');
                        ?>
                            <h2 class="agenda-dia-header">
                                <?php echo $dias_traduzidos[$dia_semana_num]; ?>
                                <span><?php echo $data_ag->format('d/m/Y'); ?></span>
                            </h2>
                        <?php endif; ?>

                        <div class="agenda-item <?php echo $passado_class; ?>">
                            <div class="agenda-item-hora">
                                <?php echo $data_ag->format('H:i'); ?>
                            </div>
                            <div class="agenda-item-info">
                                <strong><?php echo htmlspecialchars($ag['cliente_nome']); ?></strong>
                                <span><?php echo htmlspecialchars($ag['nome_servico']); ?></span>
                            </div>
                            <div class="agenda-item-acao">
                                <a href="../chat.php?id=<?php echo $ag['id']; ?>" title="Abrir Chat">
                                    <i class="fas fa-comment"></i>
                                </a>
                            </div>
                        </div>

                    <?php endforeach; ?>

                <?php else: ?>
                    <div class="empty-message-card" style="min-height: 400px;">
                        <i class="fas fa-calendar-check"></i>
                        <span>Nenhum agendamento confirmado para esta semana.</span>
                    </div>
                <?php endif; ?>

            </div>
            
        </main>
    </div>
    <?php require '../footer.php'; ?>
</body>
</html>