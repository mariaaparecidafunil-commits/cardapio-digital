<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header('Location: login.php?erro=Acesso negado.');
  exit;
}
require_once __DIR__ . '/../backend/conexao.php';

// Adicionar categoria
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['nome'])) {
  $nome = trim($_POST['nome']);
  if ($nome!=='') {
    $stmt = $conn->prepare("INSERT INTO categorias (nome) VALUES (?)");
    $stmt->bind_param("s",$nome);
    $stmt->execute();
  }
  header("Location: categorias.php");
  exit;
}

// Remover categoria
if (isset($_GET['del'])) {
  $id = (int)$_GET['del'];
  $stmt = $conn->prepare("DELETE FROM categorias WHERE id=?");
  $stmt->bind_param("i",$id);
  $stmt->execute();
  header("Location: categorias.php");
  exit;
}

// Buscar categorias
$res = $conn->query("SELECT * FROM categorias ORDER BY id DESC");
$cats = $res->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Categorias - Admin</title>
  <style>
    body{font-family:Arial;background:#f8fafc;margin:0}
    .wrap{max-width:800px;margin:20px auto;padding:20px;background:#fff;border-radius:12px}
    table{width:100%;border-collapse:collapse}
    td,th{padding:8px;border-bottom:1px solid #ddd}
    a{color:#b00020;text-decoration:none}
  </style>
</head>
<body>
<div class="wrap">
  <h2>Categorias</h2>
  <form method="post">
    <input type="text" name="nome" placeholder="Nova categoria" required>
    <button type="submit">Adicionar</button>
  </form>
  <table>
    <tr><th>ID</th><th>Nome</th><th>Ações</th></tr>
    <?php foreach($cats as $c): ?>
      <tr>
        <td><?=$c['id']?></td>
        <td><?=$c['nome']?></td>
        <td><a href="?del=<?=$c['id']?>" onclick="return confirm('Excluir?')">Excluir</a></td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>
</body>
</html>
