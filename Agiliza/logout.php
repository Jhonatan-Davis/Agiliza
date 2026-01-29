<?php
// 1. Sempre inicie a sessão
//    Precisamos disso para ter acesso às variáveis de sessão e poder limpá-las.
session_start();

// 2. Limpa todas as variáveis da sessão
//    Isso "esvazia" o array da sessão.
session_unset();

// 3. Destrói a sessão
//    Isso "mata" a sessão no servidor.
session_destroy();

// 4. Redireciona o usuário para a página de login
//    (Ajustei o caminho para apontar para a sua pasta de login)
header("Location: /Agiliza/login/login.php?status=logout");
exit();

?>