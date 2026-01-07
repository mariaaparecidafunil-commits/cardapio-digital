<?php
// admin/get_pedido.php
session_start();
require_once __DIR__ . '/../backend/conexao.php';

header("Content-Type: application/json; charset=utf-8");

if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    http_response_code(403);
    echo json_encode(["erro" => "não autorizado"]);
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    echo json_encode([]);
    exit;
}

$sql = "SELECT id, cliente_nome, cliente_endereco, forma_pagamento, total, status
        FROM pedidos
        WHERE id = $id
        LIMIT 1";
$res = $conn->query($sql);
$pedido = $res ? $res->fetch_assoc() : null;

echo json_encode($pedido ?: []);
