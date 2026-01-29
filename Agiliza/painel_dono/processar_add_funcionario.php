<?php
session_start();
require '../conexao.php';

// --- Porteiro ---
if (!isset($_SESSION['usuario_id']) || $_SESSION['funcao'] != 'dono') {
    die("Acesso negado.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $id_negocio = $_SESSION['id_negocio'];
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    // 1. Validar senha (mínimo 6 chars)
    if (strlen($senha) < 6) {
        die("Erro: A senha deve ter pelo menos 6 caracteres.");
    }

    // 2. Criptografar a senha
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

    // 3. Iniciar Transação (Vamos inserir em 2 tabelas)
    try {
        $pdo->beginTransaction();

        // 3a. Verificar se o e-mail JÁ EXISTE
        $stmt_check = $pdo->prepare("SELECT id FROM usuarios WHERE email = :email");
        $stmt_check->execute(['email' => $email]);
        $usuario_existente = $stmt_check->fetch();

        $id_novo_usuario = null;

        if ($usuario_existente) {
            // O usuário já existe (ex: ele era cliente).
            // Vamos apenas "promovê-lo" a funcionário DESTE negócio.
            $id_novo_usuario = $usuario_existente['id'];
        } else {
            // O usuário não existe. Vamos criar.
            $stmt_user = $pdo->prepare(
                "INSERT INTO usuarios (nome, email, senha, telefone) 
                 VALUES (:nome, :email, :senha, NULL)"
            );
            $stmt_user->execute([
                'nome' => $nome,
                'email' => $email,
                'senha' => $senha_hash
            ]);
            $id_novo_usuario = $pdo->lastInsertId();
        }

        // 3b. Ligar este usuário ao negócio como "funcionário"
        $stmt_membro = $pdo->prepare(
            "INSERT INTO negocio_membros (id_usuario, id_negocio, funcao)
             VALUES (:id_usuario, :id_negocio, 'funcionario')"
        );
        $stmt_membro->execute([
            'id_usuario' => $id_novo_usuario,
            'id_negocio' => $id_negocio
        ]);

        // 4. Sucesso!
        $pdo->commit();
        header("Location: minha_equipe.php?sucesso=1");
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        // Erro 23000 (Integrity constraint) = usuário já é membro
        if ($e->getCode() == 23000) {
            die("Erro: Este usuário já faz parte da sua equipe ou de outra.");
        }
        die("Erro ao adicionar funcionário: " . $e->getMessage());
    }
}
?>