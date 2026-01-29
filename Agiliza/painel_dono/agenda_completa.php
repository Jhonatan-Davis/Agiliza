<?php
session_start();
require '../conexao.php'; // Sobe um nível para achar a conexão

// --- O "Porteiro" ---
if (!isset($_SESSION['usuario_id']) || $_SESSION['funcao'] != 'dono') {
    header("Location: ../login/login.php");
    exit();
}

$id_negocio = $_SESSION['id_negocio'];
date_default_timezone_set('America/Sao_Paulo');
$id_dono = $_SESSION['usuario_id'];

// --- [CORREÇÃO] Verifica se o Dono também é Funcionário ---
$stmt_check_func = $pdo->prepare(
    "SELECT COUNT(*) FROM negocio_membros 
     WHERE id_usuario = :id_dono AND id_negocio = :id_negocio AND funcao = 'funcionario'"
);
$stmt_check_func->execute(['id_dono' => $id_dono, 'id_negocio' => $id_negocio]);
$dono_e_funcionario = $stmt_check_func->fetchColumn() > 0;

// Tradutor de dias
$dias_traduzidos = [0 => 'Domingo', 1 => 'Segunda-feira', 2 => 'Terça-feira', 3 => 'Quarta-feira', 4 => 'Quinta-feira', 5 => 'Sexta-feira', 6 => 'Sábado'];

// --- [NOVO] Lógica de Paginação de Semana ---
$data_base = new DateTime($_GET['semana'] ?? 'today');
$dia_semana_base = $data_base->format('w');
$data_inicio_semana = (clone $data_base)->modify('-' . $dia_semana_base . ' days');
$data_fim_semana = (clone $data_inicio_semana)->modify('+6 days');
$link_semana_anterior = 'agenda_completa.php?semana=' . (clone $data_inicio_semana)->modify('-7 days')->format('Y-m-d');
$link_semana_proxima = 'agenda_completa.php?semana=' . (clone $data_inicio_semana)->modify('+7 days')->format('Y-m-d');
// ---------------------------------------------

try {
    // [CORREÇÃO] A consulta SQL agora busca TODOS os agendamentos do NEGÓCIO
    // E inclui o nome do funcionário (f.nome)
    $stmt_ag = $pdo->prepare(
        "SELECT 
            a.id, a.data_hora_inicio, 
            c.nome AS cliente_nome, 
            f.nome AS funcionario_nome, 
            s.nome_servico 
         FROM agendamentos a
         JOIN usuarios c ON a.id_cliente = c.id
         JOIN usuarios f ON a.id_funcionario = f.id
         JOIN servicos s ON a.id_servico = s.id
         WHERE a.id_negocio = :id_negocio -- A MUDANÇA (pega do negócio todo)
           AND a.data_hora_inicio BETWEEN :inicio AND :fim
           AND a.status = 'confirmado' 
         ORDER BY a.data_hora_inicio ASC" // Ordena por hora
    );
    $stmt_ag->execute([
        'id_negocio' => $id_negocio, // Em vez de $id_funcionario
        'inicio' => $data_inicio_semana->format('Y-m-d 00:00:00'),
        'fim' => $data_fim_semana->format('Y-m-d 23:59:59')
    ]);
    $agendamentos = $stmt_ag->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erro ao buscar dados do calendário: ". $e->getMessage());
}

$dia_atual_loop = ""; // Variável para agrupar os dias
$data_hoje_obj = new DateTime(); 
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agenda Completa - Agiliza</title>
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
                <a href="agenda_completa.php" class="active">Agenda Completa</a>
                <a href="meu_negocio.php">Meu Negócio</a>
                <a href="minha_equipe.php">Minha Equipe</a>
                <a href="meus_servicos.php">Meus Serviços</a>
                <a href="meus_horarios.php">Meus Horários</a>
                <a href="historico.php">Histórico e Vendas</a>
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
                            $dia_atual_loop = $dia_ag_formatado;
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
                                <span>
                                    <?php echo htmlspecialchars($ag['nome_servico']); ?> com 
                                    <strong><?php echo htmlspecialchars(explode(' ', $ag['funcionario_nome'])[0]); ?></strong>
                                </span>
                            </div>
                            <div class="agenda-item-acao">
                                <span style="color: #ccc; cursor: not-allowed;" title="Chat privado">
                                    <i class="fas fa-comment-slash"></i>
                                </span>
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
    <script src="script_painel.js"></script>
    <?php require '../footer.php'; ?>
    
</body>
</html>