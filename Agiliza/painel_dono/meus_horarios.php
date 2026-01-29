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
// --- [CORREÇÃO] Verifica se o Dono também é Funcionário ---
$stmt_check_func = $pdo->prepare(
    "SELECT COUNT(*) FROM negocio_membros 
     WHERE id_usuario = :id_dono AND id_negocio = :id_negocio AND funcao = 'funcionario'"
);
$stmt_check_func->execute(['id_dono' => $id_dono, 'id_negocio' => $id_negocio]);
$dono_e_funcionario = $stmt_check_func->fetchColumn() > 0;

// --- Busca os horários atuais para preencher o formulário ---
$horarios_db = [];
try {
    // Busca os horários de funcionamento
    $stmt = $pdo->prepare("SELECT * FROM horarios_funcionamento WHERE id_negocio = :id");
    $stmt->execute(['id' => $id_negocio]);
    $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($resultado as $dia) {
        $horarios_db[$dia['dia_semana']] = $dia;
    }
    
    // Busca os feriados locais
    $stmt_locais = $pdo->prepare("SELECT * FROM feriados_personalizados WHERE id_negocio = :id ORDER BY data ASC");
    $stmt_locais->execute(['id' => $id_negocio]);
    $feriados_locais = $stmt_locais->fetchAll(PDO::FETCH_ASSOC);

    // Busca a config de feriados auto
    $stmt_feriado_config = $pdo->prepare("SELECT bloquear_feriados_auto FROM negocios WHERE id = :id");
    $stmt_feriado_config->execute(['id' => $id_negocio]);
    $bloquear_feriados_auto = $stmt_feriado_config->fetchColumn();

} catch (PDOException $e) {
    die("Erro ao buscar dados: " . $e->getMessage());
}

$dias_da_semana = [1 => 'Segunda-feira', 2 => 'Terça-feira', 3 => 'Quarta-feira', 4 => 'Quinta-feira', 5 => 'Sexta-feira', 6 => 'Sábado', 0 => 'Domingo'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Meus Horários - Agiliza</title>
    <link rel="stylesheet" href="style_painel.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    
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
                <a href="agenda_completa.php" >Agenda Completa</a>
                <a href="meu_negocio.php" >Meu Negócio</a>
                <a href="minha_equipe.php">Minha Equipe</a>
                <a href="meus_servicos.php">Meus Serviços</a>
                <a href="meus_horarios.php" class="active">Meus Horários</a>
                <a href="historico.php" >Histórico e Vendas</a>
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
                
            </div>
            <div class="sidebar-logout">
                <a href="../logout.php">Sair</a>
            </div>
        </nav>

        <main class="main-content">
            <h2>Horários de Funcionamento</h2>
            <p>Configure seus horários de trabalho e feriados.</p>
            
            <?php
                // Mostra mensagens de SUCESSO ou ERRO
                if (isset($_GET['sucesso'])) {
                    echo '<div class="error-message auto-dismiss-message" style="background-color: #4CAF50; color: white; padding: 0.75rem; border-radius: 6px; margin-bottom: 1rem;">Horários salvos com sucesso!</div>';
                }
            ?>

            <div class="form-card assistente-rapido-card">
                <h3>Assistente Rápido</h3>
                <p>Use isto para aplicar o mesmo horário para vários dias de uma vez.</p>
                
                <div class="horario-inputs">
                    <div>
                        <label>Abre Manhã</label>
                        <input type="text" class="timepicker" id="helper_abertura_manha" placeholder="HH:MM">
                    </div>
                    <div>
                        <label>Fecha (Almoço)</label>
                        <input type="text" class="timepicker" id="helper_fechamento_manha" placeholder="HH:MM">
                    </div>
                    <div>
                        <label>Abre Tarde</label>
                        <input type="text" class="timepicker" id="helper_abertura_tarde" placeholder="HH:MM">
                    </div>
                    <div>
                        <label>Fecha Tarde</label>
                        <input type="text" class="timepicker" id="helper_fechamento_tarde" placeholder="HH:MM">
                    </div>
                </div>
                
                <div class="dias-checkboxes">
                    <input type="checkbox" class="helper-dia" value="1" id="dia-1"><label for="dia-1">Seg</label>
                    <input type="checkbox" class="helper-dia" value="2" id="dia-2"><label for="dia-2">Ter</label>
                    <input type="checkbox" class="helper-dia" value="3" id="dia-3"><label for="dia-3">Qua</label>
                    <input type="checkbox" class="helper-dia" value="4" id="dia-4"><label for="dia-4">Qui</label>
                    <input type="checkbox" class="helper-dia" value="5" id="dia-5"><label for="dia-5">Sex</label>
                    <input type="checkbox" class="helper-dia" value="6" id="dia-6"><label for="dia-6">Sáb</label>
                    <input type="checkbox" class="helper-dia" value="0" id="dia-0"><label for="dia-0">Dom</label>
                </div>
                
                <button type="button" class="form-button" id="btn_aplicar_horario">Aplicar aos dias selecionados</button>
                <button type="button" class="form-button" id="btn_dias_uteis">Selecionar Dias Úteis</button>
            </div>

            <div class="form-card" style="margin-bottom: 2rem;">
                <h3>Gerenciar Feriados</h3>
                
                <form action="processar_feriados.php" method="POST" class="form-feriado-config">
                    <input type="hidden" name="action" value="salvar_config">
                    
                    <div class="aberto-toggle">
                        <strong>Bloquear feriados nacionais?</strong>
                        <div>
                            <span>Não</span>
                            <label class="switch">
                                <input type="checkbox" name="bloquear_feriados" <?php if ($bloquear_feriados_auto) echo 'checked'; ?>>
                                <span class="slider"></span>
                            </label>
                            <span>Sim</span>
                        </div>
                    </div>
                    
                    <div class="botoes-feriado">
                        <button type="submit" class="form-button">Salvar Configuração</button>
                        <button type="button" class="form-button" id="btn-abrir-modal-feriado">
                            Adicionar Feriado Local
                        </button>
                    </div>
                </form>
            </div>

            <div class="form-card">
                <form action="processar_horarios.php" method="POST">
                    
                    <?php foreach ($dias_da_semana as $num_dia => $nome_dia): ?>
                        <?php $h = $horarios_db[$num_dia] ?? null; ?>
                        
                        <div class="horario-dia-container" id="container-dia-<?php echo $num_dia; ?>"> 
                            
                            <div class="dia-info">
                                <strong><?php echo $nome_dia; ?></strong>
                                <div class="aberto-toggle">
                                    <span>Fechado</span>
                                    <label class="switch">
                                        <input type="checkbox" name="aberto[<?php echo $num_dia; ?>]" 
                                               <?php if ($h && $h['aberto']) echo 'checked'; ?>>
                                        <span class="slider"></span>
                                    </label>
                                    <span>Aberto</span>
                                </div>
                            </div>
                            
                            <div class="horario-inputs">
                                <div>
                                    <label>Abre (Manhã)</label>
                                    <input type="text" class="timepicker" name="abertura_manha[<?php echo $num_dia; ?>]" value="<?php echo $h['hora_abertura_manha'] ?? ''; ?>" placeholder="HH:MM">
                                </div>
                                <div>
                                    <label>Fecha (Manhã)</label>
                                    <input type="text" class="timepicker" name="fechamento_manha[<?php echo $num_dia; ?>]" value="<?php echo $h['hora_fechamento_manha'] ?? ''; ?>" placeholder="HH:MM">
                                </div>
                                <div>
                                    <label>Abre (Tarde)</label>
                                    <input type="text" class="timepicker" name="abertura_tarde[<?php echo $num_dia; ?>]" value="<?php echo $h['hora_abertura_tarde'] ?? ''; ?>" placeholder="HH:MM">
                                </div>
                                <div>
                                    <label>Fecha (Tarde)</label>
                                    <input type="text" class="timepicker" name="fechamento_tarde[<?php echo $num_dia; ?>]" value="<?php echo $h['hora_fechamento_tarde'] ?? ''; ?>" placeholder="HH:MM">
                                </div>
                            </div>
                            
                        </div>
                    <?php endforeach; ?>
                    
                    <button type="submit" class="form-button" style="margin-top: 1.5rem;">Salvar Horários</button>
                </form>
            </div>
        </main>
    </div>

    <div class="modal-overlay" id="modal-feriado-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <h2>Gerenciar Feriados Locais</h2>
                <button class="modal-close-btn" id="modal-feriado-close-btn">&times;</button>
            </div>
            <div class="modal-body">
                
                <h4>Adicionar Feriado Local/Personalizado</h4>
                <form action="processar_feriados.php" method="POST" class="form-feriado-local">
                    <input type="hidden" name="action" value="add_feriado">
                    <div class="form-group">
                        <label>Data</label>
                        <input type="date" name="data_feriado" required>
                    </div>
                    <div class="form-group">
                        <label>Descrição (Ex: Aniversário da Cidade)</label>
                        <input type="text" name="descricao_feriado" placeholder="Aniversário da Cidade" required>
                    </div>
                    <button type="submit" class="form-button">Adicionar</button>
                </form>

                <h4 style="margin-top: 2rem;">Meus Feriados Personalizados</h4>
                <table class="tabela-agenda" style="margin-top: 1rem;">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Descrição</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($feriados_locais) > 0): ?>
                            <?php foreach($feriados_locais as $feriado): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($feriado['data'])); ?></td>
                                    <td><?php echo htmlspecialchars($feriado['descricao']); ?></td>
                                    <td class="actions-cell" style="white-space: nowrap;">
                                        <a href="processar_feriados.php?action=del_feriado&id=<?php echo $feriado['id']; ?>" 
                                           class="icon-delete" title="Excluir Feriado"
                                           onclick="return confirm('Tem certeza que deseja excluir este feriado?');">
                                           <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" style="text-align: center; color: #888;">Nenhum feriado local cadastrado.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

            </div>
        </div>
    </div>


    <script>
        // 1. Script do Assistente Rápido
        const btnAplicar = document.getElementById('btn_aplicar_horario');
        const btnDiasUteis = document.getElementById('btn_dias_uteis');
        const helperAbManha = document.getElementById('helper_abertura_manha');
        const helperFcManha = document.getElementById('helper_fechamento_manha');
        const helperAbTarde = document.getElementById('helper_abertura_tarde');
        const helperFcTarde = document.getElementById('helper_fechamento_tarde');
        const checkboxesDias = document.querySelectorAll('.helper-dia');
        if (btnDiasUteis) {
            btnDiasUteis.addEventListener('click', () => {
                checkboxesDias.forEach(cb => {
                    const dia = cb.value;
                    if (dia >= 1 && dia <= 5) { cb.checked = true; } 
                    else { cb.checked = false; }
                });
            });
        }
        if (btnAplicar) {
            btnAplicar.addEventListener('click', () => {
                const abManha = helperAbManha.value;
                const fcManha = helperFcManha.value;
                const abTarde = helperAbTarde.value;
                const fcTarde = helperFcTarde.value;
                checkboxesDias.forEach(cb => {
                    if (cb.checked) {
                        const diaNum = cb.value;
                        const container = document.getElementById(`container-dia-${diaNum}`);
                        container.querySelector('input[type="checkbox"]').checked = true;
                        container.querySelector(`input[name="abertura_manha[${diaNum}]"]`).value = abManha;
                        container.querySelector(`input[name="fechamento_manha[${diaNum}]"]`).value = fcManha;
                        container.querySelector(`input[name="abertura_tarde[${diaNum}]"]`).value = abTarde;
                        container.querySelector(`input[name="fechamento_tarde[${diaNum}]"]`).value = fcTarde;
                    }
                });
            });
        }
        
        // 2. Script da Mensagem de Sucesso
        document.addEventListener('DOMContentLoaded', (event) => {
            const alertMessage = document.querySelector('.auto-dismiss-message');
            if (alertMessage) {
                setTimeout(() => {
                    alertMessage.classList.add('fade-out');
                }, 3000); 
            }
        });

        // 3. SCRIPT DO MODAL DE FERIADO
        const btnAbrirModalFeriado = document.getElementById('btn-abrir-modal-feriado');
        const modalFeriadoOverlay = document.getElementById('modal-feriado-overlay');
        const btnFecharModalFeriado = document.getElementById('modal-feriado-close-btn');

        if (btnAbrirModalFeriado) {
            btnAbrirModalFeriado.addEventListener('click', () => {
                modalFeriadoOverlay.classList.add('show');
            });
        }
        if (btnFecharModalFeriado) {
            btnFecharModalFeriado.addEventListener('click', () => {
                modalFeriadoOverlay.classList.remove('show');
            });
        }
        if (modalFeriadoOverlay) {
            modalFeriadoOverlay.addEventListener('click', (e) => {
                // Fecha só se clicar no fundo (overlay)
                if (e.target === modalFeriadoOverlay) {
                    modalFeriadoOverlay.classList.remove('show');
                }
            });
        }
    // ... (fim do script do assistente e da mensagem) ...

        /* ======================================= */
        /* ---- [NOVO] ATIVAR O RELÓGIO BONITO ---- */
        /* ======================================= */
        
        // Configurações do relógio
        const timepickerConfig = {
            enableTime: true,   // Habilita a seleção de hora
            noCalendar: true,   // Esconde o calendário (só queremos a hora)
            dateFormat: "H:i",  // Formato de 24h (ex: 14:00)
            time_24hr: true,
            minuteIncrement: 30 // Pulos de 30 em 30 min (opcional)
        };

        // Aplica o relógio em TODOS os inputs com a classe ".timepicker"
        flatpickr(".timepicker", timepickerConfig);

    </script>
    <script src="script_painel.js"></script>
    <?php require '../footer.php'; ?>
</body>
</html>