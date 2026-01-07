<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/../backend/conexao.php';

// Se não estiver logado, volta para login
if (!isset($_SESSION['admin_id'])) {
    header('Location: /admin/login.php');
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header('Location: produtos.php');
    exit;
}

// Buscar produto
$stmt = $conn->prepare("SELECT * FROM produtos WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$produto = $res->fetch_assoc();
$stmt->close();

if (!$produto) {
    header('Location: produtos.php');
    exit;
}

// Processar exclusão
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirm']) && $_POST['confirm'] === 'sim') {
        $stmt = $conn->prepare("DELETE FROM produtos WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            header('Location: produtos.php?msg=deleted');
            exit;
        } else {
            $msg = "❌ Erro ao excluir produto: " . $conn->error;
        }
        $stmt->close();
    } else {
        header('Location: produtos.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Excluir Produto - Mimoso Lanches</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <style>
    body {
      margin: 0;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #e6f0fa;
      font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
    }
    .panel {
      background: #fff;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 6px 20px rgba(0,0,0,0.15);
      width: 100%;
      max-width: 500px;
      text-align: center;
    }
    h1 {
      margin-top: 0;
      color: #d93025;
    }
    p {
      font-size: 16px;
      margin: 20px 0;
    }
    .msg {
      margin-bottom: 15px;
      padding: 10px;
      border-radius: 6px;
      font-weight: bold;
      text-align: center;
      background: #fdecea;
      color: #d93025;
      border: 1px solid #f5c6cb;
    }
    button {
      padding: 12px 20px;
      margin: 10px;
      border: 0;
      border-radius: 6px;
      font-weight: bold;
      cursor: pointer;
      font-size: 15px;
    }
    .yes {
      background: #d93025;
      color: #fff;
    }
    .yes:hover {
      background: #b1271e;
    }
    .no {
      background: #6c757d;
      color: #fff;
    }
    .no:hover {
      background: #555;
    }
  </style>
</head>
<body>
  <div class="panel">
    <h1>🗑️ Excluir Produto</h1>
    <?php if ($msg): ?>
      <div class="msg"><?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>
    
    <p>Tem certeza que deseja excluir o produto <strong><?php echo htmlspecialchars($produto['nome']); ?></strong>?</p>

    <form method="post">
      <button type="submit" name="confirm" value="sim" class="yes">Sim, excluir</button>
      <button type="submit" name="confirm" value="nao" class="no">Cancelar</button>
    </form>
  </div>
</body>
</html>
