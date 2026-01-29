<?php
// Define o tipo de resposta como JSON
header('Content-Type: application/json');

require 'conexao.php';
session_start();

date_default_timezone_set('America/Sao_Paulo');
setlocale(LC_TIME, 'pt_BR.utf-8', 'pt_BR', 'portuguese');

$id_negocio = isset($_GET['id_negocio']) ? (int)$_GET['id_negocio'] : 0;

if ($id_negocio == 0) {
    echo json_encode(['erro' => 'ID do negócio não fornecido']);
    exit();
}

try {
    $action = $_GET['action'] ?? null;

    // --- AÇÃO 1: BUSCAR SERVIÇOS ---
    if ($action == 'servicos') {
        $stmt = $pdo->prepare("SELECT id, nome_servico, preco, duracao_minutos FROM servicos WHERE id_negocio = :id_negocio AND ativo = 1");
        $stmt->execute(['id_negocio' => $id_negocio]);
        $servicos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($servicos);
        exit();
    }
    
    // --- AÇÃO 2: BUSCAR PROFISSIONAIS ---
    else if ($action == 'profissionais') {
        $stmt = $pdo->prepare("SELECT u.id, u.nome, u.foto_perfil_url FROM usuarios u JOIN negocio_membros m ON u.id = m.id_usuario WHERE m.id_negocio = :id_negocio AND m.funcao = 'funcionario'");
        $stmt->execute(['id_negocio' => $id_negocio]);
        $profissionais = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $resultado = [];
        foreach ($profissionais as $prof) {
            $stmt_fotos = $pdo->prepare("SELECT url_imagem FROM portfolio_fotos WHERE id_funcionario = :id_func AND destaque_popup = 1 LIMIT 4");
            $stmt_fotos->execute(['id_func' => $prof['id']]);
            $prof['portfolio'] = $stmt_fotos->fetchAll(PDO::FETCH_COLUMN);
            $resultado[] = $prof;
        }
        echo json_encode($resultado);
        exit();
    }

    // --- AÇÃO 3: BUSCAR HORÁRIOS ---
    else if ($action == 'horarios') {
        
        $id_funcionario = isset($_GET['id_funcionario']) ? (int)$_GET['id_funcionario'] : 0;
        $id_servico = isset($_GET['id_servico']) ? (int)$_GET['id_servico'] : 0;
        $data_desejada = $_GET['data'] ?? date('Y-m-d');
        
        $data_hoje = date('Y-m-d');
        if ($data_desejada < $data_hoje) {
            echo json_encode(['erro' => 'Não é possível agendar em datas passadas.']);
            exit();
        }
        
        $stmt_config = $pdo->prepare("SELECT bloquear_feriados_auto FROM negocios WHERE id = :id_negocio");
        $stmt_config->execute(['id_negocio' => $id_negocio]);
        $quer_bloquear_nacional = $stmt_config->fetchColumn();
        if ($quer_bloquear_nacional) {
            $feriados_nacionais_fixos = ['01-01', '04-21', '05-01', '09-07', '10-12', '11-02', '11-15', '12-25'];
            $mes_dia_desejado = date('m-d', strtotime($data_desejada));
            if (in_array($mes_dia_desejado, $feriados_nacionais_fixos)) {
                echo json_encode(['erro' => 'Negócio fechado (Feriado Nacional).']);
                exit();
            }
        }
        
        $stmt_feriado_local = $pdo->prepare("SELECT COUNT(*) FROM feriados_personalizados WHERE id_negocio = :id_negocio AND data = :data_desejada");
        $stmt_feriado_local->execute(['id_negocio' => $id_negocio, 'data_desejada' => $data_desejada]);
        if ($stmt_feriado_local->fetchColumn() > 0) {
            echo json_encode(['erro' => 'Negócio fechado (Feriado Local).']);
            exit();
        }
        
        $lista_funcionarios = [];
        if ($id_funcionario == 0) {
            $stmt_funcs = $pdo->prepare("SELECT id_usuario FROM negocio_membros WHERE id_negocio = :id_negocio AND funcao = 'funcionario'");
            $stmt_funcs->execute(['id_negocio' => $id_negocio]);
            $lista_funcionarios = $stmt_funcs->fetchAll(PDO::FETCH_COLUMN, 0);
        } else {
            $lista_funcionarios[] = $id_funcionario;
        }
        if (empty($lista_funcionarios)) { echo json_encode(['erro' => 'Nenhum profissional encontrado.']); exit(); }
        
        $stmt_serv = $pdo->prepare("SELECT duracao_minutos FROM servicos WHERE id = :id_servico");
        $stmt_serv->execute(['id_servico' => $id_servico]);
        $duracao_servico = $stmt_serv->fetchColumn();
        if (!$duracao_servico) { echo json_encode(['erro' => 'Serviço não encontrado']); exit(); }

        $dia_semana = date('w', strtotime($data_desejada));
        $stmt_horario = $pdo->prepare("SELECT hora_abertura_manha, hora_fechamento_manha, hora_abertura_tarde, hora_fechamento_tarde FROM horarios_funcionamento WHERE id_negocio = :id_negocio AND dia_semana = :dia AND aberto = 1");
        $stmt_horario->execute(['id_negocio' => $id_negocio, 'dia' => $dia_semana]);
        $horario_loja = $stmt_horario->fetch(PDO::FETCH_ASSOC);
        if (!$horario_loja) { echo json_encode(['erro' => 'Negócio fechado neste dia.']); exit(); }
        
        $turnos = [];
        if ($horario_loja['hora_abertura_manha'] && $horario_loja['hora_fechamento_manha'] && $horario_loja['hora_abertura_tarde'] && $horario_loja['hora_fechamento_tarde']) {
            $turnos[] = ['inicio' => $horario_loja['hora_abertura_manha'], 'fim' => $horario_loja['hora_fechamento_manha']];
            $turnos[] = ['inicio' => $horario_loja['hora_abertura_tarde'], 'fim' => $horario_loja['hora_fechamento_tarde']];
        } else if ($horario_loja['hora_abertura_manha'] && $horario_loja['hora_fechamento_tarde']) {
            $turnos[] = ['inicio' => $horario_loja['hora_abertura_manha'], 'fim' => $horario_loja['hora_fechamento_tarde']];
        }
        
        $placeholders = implode(',', array_fill(0, count($lista_funcionarios), '?'));
        $data_inicio = $data_desejada . ' 00:00:00';
        $data_fim = $data_desejada . ' 23:59:59';
        $params = $lista_funcionarios; $params[] = $data_inicio; $params[] = $data_fim;
        
        // ===========================================
        //     [A CORREÇÃO ESTÁ AQUI]
        // =_=========================================
        // Agora, ele só busca agendamentos que estão 'confirmado'
        $stmt_ag = $pdo->prepare(
            "SELECT data_hora_inicio, data_hora_fim FROM agendamentos
             WHERE id_funcionario IN ($placeholders) AND (data_hora_inicio BETWEEN ? AND ?)
             AND status = 'confirmado'"
        );
        $stmt_ag->execute($params);
        $agendamentos = $stmt_ag->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt_bl = $pdo->prepare(
            "SELECT data_hora_inicio, data_hora_fim FROM horarios_bloqueados
             WHERE id_funcionario IN ($placeholders) AND (data_hora_inicio BETWEEN ? AND ?)"
        );
        $stmt_bl->execute($params);
        $bloqueios = $stmt_bl->fetchAll(PDO::FETCH_ASSOC);
        
        $ocupados = array_merge($agendamentos, $bloqueios);
        $horarios_disponiveis = [];
        $intervalo = 30;
        $hora_atual = new DateTime(date('Y-m-d H:i:s'));
        
        foreach ($turnos as $turno) {
            $hora_inicio = new DateTime($data_desejada . ' ' . $turno['inicio']);
            $hora_fim_turno = new DateTime($data_desejada . ' ' . $turno['fim']);

            while ($hora_inicio < $hora_fim_turno) {
                $hora_termino_servico = (clone $hora_inicio)->modify("+$duracao_servico minutes");
                $slot_disponivel = true;

                if ($data_desejada == $data_hoje && $hora_inicio < $hora_atual) { 
                    $slot_disponivel = false; 
                }
                
                if ($hora_termino_servico > $hora_fim_turno) { $slot_disponivel = false; }
                
                if ($slot_disponivel) {
                    if ($id_funcionario == 0) {
                        $conflitos_no_slot = 0;
                        foreach ($ocupados as $ocupado) {
                            $ocupado_inicio = new DateTime($ocupado['data_hora_inicio']);
                            $ocupado_fim = new DateTime($ocupado['data_hora_fim']);
                            if ($hora_inicio < $ocupado_fim && $hora_termino_servico > $ocupado_inicio) {
                                $conflitos_no_slot++;
                            }
                        }
                        if ($conflitos_no_slot >= count($lista_funcionarios)) {
                            $slot_disponivel = false;
                        }
                    } else {
                        foreach ($ocupados as $ocupado) {
                            $ocupado_inicio = new DateTime($ocupado['data_hora_inicio']);
                            $ocupado_fim = new DateTime($ocupado['data_hora_fim']);
                            if ($hora_inicio < $ocupado_fim && $hora_termino_servico > $ocupado_inicio) {
                                $slot_disponivel = false;
                                break;
                            }
                        }
                    }
                }

                if ($slot_disponivel) {
                    $horarios_disponiveis[] = $hora_inicio->format('H:i');
                }
                
                $hora_inicio->modify("+$intervalo minutes");
            }
        }
        
        $horarios_disponiveis = array_unique($horarios_disponiveis);
        sort($horarios_disponiveis);

        echo json_encode($horarios_disponiveis);
        exit();
    }
    
    // Se nenhuma ação válida for encontrada
    else {
        echo json_encode(['erro' => 'Ação inválida']);
        exit();
    }

} catch (PDOException $e) {
    die("ERRO FATAL DO BANCO DE DADOS: " . $e->getMessage());
}
?>