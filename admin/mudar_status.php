<?php
// admin/mudar_status.php
session_start();
require_once __DIR__ . '/../backend/conexao.php';

if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID inválido.");
}
$id = (int)$_GET['id'];

// Busca status atual
$stmt = $conn->prepare("SELECT status FROM pedidos WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$res) { die("Pedido não encontrado."); }

$statusAtual = strtolower($res['status']);

// Ciclo de status
switch($statusAtual){
    case 'em preparo':
        $novoStatus = 'saiu para entrega';
        break;
    case 'saiu para entrega':
        $novoStatus = 'concluído';
        break;
    default:
        $novoStatus = 'em preparo';
}

// Atualiza
$stmt = $conn->prepare("UPDATE pedidos SET status=? WHERE id=?");
$stmt->bind_param("si", $novoStatus, $id);
$stmt->execute();
$stmt->close();

header("Location: pedidos.php");
exit;
