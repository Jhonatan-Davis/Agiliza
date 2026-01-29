<?php
// Define o tipo de resposta como JSON
header('Content-Type: application/json');

session_start();
require 'conexao.php';
date_default_timezone_set('America/Sao_Paulo');

// --- 1. Inclui os arquivos do PHPMailer (Instalação Manual) ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'src/Exception.php';
require 'src/PHPMailer.php';
require 'src/SMTP.php';

// --- 2. Porteiro (Segurança) ---
if (!isset($_SESSION['usuario_id']) || $_SERVER["REQUEST_METHOD"] != "POST") {
    echo json_encode(['sucesso' => false, 'erro' => 'Acesso negado.']);
    exit();
}

// --- 3. Coletar e Validar Dados ---
$id_cliente = $_SESSION['usuario_id'];
$id_negocio = $_POST['id_negocio'] ?? 0;
$id_servico = $_POST['id_servico'] ?? 0;
$id_funcionario = $_POST['id_funcionario'] ?? 0;
$data_hora_str = $_POST['data_hora_inicio'] ?? ''; 

if (empty($id_negocio) || empty($id_servico) || empty($data_hora_str)) {
    echo json_encode(['sucesso' => false, 'erro' => 'Dados incompletos.']);
    exit();
}

try {
    // --- 4. Buscar Dados para o E-mail e Cálculo ---
    $stmt_cliente = $pdo->prepare("SELECT nome, email FROM usuarios WHERE id = :id");
    $stmt_cliente->execute(['id' => $id_cliente]);
    $cliente = $stmt_cliente->fetch(PDO::FETCH_ASSOC);

    $stmt_servico = $pdo->prepare("SELECT nome_servico, duracao_minutos FROM servicos WHERE id = :id");
    $stmt_servico->execute(['id' => $id_servico]);
    $servico = $stmt_servico->fetch(PDO::FETCH_ASSOC);
    
    $id_para_notificar = $id_funcionario;
    if ($id_funcionario == 0) {
        $stmt_dono = $pdo->prepare("SELECT id_dono FROM negocios WHERE id = :id");
        $stmt_dono->execute(['id' => $id_negocio]);
        $id_para_notificar = $stmt_dono->fetchColumn();
    }
    
    $stmt_func = $pdo->prepare("SELECT nome, email FROM usuarios WHERE id = :id");
    $stmt_func->execute(['id' => $id_para_notificar]);
    $funcionario = $stmt_func->fetch(PDO::FETCH_ASSOC);

    // --- 5. Calcular Datas e Salvar no Banco ---
    $data_hora_inicio_obj = new DateTime($data_hora_str);
    $data_hora_fim_obj = (clone $data_hora_inicio_obj)->modify("+" . $servico['duracao_minutos'] . " minutes");

    $stmt_insert = $pdo->prepare(
        "INSERT INTO agendamentos (id_negocio, id_cliente, id_funcionario, id_servico, data_hora_inicio, data_hora_fim, status)
         VALUES (:id_negocio, :id_cliente, :id_funcionario, :id_servico, :inicio, :fim, 'confirmado')"
    );
    
    $stmt_insert->execute([
        'id_negocio' => $id_negocio,
        'id_cliente' => $id_cliente,
        'id_funcionario' => $id_para_notificar,
        'id_servico' => $id_servico,
        'inicio' => $data_hora_inicio_obj->format('Y-m-d H:i:s'),
        'fim' => $data_hora_fim_obj->format('Y-m-d H:i:s')
    ]);

    // --- 6. ENVIAR E-MAIL COM PHPMailer (CONFIGURADO) ---
    
    $mail = new PHPMailer(true); 

    try {
        // --- Configuração do Gmail ---
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'agilizaagendas@gmail.com'; // [SEU EMAIL]
        $mail->Password   = 'zvre bwjq trft zwjp';      // [SUA SENHA DE APP]
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        $mail->CharSet    = 'UTF-8';

        // --- E-mail para o Profissional ---
        $mail->setFrom('agilizaagendas@gmail.com', 'Agiliza - Novo Agendamento');
        $mail->addAddress($funcionario['email'], $funcionario['nome']);
        $mail->addReplyTo($cliente['email'], $cliente['nome']);

        $mail->isHTML(true);
        $mail->Subject = "Novo Agendamento: " . $servico['nome_servico'];
        $mail->Body    = "
            <h3>Olá, " . $funcionario['nome'] . "!</h3>
            <p>Um novo agendamento foi realizado:</p>
            <ul>
                <li><b>Cliente:</b> " . $cliente['nome'] . "</li>
                <li><b>Serviço:</b> " . $servico['nome_servico'] . "</li>
                <li><b>Data:</b> " . $data_hora_inicio_obj->format('d/m/Y') . "</li>
                <li><b>Hora:</b> " . $data_hora_inicio_obj->format('H:i') . "</li>
            </ul>
            <p>Acesse o painel Agiliza para ver sua agenda.</p>
        ";
        
        $mail->send();

        // --- E-mail para o Cliente ---
        $mail->clearAddresses(); 
        $mail->addAddress($cliente['email'], $cliente['nome']);
        
        $mail->Subject = "Agendamento Confirmado: " . $servico['nome_servico'];
        $mail->Body    = "
            <h3>Olá, " . $cliente['nome'] . "!</h3>
            <p>Seu agendamento foi confirmado com sucesso:</p>
            <ul>
                <li><b>Profissional:</b> " . $funcionario['nome'] . "</li>
                <li><b>Serviço:</b> " . $servico['nome_servico'] . "</li>
                <li><b>Data:</b> " . $data_hora_inicio_obj->format('d/m/Y') . "</li>
                <li><b>Hora:</b> " . $data_hora_inicio_obj->format('H:i') . "</li>
            </ul>
        ";
        
        $mail->send();

    } catch (Exception $e) {
        // Se o email falhar, não faz nada, mas o agendamento está salvo.
    }
    
    // --- 7. Sucesso ---
    echo json_encode(['sucesso' => true]);
    exit();

} catch (PDOException $e) {
    echo json_encode(['sucesso' => false, 'erro' => 'Erro no banco: ' . $e->getMessage()]);
    exit();
}
?>