<?php
session_start();
require 'conexao.php';
date_default_timezone_set('America/Sao_Paulo');

// --- 1. Inclui os arquivos do PHPMailer ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'src/Exception.php';
require 'src/PHPMailer.php';
require 'src/SMTP.php';

// --- 2. Porteiro ---
if (!isset($_SESSION['usuario_id'])) {
    die("Acesso negado.");
}

$id_cliente = $_SESSION['usuario_id'];

if (!isset($_GET['id'])) {
    die("ID do agendamento não fornecido.");
}
$id_agendamento = $_GET['id'];

try {
    // --- 3. Buscar dados ANTES de cancelar ---
    $stmt_dados = $pdo->prepare(
        "SELECT 
            a.data_hora_inicio,
            a.id_negocio,
            a.id_funcionario,
            c.nome AS cliente_nome,
            c.email AS cliente_email,
            f.nome AS funcionario_nome, 
            f.email AS funcionario_email,
            s.nome_servico
        FROM agendamentos a
        JOIN usuarios c ON a.id_cliente = c.id
        JOIN usuarios f ON a.id_funcionario = f.id
        JOIN servicos s ON a.id_servico = s.id
        WHERE a.id = :id_agendamento AND a.id_cliente = :id_cliente"
    );
    $stmt_dados->execute([
        'id_agendamento' => $id_agendamento,
        'id_cliente' => $id_cliente
    ]);
    $agendamento = $stmt_dados->fetch(PDO::FETCH_ASSOC);

    if (!$agendamento) {
        die("Agendamento não encontrado.");
    }

    // --- 4. Atualizar o status para "cancelado" ---
    $stmt_update = $pdo->prepare(
        "UPDATE agendamentos SET status = 'cancelado' 
         WHERE id = :id_agendamento"
    );
    $stmt_update->execute(['id_agendamento' => $id_agendamento]);


    // ============================================================
    // CONFIGURAÇÃO GERAL DO EMAIL (SEU GMAIL)
    // ============================================================
    $smtp_host = 'smtp.gmail.com';
    $smtp_user = 'agilizaagendas@gmail.com'; // [SEU EMAIL]
    $smtp_pass = 'zvre bwjq trft zwjp';      // [SUA SENHA DE APP]
    $smtp_from = 'agilizaagendas@gmail.com'; 
    $smtp_name = 'Agiliza - Notificações';
    // ============================================================


    // --- 5. E-mail para o PROFISSIONAL (Aviso) ---
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $smtp_host;
        $mail->SMTPAuth = true;
        $mail->Username = $smtp_user;
        $mail->Password = $smtp_pass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;
        $mail->CharSet = 'UTF-8';
        $mail->setFrom($smtp_from, $smtp_name);
        
        $mail->addAddress($agendamento['funcionario_email']);
        $mail->isHTML(true);
        $mail->Subject = "AVISO: Cancelamento (" . $agendamento['nome_servico'] . ")";
        $mail->Body = "
            <p>Olá, " . $agendamento['funcionario_nome'] . ".</p>
            <p>O agendamento de <b>" . $agendamento['cliente_nome'] . "</b> foi cancelado.</p>
            <p>Data: " . date('d/m/Y H:i', strtotime($agendamento['data_hora_inicio'])) . "</p>
        ";
        $mail->send();
    } catch (Exception $e) {}

    // --- 6. E-mail para o CLIENTE (Confirmação) ---
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $smtp_host;
        $mail->SMTPAuth = true;
        $mail->Username = $smtp_user;
        $mail->Password = $smtp_pass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;
        $mail->CharSet = 'UTF-8';
        $mail->setFrom($smtp_from, $smtp_name);

        $mail->addAddress($agendamento['cliente_email']);
        $mail->isHTML(true);
        $mail->Subject = "Confirmado: Cancelamento de Agendamento";
        
        $btn_style = "background-color: #7E57C2; color: #ffffff; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 15px;";
        $link_site = "http://localhost/Agiliza/index.php"; 

        $mail->Body = "
            <div style='font-family: Arial, sans-serif; color: #333;'>
                <h2 style='color: #7E57C2;'>Cancelamento Confirmado</h2>
                <p>Olá, " . $agendamento['cliente_nome'] . ".</p>
                <p>Seu agendamento para <b>" . $agendamento['nome_servico'] . "</b> foi cancelado com sucesso.</p>
                <br>
                <a href='" . $link_site . "' style='" . $btn_style . "'>Agendar Novo Horário</a>
            </div>
        ";
        $mail->send();
    } catch (Exception $e) {}

    // --- 7. NOTIFICAR LISTA DE ESPERA (A Vaga Abriu!) ---
    try {
        $data_vaga = date('Y-m-d', strtotime($agendamento['data_hora_inicio']));
        
        // Busca quem quer ESTE profissional OU "Qualquer um" NESTA data
        $stmt_lista = $pdo->prepare(
            "SELECT u.email 
             FROM lista_espera le
             JOIN usuarios u ON le.id_cliente = u.id
             WHERE le.id_negocio = :negocio 
               AND (le.id_funcionario = :func OR le.id_funcionario = 0)
               AND le.data_desejada = :data
               AND le.status = 'ativo'"
        );
        $stmt_lista->execute([
            'negocio' => $agendamento['id_negocio'],
            'func' => $agendamento['id_funcionario'],
            'data' => $data_vaga
        ]);
        $esperando = $stmt_lista->fetchAll(PDO::FETCH_ASSOC);

        if (count($esperando) > 0) {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $smtp_host;
            $mail->SMTPAuth = true;
            $mail->Username = $smtp_user;
            $mail->Password = $smtp_pass;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;
            $mail->CharSet = 'UTF-8';
            $mail->setFrom($smtp_from, $smtp_name);

            $mail->Subject = "VAGA ABERTA! " . date('d/m', strtotime($data_vaga));
            
            $btn_style = "background-color: #28a745; color: #ffffff; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 15px;";
            // Link direto para agendar nesse negócio
            $link_agendar = "http://localhost/Agiliza/agendamento.php?id_negocio=" . $agendamento['id_negocio'];

            $mail->Body = "
                <div style='font-family: Arial, sans-serif; color: #333;'>
                    <h2 style='color: #28a745;'>Uma vaga abriu!</h2>
                    <p>Um horário acabou de vagar com <b>" . $agendamento['funcionario_nome'] . "</b> para o dia <b>" . date('d/m/Y', strtotime($data_vaga)) . "</b>.</p>
                    <p>Corra para garantir antes que outra pessoa agende!</p>
                    <center>
                        <a href='" . $link_agendar . "' style='" . $btn_style . "'>Agendar Agora</a>
                    </center>
                </div>
            ";
            $mail->isHTML(true);

            // Envia para todos como Cópia Oculta (BCC)
            foreach ($esperando as $pessoa) {
                $mail->addBCC($pessoa['email']);
            }

            $mail->send();
        }
    } catch (Exception $e) {}

    // --- 8. Redirecionar ---
    header("Location: meus_agendamentos.php?sucesso=cancelado");
    exit();

} catch (PDOException $e) {
    die("Erro ao cancelar: " . $e->getMessage());
}
?>