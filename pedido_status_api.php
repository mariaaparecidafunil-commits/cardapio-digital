<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/backend/conexao.php';

$pedidoId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if($pedidoId <= 0){
    echo json_encode(['erro'=>'ID inválido']);
    exit;
}

$stmt = $conn->prepare("SELECT status FROM pedidos WHERE id=?");
$stmt->bind_param("i", $pedidoId);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

if($res){
    echo json_encode(['status'=>$res['status']]);
}else{
    echo json_encode(['erro'=>'Pedido não encontrado']);
}
