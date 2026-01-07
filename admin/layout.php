<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit;
}

// Página atual para marcar menu ativo
$current = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Painel Admin - Mimoso Lanches</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<style>
body{background:#111827;color:#f9fafb;}
.sidebar{width:240px;position:fixed;top:0;bottom:0;left:0;background:#1f2937;display:flex;flex-direction:column;z-index:1000;}
.sidebar .brand{font-weight:bold;font-size:20px;text-align:center;padding:20px;border-bottom:1px solid #374151;}
.sidebar a{color:#d1d5db;text-decoration:none;display:flex;align-items:center;padding:12px 20px;font-size:15px;}
.sidebar a:hover,.sidebar a.active{background:#2563eb;color:#fff;}
.sidebar a i{margin-right:10px;font-size:18px;}
.main{margin-left:240px;padding:20px;}
.navbar-top{background:#1f2937;color:#f9fafb;padding:10px 20px;display:flex;justify-content:space-between;align-items:center;}
.navbar-top a{color:#f9fafb;text-decoration:none;}
footer{text-align:center;color:#9ca3af;font-size:13px;margin-top:40px;}
</style>
</head>
<body>

<div class="sidebar">
  <div class="brand">🍔 Mimoso Lanches</div>
  <a href="dashboard.php" class="<?=($current=='dashboard.php')?'active':''?>"><i class="bi bi-speedometer2"></i> Dashboard</a>
  <a href="pedidos.php" class="<?=($current=='pedidos.php')?'active':''?>"><i class="bi bi-bag-check"></i> Pedidos</a>
  <a href="categorias.php" class="<?=($current=='categorias.php')?'active':''?>"><i class="bi bi-list-ul"></i> Categorias</a>
  <a href="produtos.php" class="<?=($current=='produtos.php')?'active':''?>"><i class="bi bi-basket2"></i> Produtos</a>
  <a href="relatorios.php" class="<?=($current=='relatorios.php')?'active':''?>"><i class="bi bi-graph-up"></i> Relatórios</a>
  <div class="mt-auto p-3">
    <a href="logout.php" class="btn btn-danger w-100"><i class="bi bi-box-arrow-right"></i> Sair</a>
  </div>
</div>

<div class="main">
  <div class="navbar-top">
    <span><strong>Painel Administrativo</strong></span>
    <span>Bem-vindo, <?=htmlspecialchars($_SESSION['user'] ?? 'Admin')?> 👋</span>
  </div>
