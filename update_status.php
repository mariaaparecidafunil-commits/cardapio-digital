<?php
// admin/update_status.php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
  echo json_encode(['ok'=>false,'msg'=>'Acesso negado']);
  exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data) {
  echo json_encode(['ok'=>false,'msg'=>'Requisição inválida']);
  exit;
}

$pedido_id = isset($data['pedido_id']) ? (int)$data['pedido_id'] : 0;
$tipo = $data['tipo'] ?? '';
$valor = $data['valor'] ?? '';

if (!$pedido_id || !$tipo || $valor === '') {
  echo json_encode(['ok'=>false,'msg'=>'Dados incompletos']);
  exit;
}

require_once __DIR__ . '/../backend/conexao.php';

if ($tipo === 'pedido') {
  $stmt = $conn->prepare("UPDATE pedidos SET status=? WHERE id=?");
  $stmt->bind_param("si", $valor, $pedido_id);
  if ($stmt->execute()) {
    echo json_encode(['ok'=>true]);
  } else {
    echo json_encode(['ok'=>false,'msg'=>'Erro ao atualizar pedido']);
  }
  exit;
}

if ($tipo === 'pagamento') {
  // atualiza a tabela pagamentos (se houver um registro) ou cria se não existir
  $stmtCheck = $conn->prepare("SELECT id FROM pagamentos WHERE pedido_id=? LIMIT 1");
  $stmtCheck->bind_param("i", $pedido_id);
  $stmtCheck->execute();
  $row = $stmtCheck->get_result()->fetch_assoc();
  if ($row) {
    $stmt = $conn->prepare("UPDATE pagamentos SET status=? WHERE id=?");
    $stmt->bind_param("si", $valor, $row['id']);
    if ($stmt->execute()) {
      echo json_encode(['ok'=>true]);
    } else {
      echo json_encode(['ok'=>false,'msg'=>'Erro ao atualizar pagamento']);
    }
  } else {
    // criar registro mínimo (valor 0 se não tiver)
    $stmtInsert = $conn->prepare("INSERT INTO pagamentos (pedido_id, metodo, status, valor) VALUES (?, 'Desconhecido', ?, 0)");
    $stmtInsert->bind_param("is", $pedido_id, $valor);
    if ($stmtInsert->execute()) {
      echo json_encode(['ok'=>true]);
    } else {
      echo json_encode(['ok'=>false,'msg'=>'Erro ao criar pagamento']);
    }
  }
  exit;
}

echo json_encode(['ok'=>false,'msg'=>'Tipo inválido']);
