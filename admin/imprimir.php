<?php
session_start();
require_once __DIR__ . '/../backend/conexao.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) die("ID inválido.");
$id = (int)$_GET['id'];

// Pedido
$stmt = $conn->prepare("SELECT * FROM pedidos WHERE id=?");
$stmt->bind_param("i",$id);
$stmt->execute();
$res = $stmt->get_result();
$pedido = $res?$res->fetch_assoc():null;
$stmt->close();
if(!$pedido) die("Pedido não encontrado.");

// Itens
$stmt2 = $conn->prepare("SELECT i.*,p.nome FROM itens_pedido i
 LEFT JOIN produtos p ON p.id=i.produto_id WHERE i.pedido_id=?");
$stmt2->bind_param("i",$id);
$stmt2->execute();
$res2 = $stmt2->get_result();
$itens=[];
if($res2){while($r=$res2->fetch_assoc()){$itens[]=$r;}}
$stmt2->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Pedido #<?=$pedido['id']?></title>

<style>
/* ======== LAYOUT DE IMPRESSÃO PROFISSIONAL ======== */
body{
  font-family: "Arial", sans-serif;
  font-size: 13px;
  margin:0;
  padding:0 4px;
  width:58mm;
  color:#000;
}

h1{
  text-align:center;
  font-size:18px;
  margin:0;
  padding:0;
  font-weight:bold;
}

.subtitulo{
  text-align:center;
  margin-top:2px;
  font-size:14px;
}

hr{
  border:none;
  border-top:1px dashed #000;
  margin:6px 0;
}

.info{
  font-size:13px;
  line-height:16px;
}

.itens{
  margin-top:6px;
}

.item{
  margin-bottom:6px;
  font-size:13px;
}

.qtd{
  font-weight:bold;
}

.nome{
  display:inline-block;
  max-width:38mm;
  word-wrap:break-word;
}

.preco{
  float:right;
  font-weight:bold;
}

.obs{
  font-size:12px;
  margin-left:18px;
  color:#444;
}

.total{
  font-size:15px;
  font-weight:bold;
  text-align:right;
  margin-top:6px;
}

.rodape{
  text-align:center;
  margin-top:8px;
  font-size:12px;
  font-weight:bold;
}

.data{
  text-align:center;
  margin-top:4px;
  font-size:12px;
}

@media print {
  button{ display:none; }
  @page{ size:58mm auto; margin:2mm; }
}
</style>
</head>
<body>

<h1>Mimoso Lanches</h1>
<div class="subtitulo">🍔 Pedido #<?=$pedido['id']?></div>

<hr>

<div class="info">
  <b>Cliente:</b> <?=htmlspecialchars($pedido['cliente_nome'])?><br>
  <?php if(!empty($pedido['telefone'])): ?>
  <b>Tel:</b> <?=htmlspecialchars($pedido['telefone'])?><br>
  <?php endif; ?>
  <b>End:</b> <?=htmlspecialchars($pedido['cliente_endereco'])?><br>
  <b>Pagamento:</b> <?=htmlspecialchars($pedido['forma_pagamento'])?><br>
  <b>Status:</b> <?=htmlspecialchars($pedido['status'])?><br>
</div>

<hr>

<div class="itens">
<?php foreach($itens as $i): ?>
  <div class="item">
    <span class="qtd"><?=$i['quantidade']?>x</span>
    <span class="nome"><?=htmlspecialchars($i['nome'])?></span>
    <span class="preco">R$ <?=number_format($i['preco']*$i['quantidade'],2,",",".")?></span>
    <div style="clear:both;"></div>

    <?php
      $detalhes=[];
      if(!empty($i['adicionais'])) $detalhes[]="acrescenta: ".$i['adicionais'];
      if(!empty($i['remocoes'])) $detalhes[]="sem: ".$i['remocoes'];
      if(!empty($i['observacao'])) $detalhes[]="Obs: ".$i['observacao'];
      if($detalhes):
    ?>
      <div class="obs"><?=implode(" | ", $detalhes)?></div>
    <?php endif; ?>
  </div>
<?php endforeach; ?>
</div>

<hr>

<div class="total">
Total: R$ <?=number_format($pedido['total'],2,",",".")?>
</div>

<div class="data">
<?=date("d/m/Y H:i", strtotime($pedido['data']))?>
</div>

<hr>

<div class="rodape">
Obrigado pela preferência!<br>
Volte sempre! 😄
</div>

<button onclick="window.print()">🖨 Imprimir</button>

<script>
window.onload = ()=>{ window.print(); };
</script>

</body>
</html>
