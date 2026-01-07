<?php
// admin/delete_pedido.php
session_start();
require_once __DIR__ . '/../backend/conexao.php';

// Verifica se está logado como admin
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit;
}

// Verifica se veio via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: pedidos.php");
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
    die("ID do pedido inválido.");
}

// Excluir itens relacionados primeiro
$stmt = $conn->prepare("DELETE FROM itens_pedido WHERE pedido_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

// Excluir o pedido
$stmt = $conn->prepare("DELETE FROM pedidos WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

// Volta para a listagem
header("Location: pedidos.php?msg=excluido");
exit;
