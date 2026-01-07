<?php
// public_html/pedido_status.php
require_once __DIR__ . '/backend/conexao.php';

$pedido = null;
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $telefone = trim($_POST['telefone']);
  $id = intval($_POST['id']);
  $sql="SELECT * FROM pedidos WHERE 1";
  if($telefone) $sql.=" AND telefone=?";
  if($id) $sql.=" AND id=?";
  $stmt=$conn->prepare($sql);
  if($telefone && $id) $stmt->bind_param("si",$telefone,$id);
  elseif($telefone) $stmt->bind_param("s",$telefone);
  else $stmt->bind_param("i",$id);
  $stmt->execute();
  $pedido=$stmt->get_result()->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Acompanhar Pedido</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body{font-family:Arial,sans-serif;background:#f9fafb;margin:0;padding:20px}
h1{text-align:center}
form{max-width:400px;margin:20px auto;background:#fff;padding:20px;border-radius:10px;box-shadow:0 2px 6px rgba(0,0,0,.1)}
input,button{width:100%;padding:10px;margin-top:10px;border-radius:6px;border:1px solid #ccc}
button{background:#2563eb;color:#fff;border:none;cursor:pointer}
.card{max-width:400px;margin:20px auto;background:#fff;padding:20px;border-radius:10px;box-shadow:0 2px 6px rgba(0,0,0,.1)}
</style>
</head>
<body>
<h1>🔍 Acompanhar Pedido</h1>
<form method="post">
  <input type="number" name="id" placeholder="Número do pedido">
  <input type="tel" name="telefone" placeholder="Telefone usado no pedido">
  <button type="submit">Buscar</button>
</form>
<?php if($pedido): ?>
<div class="card">
  <h3>Pedido #<?=$pedido['id']?></h3>
  <p><b>Cliente:</b> <?=$pedido['cliente_nome']?></p>
  <p><b>Status:</b> <?=$pedido['status']?></p>
  <p><b>Total:</b> R$ <?=number_format($pedido['total'],2,",",".")?></p>
  <p><b>Data:</b> <?=date("d/m/Y H:i",strtotime($pedido['data']))?></p>
</div>
<?php elseif($_SERVER['REQUEST_METHOD']==='POST'): ?>
<p style="text-align:center;color:red">Pedido não encontrado.</p>
<?php endif; ?>
</body>
</html>
