<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Se já estiver logado, vai direto para o painel
if (isset($_SESSION['admin_id'])) {
  header('Location: /admin/index.php');
  exit;
}

// Captura se veio erro ou logout
$err = isset($_GET['err']) ? $_GET['err'] : '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Login - Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <style>
    body {
      margin: 0;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #e6f0fa; /* fundo azul clarinho */
      font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
    }
    .login-box {
      background: #fff;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 6px 20px rgba(0,0,0,0.15);
      width: 100%;
      max-width: 360px;
    }
    h2 {
      margin: 0 0 20px;
      text-align: center;
      color: #1a73e8;
    }
    .msg {
      margin-bottom: 12px;
      padding: 10px;
      border-radius: 6px;
      font-weight: bold;
      text-align: center;
    }
    .msg.error {
      background: #fdecea;
      color: #d93025;
      border: 1px solid #f5c6cb;
    }
    .msg.success {
      background: #e6f4ea;
      color: #188038;
      border: 1px solid #c3e6cb;
    }
    label {
      display: block;
      font-size: 14px;
      margin-top: 12px;
      color: #333;
    }
    input {
      width: 100%;
      padding: 12px;
      border: 1px solid #ddd;
      border-radius: 6px;
      margin-top: 6px;
      font-size: 14px;
    }
    button {
      width: 100%;
      padding: 12px;
      margin-top: 20px;
      border: 0;
      border-radius: 6px;
      background: #1a73e8;
      color: #fff;
      font-weight: bold;
      font-size: 15px;
      cursor: pointer;
      transition: background 0.3s;
    }
    button:hover {
      background: #1669c1;
    }
  </style>
</head>
<body>
  <div class="login-box">
    <h2>Área Administrativa</h2>

    <?php if ($err): ?>
      <?php if ($err === '1'): ?>
        <div class="msg error">Usuário ou senha inválidos.</div>
      <?php elseif ($err === 'logout'): ?>
        <div class="msg success">Sessão encerrada com sucesso.</div>
      <?php endif; ?>
    <?php endif; ?>

    <form method="post" action="login_check.php" autocomplete="off">
      <label for="login">Usuário</label>
      <input type="text" id="login" name="login" required />

      <label for="senha">Senha</label>
      <input type="password" id="senha" name="senha" required />

      <button type="submit">Entrar</button>
    </form>
  </div>
</body>
</html>
