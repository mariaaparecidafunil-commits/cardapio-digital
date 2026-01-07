<?php
// status_api.php
require_once __DIR__ . '/backend/conexao.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die(json_encode(['status'=>'inválido']));
}
$pedido_id = (int)$_GET['id'];

$stmt = $conn->prepare("SELECT status FROM pedidos WHERE id=? LIMIT 1");
$stmt->bind_param("i", $pedido_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$stmt->close();

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['status' => $res ? $res['status'] : 'desconhecido']);
