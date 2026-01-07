<?php
session_start();
require_once __DIR__ . '/../backend/conexao.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) die("ID inválido.");
$id = (int)$_GET['id'];

$stmt = $conn->prepare("SELECT * FROM pedidos WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$pedido = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$pedido) die("Pedido não encontrado.");

$stmt2 = $conn->prepare("SELECT i.*, p.nome 
                         FROM itens_pedido i
                         LEFT JOIN produtos p ON i.produto_id = p.id
                         WHERE i.pedido_id=?");
$stmt2->bind_param("i", $id);
$stmt2->execute();
$itens = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt2->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Pedido #<?=htmlspecialchars($pedido['id'])?></title>
<style>
  body{
    font-family: Arial, sans-serif;
    font-size: 12px;
    margin:0;
    padding:0;
  }
  h1{font-size:14px;text-align:center;margin:2px 0;}
  .center{text-align:center;}
  .line{border-top:1px dashed #000;margin:4px 0;}
  table{width:100%;border-collapse:collapse;}
  td{padding:2px 0;vertical-align:top;}
  .right{text-align:right;}
  .total{font-weight:bold;font-size:13px;margin-top:4px;}
  @media print {button{display:none}}
</style>
</head>
<body>
<h1>Mimoso Lanches</h1>
<div class="center">🍔 Pedido #<?=htmlspecialchars($pedido['id'])?></div>
<div class="line"></div>

<p><b>Cliente:</b> <?=htmlspecialchars($pedido['cliente_nome'])?></p>
<?php if(!empty($pedido['telefone'])): ?><p><b>Tel:</b> <?=htmlspecialchars($pedido['telefone'])?></p><?php endif;?>
<p><b>End:</b> <?=htmlspecialchars($pedido['cliente_endereco'])?></p>
<p><b>Pagamento:</b> <?=htmlspecialchars($pedido['forma_pagamento'])?></p>
<p><b>Status:</b> <?=htmlspecialchars($pedido['status'])?></p>

<div class="line"></div>
<table>
<?php foreach($itens as $i): ?>
<tr>
  <td><?=intval($i['quantidade'])?>x</td>
  <td>
    <?=htmlspecialchars($i['nome'])?>
    <?php
      $extras=[];
      if(!empty($i['adicionais'])) $extras[]="acrescentar: ".htmlspecialchars($i['adicionais']);
      if(!empty($i['remocoes'])) $extras[]="sem: ".htmlspecialchars($i['remocoes']);
      if(!empty($i['observacao'])) $extras[]="Obs: ".htmlspecialchars($i['observacao']);
      if($extras) echo "<br><small>".implode(" | ",$extras)."</small>";
    ?>
  </td>
  <td class="right">R$ <?=number_format($i['preco']*$i['quantidade'],2,",",".")?></td>
</tr>
<?php endforeach;?>
</table>

<div class="line"></div>
<p class="total">Total: R$ <?=number_format($pedido['total'],2,",",".")?></p>
<div class="center"><?=date("d/m/Y H:i",strtotime($pedido['data']))?></div>
<div class="line"></div>
<div class="center">Obrigado pela preferência!</div>

<button onclick="window.print()">🖨 Imprimir</button>

<script>
window.onload = ()=>{window.print();};
</script>
</body>
</html>
