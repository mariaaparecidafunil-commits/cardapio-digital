<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/../backend/conexao.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: /admin/login.php');
    exit;
}

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: pedidos.php');
    exit;
}

// Buscar pedido
$stmt = $conn->prepare("SELECT * FROM pedidos WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$pedido = $res->fetch_assoc();
$stmt->close();

if (!$pedido) {
    header('Location: pedidos.php');
    exit;
}

// Atualizar status
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'] ?? 'Novo';
    $stmt = $conn->prepare("UPDATE pedidos SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id);
    if ($stmt->execute()) {
        $msg = "✅ Status atualizado!";
        $pedido['status'] = $status;
    } else {
        $msg = "❌ Erro ao atualizar: " . $conn->error;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Atualizar Pedido - Mimoso Lanches</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <style>
    body {
      margin: 0; min-height: 100vh;
      display: flex; align-items: center; justify-content: center;
      background: #e6f0fa; font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
    }
    .panel {
      background: #fff; padding: 30px; border-radius: 12px;
      box-shadow: 0 6px 20px rgba(0,0,0,0.15);
      width: 100%; max-width: 500px;
    }
    h1 { text-align: center; color: #1a73e8; }
    .msg { margin-bottom: 15px; padding: 10px; border-radius: 6px; font-weight: bold; text-align: center; }
    .success { background: #e6f4ea; color: #188038; border: 1px solid #c3e6cb; }
    .error { background: #fdecea; color: #d93025; border: 1px solid #f5c6cb; }
    label { display: block; margin-top: 12px; font-weight: 500; }
    select { width: 100%; padding: 12px; margin-top: 6px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; }
    button { width: 100%; padding: 12px; margin-top: 20px; border: 0; border-radius: 6px; background: #1a73e8; color: #fff; font-weight: bold; cursor: pointer; }
    button:hover { background: #1669c1; }
    .back { display: inline-block; margin-top: 15px; padding: 8px 14px; border-radius: 6px; background: #6c757d; color: #fff; text-decoration: none; }
  </style>
</head>
<body>
  <div class="panel">
    <h1>✏️ Atualizar Pedido</h1>
    <?php if ($msg): ?>
      <div class="msg <?php echo strpos($msg,'✅')!==false ? 'success' : 'error'; ?>">
        <?php echo $msg; ?>
      </div>
    <?php endif; ?>
    <p><strong>Cliente:</strong> <?php echo htmlspecialchars($pedido['cliente_nome']); ?></p>
    <p><strong>Endereço:</strong> <?php echo htmlspecialchars($pedido['cliente_end']); ?></p>
    <p><strong>Total:</strong> R$ <?php echo number_format($pedido['total'],2,',','.'); ?></p>
    <form method="post">
      <label for="status">Status</label>
      <select name="status" id="status">
        <option value="Novo" <?php echo $pedido['status']==='Novo'?'selected':''; ?>>Novo</option>
        <option value="Em preparo" <?php echo $pedido['status']==='Em preparo'?'selected':''; ?>>Em preparo</option>
        <option value="Entregue" <?php echo $pedido['status']==='Entregue'?'selected':''; ?>>Entregue</option>
      </select>
      <button type="submit">Salvar Alterações</button>
    </form>
    <a href="pedidos.php" class="back">⬅ Voltar</a>
  </div>
</body>
</html>
