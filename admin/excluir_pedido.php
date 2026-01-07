<?php
// admin/excluir_pedido.php
session_start();
require_once __DIR__ . '/../backend/conexao.php';

// Verifica login
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { die("ID inválido."); }

// Primeiro remove os itens vinculados
$stmt = $conn->prepare("DELETE FROM itens_pedido WHERE pedido_id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

// Agora remove o pedido
$stmt = $conn->prepare("DELETE FROM pedidos WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

// Opcional: se tiver tabela de pagamentos vinculada
$conn->query("DELETE FROM pagamentos WHERE pedido_id=$id");

header("Location: pedidos.php?msg=excluido");
exit;
