<?php
// admin/pedidos_novos.php
session_start();
require_once __DIR__ . '/../backend/conexao.php';

header("Content-Type: application/json; charset=utf-8");

if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    http_response_code(403);
    echo json_encode(["erro" => "não autorizado"]);
    exit;
}

$res = $conn->query("SELECT MAX(id) AS ultimo FROM pedidos");
$row = $res->fetch_assoc();

echo json_encode([
    "novoId" => $row && $row['ultimo'] ? (int)$row['ultimo'] : 0
]);
