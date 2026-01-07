<?php
// admin/imprimir_mensagem.php
session_start();
require_once __DIR__ . '/../backend/conexao.php';

if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    die("Acesso negado");
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if($id <= 0){ die("Pedido inválido"); }

// Busca pedido
$stmt = $conn->prepare("SELECT * FROM pedidos WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$pedido = $stmt->get_result()->fetch_assoc();
$stmt->close();
if(!$pedido) die("Pedido não encontrado");

// Busca itens
$stmt2 = $conn->prepare("SELECT i.*, p.nome 
                         FROM itens_pedido i
                         LEFT JOIN produtos p ON p.id = i.produto_id
                         WHERE i.pedido_id=?");
$stmt2->bind_param("i", $id);
$stmt2->execute();
$itens = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt2->close();

// Monta texto parecido com o que vai para o WhatsApp
$mensagem = "🍔 Mimoso Lanches\n";
$mensagem .= "Pedido #{$pedido['id']}\n";
$mensagem .= "Cliente: {$pedido['cliente_nome']}\n";
$mensagem .= "Endereço: {$pedido['cliente_endereco']}\n";
$mensagem .= "Pagamento: {$pedido['forma_pagamento']}\n";
$mensagem .= "---------------------------\n";
foreach($itens as $i){
    $mensagem .= "{$i['quantidade']}x {$i['nome']} — R$ ".number_format($i['preco']*$i['quantidade'],2,",",".")."\n";
}
$mensagem .= "---------------------------\n";
$mensagem .= "Total: R$ ".number_format($pedido['total'],2,",",".")."\n";
$mensagem .= "Status: {$pedido['status']}\n";
$mensagem .= "---------------------------\n";
$mensagem .= date("d/m/Y H:i", strtotime($pedido['data']))."\n";
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Mensagem Pedido #<?=$id?></title>
  <style>
    body {
      font-family: monospace, sans-serif;
      font-size: 13px;
      margin: 0;
      padding: 5px;
      width: 58mm; /* troque para 80mm se sua impressora for 80mm */
      color:#000;
    }
    pre { white-space: pre-wrap; }
    button { margin:10px 0; padding:5px 10px; }
    @media print {
      @page { size: 58mm auto; margin: 3mm; }
      body { width:58mm; margin:0; padding:0; font-size:13px; }
      button { display:none; }
    }
  </style>
</head>
<body>
  <button onclick="window.print()">🖨 Imprimir</button>
  <pre><?=htmlspecialchars($mensagem)?></pre>

  <script>
    window.onload = ()=>{ window.print(); }
  </script>
</body>
</html>
