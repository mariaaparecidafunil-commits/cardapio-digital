<?php
require_once __DIR__ . '/backend/conexao.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) die("Pedido inválido.");

// Buscar pedido
$stmt = $conn->prepare("SELECT * FROM pedidos WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$pedido = $stmt->get_result()->fetch_assoc();

if (!$pedido) die("Pedido não encontrado.");

// Buscar itens
$stmt2 = $conn->prepare("SELECT i.*, p.nome 
                         FROM itens_pedido i 
                         JOIN produtos p ON i.produto_id=p.id 
                         WHERE i.pedido_id=?");
$stmt2->bind_param("i", $id);
$stmt2->execute();
$itens = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Meu Pedido #<?=$pedido['id']?> - Mimoso Lanches</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body{font-family:Arial,Helvetica,sans-serif;background:#f8fafc;margin:0;padding:20px}
    .box{max-width:600px;margin:0 auto;background:#fff;padding:20px;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,.08)}
    h1{margin-top:0;font-size:20px;color:#111827}
    .status{padding:10px;border-radius:8px;margin:10px 0;font-weight:bold}
    .Em\.preparo{background:#fde68a;color:#92400e}
    .Saiu\.para\.entrega{background:#bfdbfe;color:#1e3a8a}
    .Entregue{background:#bbf7d0;color:#166534}
    table{width:100%;border-collapse:collapse;margin-top:12px}
    td,th{padding:8px;border-bottom:1px solid #ddd}
    .total{font-weight:bold;text-align:right;margin-top:12px}
  </style>
</head>
<body>
<div class="box">
  <h1>🍔 Pedido #<?=$pedido['id']?> - Mimoso Lanches</h1>
  <p><b>Cliente:</b> <?=$pedido['cliente_nome']?></p>
  <p><b>Endereço:</b> <?=$pedido['cliente_endereco']?></p>
  <p><b>Pagamento:</b> <?=$pedido['forma_pagamento']?></p>
  <div class="status <?=$pedido['status']?>">Status: <?=$pedido['status']?></div>

  <h2>Itens</h2>
  <table>
    <tr><th>Qtd</th><th>Produto</th><th>Preço</th></tr>
    <?php foreach($itens as $i): ?>
      <tr>
        <td><?=$i['quantidade']?></td>
        <td><?=$i['nome']?></td>
        <td>R$ <?=number_format($i['preco']*$i['quantidade'],2,',','.')?></td>
      </tr>
    <?php endforeach; ?>
  </table>
  <p class="total">Total: R$ <?=number_format($pedido['total'],2,',','.')?></p>
  <p><i>Atualizado em <?=date("d/m/Y H:i", strtotime($pedido['data']))?></i></p>
</div>
</body>
</html>
