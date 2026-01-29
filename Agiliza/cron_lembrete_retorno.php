<?php
// ROBÔ DE RETENÇÃO DE CLIENTES
require 'conexao.php';
date_default_timezone_set('America/Sao_Paulo');

// Inclui PHPMailer (para enviar o convite)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
require 'src/Exception.php';
require 'src/PHPMailer.php';
require 'src/SMTP.php';

echo "Iniciando análise de retenção...\n";

try {
    $hoje = date('Y-m-d');
    
    // 1. A GRANDE CONSULTA:
    // Encontra agendamentos concluídos onde:
    // - O serviço tem uma "frequência de retorno" definida.
    // - A data ideal de retorno (data do serviço + frequência) é HOJE.
    // - O cliente NÃO tem nenhum agendamento futuro marcado nesse negócio.
    
    $sql = "
        SELECT 
            a.id_cliente, 
            c.nome AS nome_cliente, 
            c.email AS email_cliente,
            s.nome_servico, 
            s.frequencia_retorno_dias,
            n.nome_negocio,
            n.id AS id_negocio,
            DATE_ADD(DATE(a.data_hora_inicio), INTERVAL s.frequencia_retorno_dias DAY) AS data_sugerida
        FROM agendamentos a
        JOIN servicos s ON a.id_servico = s.id
        JOIN usuarios c ON a.id_cliente = c.id
        JOIN negocios n ON a.id_negocio = n.id
        WHERE 
            a.status = 'concluido' 
            AND s.frequencia_retorno_dias IS NOT NULL
            AND DATE_ADD(DATE(a.data_hora_inicio), INTERVAL s.frequencia_retorno_dias DAY) = :hoje
            -- Garante que é o ÚLTIMO agendamento desse serviço para esse cliente
            AND a.data_hora_inicio = (
                SELECT MAX(a2.data_hora_inicio) 
                FROM agendamentos a2 
                WHERE a2.id_cliente = a.id_cliente AND a2.id_servico = s.id
            )
            -- Garante que o cliente NÃO tem agendamento futuro neste negócio
            AND NOT EXISTS (
                SELECT 1 FROM agendamentos a3 
                WHERE a3.id_cliente = a.id_cliente 
                  AND a3.id_negocio = a.id_negocio 
                  AND a3.data_hora_inicio >= :hoje
            )
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['hoje' => $hoje]);
    $clientes_para_avisar = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Encontrados " . count($clientes_para_avisar) . " clientes para convidar de volta.\n";

    // 2. Configura o PHPMailer (apenas uma vez)
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'agilizaagendas@gmail.com'; // SEU EMAIL
    $mail->Password = 'zvre bwjq trft zwjp';      // SUA SENHA
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = 465;
    $mail->CharSet = 'UTF-8';
    $mail->setFrom('agilizaagendas@gmail.com', 'Agiliza - Seu Visual');

    // 3. Envia os e-mails
    foreach ($clientes_para_avisar as $aviso) {
        try {
            $mail->clearAddresses();
            $mail->addAddress($aviso['email_cliente'], $aviso['nome_cliente']);
            
            $primeiro_nome = explode(' ', $aviso['nome_cliente'])[0];
            $link_agendar = "http://localhost/Agiliza/agendamento.php?id_negocio=" . $aviso['id_negocio'];
            $btn_style = "background-color: #7E57C2; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block; margin-top: 15px;";

            $mail->Subject = "Ei $primeiro_nome, hora de renovar o visual?";
            $mail->Body = "
                <div style='font-family: Arial, sans-serif; color: #333; max-width: 600px;'>
                    <h2 style='color: #7E57C2;'>Olá, $primeiro_nome!</h2>
                    <p>Já se passaram <b>{$aviso['frequencia_retorno_dias']} dias</b> desde o seu último <b>{$aviso['nome_servico']}</b> no <b>{$aviso['nome_negocio']}</b>.</p>
                    <p>Para manter o visual em dia, essa é a hora perfeita para retornar!</p>
                    
                    <center>
                        <a href='$link_agendar' style='$btn_style'>Agendar Agora</a>
                    </center>
                    <br>
                    <p style='font-size: 0.9rem; color: #777;'>Esperamos vê-lo em breve!</p>
                </div>
            ";
            $mail->isHTML(true);
            $mail->send();
            
            echo "E-mail enviado para: " . $aviso['email_cliente'] . "\n";

        } catch (Exception $e) {
            echo "Erro ao enviar para " . $aviso['email_cliente'] . ": " . $mail->ErrorInfo . "\n";
        }
    }

} catch (PDOException $e) {
    echo "ERRO DE BANCO: " . $e->getMessage();
}
?>