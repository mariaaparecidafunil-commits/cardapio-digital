<?php
require_once __DIR__ . '/backend/conexao.php';

$pedidoId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if($pedidoId <= 0){
    die("<h2>Pedido não encontrado</h2>");
}

// Busca pedido
$stmt = $conn->prepare("SELECT * FROM pedidos WHERE id=?");
$stmt->bind_param("i", $pedidoId);
$stmt->execute();
$pedido = $stmt->get_result()->fetch_assoc();
if(!$pedido){
    die("<h2>Pedido não encontrado</h2>");
}

// Busca itens do pedido
$itens = [];
$resItens = $conn->prepare("SELECT * FROM itens_pedido WHERE pedido_id=?");
$resItens->bind_param("i", $pedidoId);
$resItens->execute();
$q = $resItens->get_result();
while($row=$q->fetch_assoc()){ $itens[]=$row; }

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Acompanhar Pedido #<?=$pedidoId?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body{background:#f8fafc;padding:20px;}
    .status{font-size:1.2rem;font-weight:bold;}
    .card{margin-bottom:20px;}
  </style>
</head>
<body>
<div class="container">
  <h3>🍔 Mimoso Lanches</h3>
  <h5>Pedido #<?=$pedidoId?></h5>

  <div class="card">
    <div class="card-body">
      <p><strong>Cliente:</strong> <?=htmlspecialchars($pedido['cliente_nome'])?></p>
      <p><strong>Endereço:</strong> <?=htmlspecialchars($pedido['cliente_endereco'])?></p>
      <p><strong>Pagamento:</strong> <?=htmlspecialchars($pedido['forma_pagamento'])?></p>
      <p><strong>Total:</strong> R$ <?=number_format($pedido['total'],2,',','.')?></p>
      <p class="status">📦 Status: <span id="status"><?=htmlspecialchars($pedido['status'])?></span></p>
    </div>
  </div>

  <div class="card">
    <div class="card-header">Itens do Pedido</div>
    <ul class="list-group list-group-flush">
      <?php foreach($itens as $i): ?>
        <li class="list-group-item">
          <?=$i['qtd']?>x <?=htmlspecialchars($i['nome'])?> — R$ <?=number_format($i['preco'],2,',','.')?>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>

  <p class="text-muted">A página atualiza automaticamente a cada 10 segundos.</p>
</div>

<script>
// Atualiza status a cada 10s
setInterval(()=>{
  fetch("pedido_status_api.php?id=<?=$pedidoId?>")
    .then(r=>r.json())
    .then(d=>{
       if(d.status){
         document.getElementById('status').innerText = d.status;
       } else if(d.erro){
         console.warn(d.erro);
       }
    })
    .catch(err=>console.error('Erro ao atualizar status', err));
},10000);
</script>
</body>
</html>
