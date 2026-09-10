<?php

require_once __DIR__ . '/../config/bootstrap.php';

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

require_once APP_ROOT . '/src/Exception.php';
require_once APP_ROOT . '/src/PHPMailer.php';
require_once APP_ROOT . '/src/SMTP.php';

session_start();
date_default_timezone_set('America/Sao_Paulo');

function criarMailer(): PHPMailer
{
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'agilizaagendas@gmail.com';
    $mail->Password = 'zvre bwjq trft zwjp';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = 465;
    $mail->CharSet = 'UTF-8';
    $mail->setFrom('agilizaagendas@gmail.com', 'Agiliza - Notificações');
    return $mail;
}

function responderJson(array $dados): never
{
    header('Content-Type: application/json');
    echo json_encode($dados);
    exit();
}

function buscarDadosAgendamento(): never
{
    global $pdo;
    $id_negocio = (int)($_GET['id_negocio'] ?? 0);
    $subaction = $_GET['subaction'] ?? '';

    if (!$id_negocio) {
        responderJson(['erro' => 'ID do negócio não fornecido']);
    }

    try {
        if ($subaction === 'servicos') {
            $stmt = $pdo->prepare(
                'SELECT id, nome_servico, preco, duracao_minutos FROM servicos
                 WHERE id_negocio = :id_negocio AND ativo = 1'
            );
            $stmt->execute(['id_negocio' => $id_negocio]);
            responderJson($stmt->fetchAll(PDO::FETCH_ASSOC));
        }

        if ($subaction === 'profissionais') {
            $stmt = $pdo->prepare(
                "SELECT u.id, u.nome, u.foto_perfil_url
                 FROM usuarios u JOIN negocio_membros m ON u.id = m.id_usuario
                 WHERE m.id_negocio = :id_negocio AND m.funcao = 'funcionario'"
            );
            $stmt->execute(['id_negocio' => $id_negocio]);
            $resultado = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $profissional) {
                $stmt_fotos = $pdo->prepare(
                    'SELECT url_imagem FROM portfolio_fotos
                     WHERE id_funcionario = :id_func AND destaque_popup = 1 LIMIT 4'
                );
                $stmt_fotos->execute(['id_func' => $profissional['id']]);
                $profissional['portfolio'] = $stmt_fotos->fetchAll(PDO::FETCH_COLUMN);
                $resultado[] = $profissional;
            }
            responderJson($resultado);
        }

        if ($subaction !== 'horarios') {
            responderJson(['erro' => 'Ação inválida']);
        }

        $id_funcionario = (int)($_GET['id_funcionario'] ?? 0);
        $id_servico = (int)($_GET['id_servico'] ?? 0);
        $data = $_GET['data'] ?? date('Y-m-d');
        $hoje = date('Y-m-d');
        if ($data < $hoje) {
            responderJson(['erro' => 'Não é possível agendar em datas passadas.']);
        }

        $stmt = $pdo->prepare('SELECT bloquear_feriados_auto FROM negocios WHERE id = :id');
        $stmt->execute(['id' => $id_negocio]);
        $feriadoNacional = ['01-01', '04-21', '05-01', '09-07', '10-12', '11-02', '11-15', '12-25'];
        if ($stmt->fetchColumn() && in_array(date('m-d', strtotime($data)), $feriadoNacional, true)) {
            responderJson(['erro' => 'Negócio fechado (Feriado Nacional).']);
        }

        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM feriados_personalizados
             WHERE id_negocio = :id_negocio AND data = :data'
        );
        $stmt->execute(['id_negocio' => $id_negocio, 'data' => $data]);
        if ($stmt->fetchColumn() > 0) {
            responderJson(['erro' => 'Negócio fechado (Feriado Local).']);
        }

        if (!$id_funcionario) {
            $stmt = $pdo->prepare(
                "SELECT id_usuario FROM negocio_membros
                 WHERE id_negocio = :id_negocio AND funcao = 'funcionario'"
            );
            $stmt->execute(['id_negocio' => $id_negocio]);
            $funcionarios = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } else {
            $funcionarios = [$id_funcionario];
        }
        if (!$funcionarios) {
            responderJson(['erro' => 'Nenhum profissional encontrado.']);
        }

        $stmt = $pdo->prepare('SELECT duracao_minutos FROM servicos WHERE id = :id');
        $stmt->execute(['id' => $id_servico]);
        $duracao = (int)$stmt->fetchColumn();
        if (!$duracao) {
            responderJson(['erro' => 'Serviço não encontrado']);
        }

        $stmt = $pdo->prepare(
            'SELECT hora_abertura_manha, hora_fechamento_manha,
                    hora_abertura_tarde, hora_fechamento_tarde
             FROM horarios_funcionamento
             WHERE id_negocio = :id_negocio AND dia_semana = :dia AND aberto = 1'
        );
        $stmt->execute(['id_negocio' => $id_negocio, 'dia' => date('w', strtotime($data))]);
        $horario = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$horario) {
            responderJson(['erro' => 'Negócio fechado neste dia.']);
        }

        $turnos = [];
        if ($horario['hora_abertura_manha'] && $horario['hora_fechamento_manha'] &&
            $horario['hora_abertura_tarde'] && $horario['hora_fechamento_tarde']) {
            $turnos[] = ['inicio' => $horario['hora_abertura_manha'], 'fim' => $horario['hora_fechamento_manha']];
            $turnos[] = ['inicio' => $horario['hora_abertura_tarde'], 'fim' => $horario['hora_fechamento_tarde']];
        } elseif ($horario['hora_abertura_manha'] && $horario['hora_fechamento_tarde']) {
            $turnos[] = ['inicio' => $horario['hora_abertura_manha'], 'fim' => $horario['hora_fechamento_tarde']];
        }

        $placeholders = implode(',', array_fill(0, count($funcionarios), '?'));
        $params = $funcionarios;
        $params[] = "$data 00:00:00";
        $params[] = "$data 23:59:59";
        $stmt = $pdo->prepare(
            "SELECT data_hora_inicio, data_hora_fim FROM agendamentos
             WHERE id_funcionario IN ($placeholders)
             AND data_hora_inicio BETWEEN ? AND ? AND status = 'confirmado'"
        );
        $stmt->execute($params);
        $ocupados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare(
            "SELECT data_hora_inicio, data_hora_fim FROM horarios_bloqueados
             WHERE id_funcionario IN ($placeholders) AND data_hora_inicio BETWEEN ? AND ?"
        );
        $stmt->execute($params);
        $ocupados = array_merge($ocupados, $stmt->fetchAll(PDO::FETCH_ASSOC));

        $disponiveis = [];
        $agora = new DateTime();
        foreach ($turnos as $turno) {
            $inicio = new DateTime("$data {$turno['inicio']}");
            $fimTurno = new DateTime("$data {$turno['fim']}");
            while ($inicio < $fimTurno) {
                $fim = (clone $inicio)->modify("+$duracao minutes");
                $livre = $fim <= $fimTurno && !($data === $hoje && $inicio < $agora);
                $conflitos = 0;
                foreach ($ocupados as $ocupado) {
                    if ($inicio < new DateTime($ocupado['data_hora_fim']) && $fim > new DateTime($ocupado['data_hora_inicio'])) {
                        $conflitos++;
                    }
                }
                if ($conflitos >= ($id_funcionario ? 1 : count($funcionarios))) {
                    $livre = false;
                }
                if ($livre) {
                    $disponiveis[] = $inicio->format('H:i');
                }
                $inicio->modify('+30 minutes');
            }
        }

        $disponiveis = array_values(array_unique($disponiveis));
        sort($disponiveis);
        responderJson($disponiveis);
    } catch (PDOException $e) {
        responderJson(['erro' => 'Erro ao consultar horários.']);
    }
}

function processarAgendamento(): never
{
    global $pdo;
    if (!isset($_SESSION['usuario_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
        responderJson(['sucesso' => false, 'erro' => 'Acesso negado.']);
    }

    $id_negocio = (int)($_POST['id_negocio'] ?? 0);
    $id_servico = (int)($_POST['id_servico'] ?? 0);
    $id_funcionario = (int)($_POST['id_funcionario'] ?? 0);
    $inicio = $_POST['data_hora_inicio'] ?? '';
    if (!$id_negocio || !$id_servico || !$inicio) {
        responderJson(['sucesso' => false, 'erro' => 'Dados incompletos.']);
    }

    try {
        $stmt = $pdo->prepare('SELECT nome, email FROM usuarios WHERE id = :id');
        $stmt->execute(['id' => $_SESSION['usuario_id']]);
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt = $pdo->prepare('SELECT nome_servico, duracao_minutos FROM servicos WHERE id = :id');
        $stmt->execute(['id' => $id_servico]);
        $servico = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$cliente || !$servico) {
            responderJson(['sucesso' => false, 'erro' => 'Cliente ou serviço não encontrado.']);
        }

        if (!$id_funcionario) {
            $stmt = $pdo->prepare('SELECT id_dono FROM negocios WHERE id = :id');
            $stmt->execute(['id' => $id_negocio]);
            $id_funcionario = (int)$stmt->fetchColumn();
        }
        $stmt = $pdo->prepare('SELECT nome, email FROM usuarios WHERE id = :id');
        $stmt->execute(['id' => $id_funcionario]);
        $funcionario = $stmt->fetch(PDO::FETCH_ASSOC);
        $inicioObj = new DateTime($inicio);
        $fimObj = (clone $inicioObj)->modify('+' . (int)$servico['duracao_minutos'] . ' minutes');

        $stmt = $pdo->prepare(
            "INSERT INTO agendamentos
             (id_negocio, id_cliente, id_funcionario, id_servico, data_hora_inicio, data_hora_fim, status)
             VALUES (:negocio, :cliente, :funcionario, :servico, :inicio, :fim, 'confirmado')"
        );
        $stmt->execute([
            'negocio' => $id_negocio,
            'cliente' => $_SESSION['usuario_id'],
            'funcionario' => $id_funcionario,
            'servico' => $id_servico,
            'inicio' => $inicioObj->format('Y-m-d H:i:s'),
            'fim' => $fimObj->format('Y-m-d H:i:s')
        ]);

        try {
            $mail = criarMailer();
            $mail->addAddress($funcionario['email'], $funcionario['nome']);
            $mail->addReplyTo($cliente['email'], $cliente['nome']);
            $mail->isHTML(true);
            $mail->Subject = 'Novo Agendamento: ' . $servico['nome_servico'];
            $mail->Body = '<h3>Novo agendamento</h3><p>Cliente: ' . htmlspecialchars($cliente['nome']) .
                '<br>Serviço: ' . htmlspecialchars($servico['nome_servico']) .
                '<br>Data: ' . $inicioObj->format('d/m/Y H:i') . '</p>';
            $mail->send();
            $mail->clearAddresses();
            $mail->addAddress($cliente['email'], $cliente['nome']);
            $mail->Subject = 'Agendamento Confirmado: ' . $servico['nome_servico'];
            $mail->Body = '<h3>Agendamento confirmado</h3><p>Seu horário com ' .
                htmlspecialchars($funcionario['nome']) . ' foi reservado para ' .
                $inicioObj->format('d/m/Y H:i') . '.</p>';
            $mail->send();
        } catch (Exception $e) {
        }
        responderJson(['sucesso' => true]);
    } catch (PDOException $e) {
        responderJson(['sucesso' => false, 'erro' => 'Erro no banco.']);
    }
}

function cancelarAgendamento(): never
{
    global $pdo;
    if (!isset($_SESSION['usuario_id'])) {
        exit('Acesso negado.');
    }
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) {
        exit('ID do agendamento não fornecido.');
    }

    try {
        $stmt = $pdo->prepare(
                'SELECT a.id_negocio, a.id_funcionario, a.data_hora_inicio,
                    c.nome cliente_nome, c.email cliente_email,
                    f.nome funcionario_nome, f.email funcionario_email, s.nome_servico
             FROM agendamentos a
             JOIN usuarios c ON a.id_cliente = c.id
             JOIN usuarios f ON a.id_funcionario = f.id
             JOIN servicos s ON a.id_servico = s.id
             WHERE a.id = :id AND a.id_cliente = :cliente'
        );
        $stmt->execute(['id' => $id, 'cliente' => $_SESSION['usuario_id']]);
        $agendamento = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$agendamento) {
            exit('Agendamento não encontrado.');
        }

        $stmt = $pdo->prepare("UPDATE agendamentos SET status = 'cancelado' WHERE id = :id");
        $stmt->execute(['id' => $id]);

        try {
            $mail = criarMailer();
            $mail->addAddress($agendamento['funcionario_email']);
            $mail->isHTML(true);
            $mail->Subject = 'Cancelamento de agendamento';
            $mail->Body = '<p>O agendamento de ' . htmlspecialchars($agendamento['cliente_nome']) . ' foi cancelado.</p>';
            $mail->send();
            $mail->clearAddresses();
            $mail->addAddress($agendamento['cliente_email']);
            $mail->Subject = 'Cancelamento confirmado';
            $mail->Body = '<p>Seu agendamento de ' . htmlspecialchars($agendamento['nome_servico']) . ' foi cancelado.</p>';
            $mail->send();
        } catch (Exception $e) {
        }

        $stmt = $pdo->prepare(
            "SELECT u.email FROM lista_espera le
             JOIN usuarios u ON le.id_cliente = u.id
             WHERE le.id_negocio = :negocio
               AND (le.id_funcionario = :funcionario OR le.id_funcionario = 0)
               AND le.data_desejada = :data AND le.status = 'ativo'"
        );
        $stmt->execute([
            'negocio' => $agendamento['id_negocio'],
            'funcionario' => $agendamento['id_funcionario'],
            'data' => date('Y-m-d', strtotime($agendamento['data_hora_inicio']))
        ]);
        $esperando = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if ($esperando) {
            try {
                $mail = criarMailer();
                $mail->isHTML(true);
                $mail->Subject = 'Vaga aberta no Agiliza';
                $mail->Body = '<p>Uma vaga foi liberada para ' .
                    date('d/m/Y', strtotime($agendamento['data_hora_inicio'])) . '.</p>';
                foreach ($esperando as $email) {
                    $mail->addBCC($email);
                }
                $mail->send();
            } catch (Exception $e) {
            }
        }

        header('Location: ' . BASE_URL . '/views/cliente/meus_agendamentos.php?sucesso=cancelado');
        exit();
    } catch (PDOException $e) {
        exit('Erro ao cancelar o agendamento.');
    }
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
if ($action === 'dados') {
    buscarDadosAgendamento();
}
if ($action === 'processar') {
    processarAgendamento();
}
if ($action === 'cancelar') {
    cancelarAgendamento();
}

http_response_code(400);
responderJson(['sucesso' => false, 'erro' => 'Ação de agendamento inválida.']);