<?php
session_start();
require 'conexao.php';

// --- Porteiro ---
if (!isset($_SESSION['usuario_id']) || $_SERVER["REQUEST_METHOD"] != "POST") {
    die("Acesso negado.");
}

// [CORREÇÃO] A variável correta é '$id_usuario'
$id_usuario = $_SESSION['usuario_id']; 

$action = $_POST['action'] ?? 'salvar_perfil';

try {
    // --- AÇÃO 1: MUDAR A SENHA ---
    if ($action == 'mudar_senha') {
        
        $senha_atual = $_POST['senha_atual'];
        $nova_senha = $_POST['nova_senha'];
        $confirmar_nova_senha = $_POST['confirmar_nova_senha'];

        // 1. Busca o HASH da senha atual
        $stmt_check = $pdo->prepare("SELECT senha FROM usuarios WHERE id = :id");
        $stmt_check->execute(['id' => $id_usuario]); // [CORREÇÃO] Usando $id_usuario
        $usuario = $stmt_check->fetch(PDO::FETCH_ASSOC);

        // 2. Verifica se a senha atual está correta
        if (!$usuario || !password_verify($senha_atual, $usuario['senha'])) {
            $_SESSION['erro_senha'] = "A sua Senha Atual está incorreta.";
            header("Location: meu_perfil.php");
            exit();
        }

        // 3. Verifica as regras da nova senha
        if (strlen($nova_senha) < 6) { $_SESSION['erro_senha'] = "A Nova Senha deve ter pelo menos 6 caracteres."; header("Location: meu_perfil.php"); exit(); }
        if (!preg_match('/[A-Z]/', $nova_senha)) { $_SESSION['erro_senha'] = "A Nova Senha deve ter pelo menos 1 letra maiúscula."; header("Location: meu_perfil.php"); exit(); }
        if (!preg_match('/[0-9]/', $nova_senha)) { $_SESSION['erro_senha'] = "A Nova Senha deve ter pelo menos 1 número."; header("Location: meu_perfil.php"); exit(); }
        if ($nova_senha !== $confirmar_nova_senha) { $_SESSION['erro_senha'] = "A Nova Senha e a Confirmação não coincidem."; header("Location: meu_perfil.php"); exit(); }

        // 4. Salva a nova senha
        $nova_senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
        $stmt_update = $pdo->prepare("UPDATE usuarios SET senha = :senha WHERE id = :id");
        $stmt_update->execute(['senha' => $nova_senha_hash, 'id' => $id_usuario]); // [CORREÇÃO] Usando $id_usuario

        $_SESSION['sucesso_senha'] = "Senha alterada com sucesso!";
        header("Location: meu_perfil.php");
        exit();
    }

    // --- AÇÃO 2: SALVAR PERFIL (Nome e Foto) ---
    if ($action == 'salvar_perfil') {
        
        $stmt_current = $pdo->prepare("SELECT nome, email, foto_perfil_url FROM usuarios WHERE id = :id");
        $stmt_current->execute(['id' => $id_usuario]); // [CORREÇÃO] Usando $id_usuario
        $usuario_atual = $stmt_current->fetch(PDO::FETCH_ASSOC);

        $novo_nome = $_POST['nome'];
        $nova_foto_url = $usuario_atual['foto_perfil_url'];

        // 1. [NOVO] Checar se o E-mail já existe (e não é o dele mesmo)
        // (Esta lógica não estava no script do funcionário)
        if (isset($_POST['email']) && $_POST['email'] != $usuario_atual['email']) {
            $novo_email = $_POST['email'];
            $stmt_email = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE email = :email");
            $stmt_email->execute(['email' => $novo_email]);
            if ($stmt_email->fetchColumn() > 0) {
                $_SESSION['erro_perfil'] = "Erro: Este e-mail já está em uso por outra conta.";
                header("Location: meu_perfil.php");
                exit();
            }
        } else {
            $novo_email = $usuario_atual['email']; // Mantém o email antigo
        }


        // 2. Processa a FOTO DE PERFIL
        if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] == UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/'; 
            
            // [CORREÇÃO] Usando $id_usuario para o nome do arquivo
            $file_name = uniqid('user_' . $id_usuario . '_') . '.' . strtolower(pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION));
            $target_file = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $target_file)) {
                $nova_foto_url = 'uploads/' . $file_name; 
            } else {
                $_SESSION['erro_perfil'] = "Erro ao fazer upload da foto.";
                header("Location: meu_perfil.php");
                exit();
            }
        }

        // 3. Atualiza o banco
        $stmt_update = $pdo->prepare(
            "UPDATE usuarios SET 
                nome = :nome, 
                email = :email,
                foto_perfil_url = :foto 
            WHERE id = :id"
        );
        $stmt_update->execute([
            'nome' => $novo_nome,
            'email' => $novo_email,
            'foto' => $nova_foto_url,
            'id' => $id_usuario // [CORREÇÃO] Usando $id_usuario
        ]);
        
        // 4. Atualiza a SESSÃO
        $_SESSION['usuario_nome'] = $novo_nome;
        $_SESSION['usuario_foto'] = $nova_foto_url;

        // Sucesso!
        header("Location: meu_perfil.php?sucesso=1");
        exit();
    }

} catch (PDOException $e) {
    $_SESSION['erro_perfil'] = "Erro no banco de dados: " . $e->getMessage();
    header("Location: meu_perfil.php");
    exit();
}
?>