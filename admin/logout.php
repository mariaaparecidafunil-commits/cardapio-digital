<?php
// admin/logout.php
session_start();

// destruir todos os dados da sessão
session_unset();
session_destroy();

// redirecionar de volta para o login com mensagem
header("Location: /admin/login.php?err=logout");
exit;
