<?php
session_start();
require_once __DIR__ . '/../backend/conexao.php';

// ✅ Verifica permissão
if (!isset($_SESSION['perfil']) || $_SESSION['perfil'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// ✅ Verifica ID
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    die("<h3 style='color:red;margin:40px'>ID inválido.</h3>");
}

// ✅ Apagar produto (insumos e opcionais apagam automático por FK)
$stmt = $conn->prepare("DELETE FROM produtos WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

// ✅ Redireciona de volta
header("Location: produtos.php?deleted=1");
exit;
