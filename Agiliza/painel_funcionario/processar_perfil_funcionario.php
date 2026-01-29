<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require '../conexao.php';

// --- Porteiro ---
if (!isset($_SESSION['usuario_id'])) {
    die("Acesso negado.");
}

// [NOVO] Verifica quem está editando
$e_dono = ($_SESSION['funcao'] == 'dono');
// [NOVO] Define para onde voltar em caso de erro/sucesso
$pagina_retorno = $e_dono ? '../painel_dono/meu_perfil.php' : 'meu_perfil.php';


$id_funcionario = $_SESSION['usuario_id']; // Pega o ID de quem está logado
$action = $_POST['action'] ?? 'salvar_perfil';

try {
    // --- AÇÃO 1: MUDAR A SENHA ---
    if ($action == 'mudar_senha') {
        
        $senha_atual = $_POST['senha_atual'];
        $nova_senha = $_POST['nova_senha'];
        $confirmar_nova_senha = $_POST['confirmar_nova_senha'];

        $stmt_check = $pdo->prepare("SELECT senha FROM usuarios WHERE id = :id");
        $stmt_check->execute(['id' => $id_funcionario]);
        $usuario = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if (!$usuario || !password_verify($senha_atual, $usuario['senha'])) {
            $_SESSION['erro_senha'] = "A sua Senha Atual está incorreta.";
            header("Location: $pagina_retorno");
            exit();
        }

        if (strlen($nova_senha) < 6) { $_SESSION['erro_senha'] = "A Nova Senha deve ter pelo menos 6 caracteres."; header("Location: $pagina_retorno"); exit(); }
        if (!preg_match('/[A-Z]/', $nova_senha)) { $_SESSION['erro_senha'] = "A Nova Senha deve ter pelo menos 1 letra maiúscula."; header("Location: $pagina_retorno"); exit(); }
        if (!preg_match('/[0-9]/', $nova_senha)) { $_SESSION['erro_senha'] = "A Nova Senha deve ter pelo menos 1 número."; header("Location: $pagina_retorno"); exit(); }
        if ($nova_senha !== $confirmar_nova_senha) { $_SESSION['erro_senha'] = "A Nova Senha e a Confirmação não coincidem."; header("Location: $pagina_retorno"); exit(); }

        $nova_senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
        $stmt_update = $pdo->prepare("UPDATE usuarios SET senha = :senha WHERE id = :id");
        $stmt_update->execute(['senha' => $nova_senha_hash, 'id' => $id_funcionario]);

        $_SESSION['sucesso_senha'] = "Senha alterada com sucesso!";
        header("Location: $pagina_retorno");
        exit();
    }

    // --- AÇÃO 2: SALVAR PERFIL (Nome, Email, Foto) ---
    if ($action == 'salvar_perfil') {
        
        $stmt_current = $pdo->prepare("SELECT nome, email, foto_perfil_url FROM usuarios WHERE id = :id");
        $stmt_current->execute(['id' => $id_funcionario]);
        $usuario_atual = $stmt_current->fetch(PDO::FETCH_ASSOC);

        $novo_nome = $_POST['nome'];
        $novo_email = $_POST['email'];
        $nova_foto_url = $usuario_atual['foto_perfil_url'];

        if ($novo_email != $usuario_atual['email']) {
            $stmt_email = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE email = :email");
            $stmt_email->execute(['email' => $novo_email]);
            if ($stmt_email->fetchColumn() > 0) {
                $_SESSION['erro_perfil'] = "Erro: Este e-mail já está em uso por outra conta.";
                header("Location: $pagina_retorno");
                exit();
            }
        }

        if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] == UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/'; 
            $file_name = uniqid('user_' . $id_funcionario . '_') . '.' . strtolower(pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION));
            $target_file = $upload_dir . $file_name;
            if (move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $target_file)) {
                $nova_foto_url = 'uploads/' . $file_name; 
            } else {
                $_SESSION['erro_perfil'] = "Erro ao fazer upload da foto.";
                header("Location: $pagina_retorno");
                exit();
            }
        }

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
            'id' => $id_funcionario
        ]);
        
        $_SESSION['usuario_nome'] = $novo_nome;
        $_SESSION['usuario_foto'] = $nova_foto_url;
        
        header("Location: $pagina_retorno?sucesso=1");
        exit();
    }

} catch (PDOException $e) {
    $_SESSION['erro_perfil'] = "Erro no banco de dados: " . $e->getMessage();
    header("Location: $pagina_retorno");
    exit();
}
?>