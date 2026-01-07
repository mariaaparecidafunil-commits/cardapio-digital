<?php
// admin/pedidos_editar.php
session_start();
require_once __DIR__ . '/../backend/conexao.php';

if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { die("Pedido inválido."); }

// Buscar dados atuais
$stmt = $conn->prepare("SELECT * FROM pedidos WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$pedido = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pedido) { die("Pedido não encontrado."); }

// Salvar alterações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['cliente_nome']);
    $telefone = trim($_POST['telefone']);
    $endereco = trim($_POST['cliente_endereco']);
    $forma = $_POST['forma_pagamento'];
    $total = floatval($_POST['total']);
    $status = $_POST['status'];

    $permitidos = ['Recebido','Em preparo','Saiu para entrega','Entregue','Cancelado'];
    if (!in_array($status, $permitidos)) $status = 'Recebido';

    $stmt = $conn->prepare("UPDATE pedidos SET cliente_nome=?, telefone=?, cliente_endereco=?, forma_pagamento=?, total=?, status=? WHERE id=?");
    $stmt->bind_param("ssssdsd", $nome, $telefone, $endereco, $forma, $total, $status, $id);
    $stmt->execute();
    $stmt->close();

    header("Location: pedidos.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Editar Pedido #<?=$pedido['id']?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body{font-family:Arial,sans-serif;margin:0;background:#f9fafb;color:#111}
    header{background:#1f2937;color:#fff;padding:16px;text-align:center}
    h1{margin:0;font-size:22px}
    .wrap{max-width:600px;margin:20px auto;padding:16px;background:#fff;border-radius:8px;border:1px solid #ddd}
    label{display:block;margin-top:10px;font-weight:bold}
    input,select{width:100%;padding:8px;border:1px solid #ddd;border-radius:6px;margin-top:6px}
    .actions{margin-top:16px;display:flex;gap:10px}
    .btn{padding:8px 12px;border:0;border-radius:6px;cursor:pointer}
    .btn-save{background:#2563eb;color:#fff}
    .btn-back{background:#6b7280;color:#fff;text-decoration:none;display:inline-block}
  </style>
</head>
<body>
<header><h1>✏️ Editar Pedido #<?=$pedido['id']?></h1></header>

<div class="wrap">
  <form method="post">
    <label>Cliente</label>
    <input type="text" name="cliente_nome" value="<?=htmlspecialchars($pedido['cliente_nome'])?>" required>

    <label>Telefone</label>
    <input type="text" name="telefone" value="<?=htmlspecialchars($pedido['telefone'])?>" required>

    <label>Endereço</label>
    <input type="text" name="cliente_endereco" value="<?=htmlspecialchars($pedido['cliente_endereco'])?>" required>

    <label>Forma de pagamento</label>
    <select name="forma_pagamento" required>
      <?php
        $formas = ['Dinheiro','Pix','Cartão'];
        foreach($formas as $f){
          $sel = ($pedido['forma_pagamento']===$f) ? 'selected' : '';
          echo "<option $sel>$f</option>";
        }
      ?>
    </select>

    <label>Total (R$)</label>
    <input type="number" step="0.01" name="total" value="<?=number_format($pedido['total'],2,'.','')?>" required>

    <label>Status</label>
    <select name="status">
      <?php
        $sts = ['Recebido','Em preparo','Saiu para entrega','Entregue','Cancelado'];
        foreach($sts as $s){
          $sel = ($pedido['status']===$s) ? 'selected' : '';
          echo "<option $sel>$s</option>";
        }
      ?>
    </select>

    <div class="actions">
      <button type="submit" class="btn btn-save">💾 Salvar</button>
      <a href="pedidos.php" class="btn btn-back">↩ Voltar</a>
    </div>
  </form>
</div>
</body>
</html>
