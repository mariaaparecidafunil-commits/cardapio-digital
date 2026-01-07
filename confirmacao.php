<?php
// confirmacao.php
require_once __DIR__ . '/backend/conexao.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Pedido inválido.");
}
$pedido_id = (int)$_GET['id'];

// Busca pedido
$stmt = $conn->prepare("SELECT * FROM pedidos WHERE id=? LIMIT 1");
$stmt->bind_param("i", $pedido_id);
$stmt->execute();
$pedido = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pedido) {
    die("Pedido não encontrado.");
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Confirmação do Pedido - Mimoso Lanches</title>
  <style>
    body { font-family: Arial, sans-serif; background:#f4f4f9; margin:0; padding:20px; }
    .box { max-width:600px; margin:auto; background:#fff; padding:20px; border-radius:10px; box-shadow:0 0 15px rgba(0,0,0,0.1); text-align:center; }
    h1 { color:green; }
    .pedido { margin:20px 0; font-size:18px; }
    .link { margin-top:20px; font-size:16px; }
    .link a { color:#d35400; text-decoration:none; font-weight:bold; }
    .link a:hover { text-decoration:underline; }
    footer { text-align:center; font-size:13px; color:#888; margin-top:30px; }
  </style>
</head>
<body>
  <div class="box">
    <h1>✅ Pedido realizado com sucesso!</h1>
    <div class="pedido">
      <p><strong>Nº do pedido:</strong> <?= $pedido['id'] ?></p>
      <p><strong>Cliente:</strong> <?= htmlspecialchars($pedido['cliente_nome']) ?></p>
      <p><strong>Total:</strong> R$ <?= number_format($pedido['total'],2,",",".") ?></p>
    </div>

    <div class="link">
      👉 <a href="status.php?id=<?= $pedido['id'] ?>">Acompanhe seu pedido em tempo real</a>
    </div>
  </div>
  <footer>
    © <?= date("Y") ?> Mimoso Lanches - Todos os direitos reservados
  </footer>
</body>
</html>
