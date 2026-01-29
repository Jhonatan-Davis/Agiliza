<?php
session_start();
require '../conexao.php';

// --- Porteiro ---
if (!isset($_SESSION['usuario_id']) || $_SESSION['funcao'] != 'funcionario') {
    header("Location: ../login/login.php");
    exit();
}

$id_funcionario = $_SESSION['usuario_id'];
date_default_timezone_set('America/Sao_Paulo');

// --- Busca os bloqueios futuros deste funcionário ---
try {
    $stmt = $pdo->prepare(
        "SELECT * FROM horarios_bloqueados 
         WHERE id_funcionario = :id_funcionario 
         AND data_hora_fim >= NOW() -- Só mostra bloqueios que ainda não acabaram
         ORDER BY data_hora_inicio ASC"
    );
    $stmt->execute(['id_funcionario' => $id_funcionario]);
    $bloqueios = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erro ao buscar bloqueios: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Bloqueios - Agiliza</title>
    <link rel="stylesheet" href="../painel_dono/style_painel.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/pt.js"></script> </head>
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
                <a href="agenda_completa.php">Agenda Completa</a>
                <a href="meu_perfil.php">Meu Perfil</a>
                <a href="meu_portfolio.php">Meu Portfólio</a>
                <a href="meus_bloqueios.php" class="active">Meus Bloqueios</a>
            </div>
            <div class="sidebar-logout">
                <a href="../logout.php">Sair</a>
            </div>
        </nav>

        <main class="main-content">
            <h2>Meus Bloqueios</h2>
            <p>Adicione períodos em que você não estará disponível (almoço, médico, férias, etc.). Os clientes não poderão agendar nestes horários.</p>

            <?php
                if (isset($_GET['sucesso'])) {
                    echo '<div class="error-message auto-dismiss-message" style="background-color: #4CAF50;">Ação realizada com sucesso!</div>';
                }
                if (isset($_GET['erro'])) {
                    echo '<div class="error-message auto-dismiss-message" style="background-color: #ff3333;">'.htmlspecialchars($_GET['erro']).'</div>';
                }
            ?>

            <div class="form-card" style="margin-bottom: 2rem;">
                <h3>Adicionar Novo Bloqueio</h3>
                
                <form action="processar_bloqueio.php" method="POST">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="form-group">
                        <label for="motivo">Motivo (Ex: Almoço, Dentista, Férias)</label>
                        <input type="text" id="motivo" name="motivo" class="form-group-input-select" placeholder="Ex: Almoço" required>
                    </div>
                    
                    <div class="horario-inputs" style="grid-template-columns: 1fr 1fr 1fr;">
                        <div class="form-group">
                            <label for="data_bloqueio">Data</label>
                            <input type="text" id="data_bloqueio" name="data_bloqueio" class="datepicker" placeholder="Selecione o dia" required>
                        </div>
                        <div class="form-group">
                            <label for="hora_inicio">Hora de Início</label>
                            <input type="text" id="hora_inicio" name="hora_inicio" class="timepicker" placeholder="HH:MM" required>
                        </div>
                        <div class="form-group">
                            <label for="hora_fim">Hora de Fim</label>
                            <input type="text" id="hora_fim" name="hora_fim" class="timepicker" placeholder="HH:MM" required>
                        </div>
                    </div>

                    <button type="submit" class="form-button" style="margin-top: 1rem;">Salvar Bloqueio</button>
                </form>
            </div>

            <h3>Meus Próximos Bloqueios</h3>
            <table class="tabela-agenda">
                <thead>
                    <tr>
                        <th>Motivo</th>
                        <th>Data</th>
                        <th>Início</th>
                        <th>Fim</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($bloqueios) > 0): ?>
                        <?php foreach ($bloqueios as $bloqueio): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($bloqueio['motivo']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($bloqueio['data_hora_inicio'])); ?></td>
                                <td><?php echo date('H:i', strtotime($bloqueio['data_hora_inicio'])); ?></td>
                                <td><?php echo date('H:i', strtotime($bloqueio['data_hora_fim'])); ?></td>
                                <td class="actions-cell">
                                    <a href="processar_bloqueio.php?action=delete&id=<?php echo $bloqueio['id']; ?>" 
                                       class="icon-delete" title="Excluir Bloqueio"
                                       onclick="return confirm('Tem certeza que deseja excluir este bloqueio?');">
                                       <i class="fas fa-trash-alt"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: #888;">Nenhum bloqueio futuro cadastrado.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </main>
    </div>

<script>
    // 1. Script da Mensagem de Sucesso que desaparece
    document.addEventListener('DOMContentLoaded', (event) => {
        const alertMessage = document.querySelector('.auto-dismiss-message');
        if (alertMessage) {
            setTimeout(() => {
                alertMessage.classList.add('fade-out');
            }, 3000);
        }
    });

    // 2. Ativa o Relógio Bonito (flatpickr)
    flatpickr(".timepicker", {
        enableTime: true,
        noCalendar: true,
        dateFormat: "H:i",
        time_24hr: true,
        minuteIncrement: 15 // Pulos de 15 min
    });

    // 3. Ativa o Calendário Bonito (flatpickr)
    flatpickr(".datepicker", {
        dateFormat: "Y-m-d", // Formato do banco (AAAA-MM-DD)
        minDate: "today", // Não pode bloquear no passado
        "locale": "pt" // Traduz
    });
</script>
<?php require '../footer.php'; ?>
</body>
</html>