<?php
// admin/opcionais.php
ini_set('display_errors',1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/../backend/conexao.php';

// --- ADICIONAR / EDITAR ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $conn->real_escape_string($_POST['nome']);
    $preco = (float)$_POST['preco'];

    if (!empty($_POST['id'])) {
        // EDITAR
        $id = (int)$_POST['id'];
        $conn->query("UPDATE opcionais SET nome='$nome', preco=$preco WHERE id=$id");
    } else {
        // NOVO
        $conn->query("INSERT INTO opcionais (produto_id, nome, preco) VALUES (0,'$nome',$preco)");
    }

    header("Location: opcionais.php");
    exit;
}

// --- EXCLUIR ---
if (isset($_GET['del'])) {
    $id = (int)$_GET['del'];
    $conn->query("DELETE FROM opcionais WHERE id=$id");
    header("Location: opcionais.php");
    exit;
}

// --- LISTAR ---
$opcionais = $conn->query("SELECT * FROM opcionais ORDER BY nome ASC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Opcionais - Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background:#1e1e2f; color:#f0f0f0; }
    .wrap{max-width:800px;margin:20px auto;padding:20px;background:#2b2b3c;border-radius:12px;box-shadow:0 2px 6px rgba(0,0,0,.5);}
    .table-dark td,.table-dark th{color:#f0f0f0;}
  </style>
</head>
<body>
<div class="wrap">
  <h2 class="mb-4">Gerenciar Opcionais</h2>

  <!-- FORMULÁRIO DE CADASTRO -->
  <form method="post" class="row g-3 mb-4">
    <input type="hidden" name="id" id="edit-id">
    <div class="col-md-6">
      <label class="form-label">Nome do opcional</label>
      <input type="text" name="nome" id="edit-nome" class="form-control" required>
    </div>
    <div class="col-md-3">
      <label class="form-label">Preço (R$)</label>
      <input type="number" step="0.01" name="preco" id="edit-preco" class="form-control" required>
    </div>
    <div class="col-md-3 d-flex align-items-end">
      <button class="btn btn-success w-100">Salvar</button>
    </div>
  </form>

  <!-- LISTA DE OPCIONAIS -->
  <table class="table table-dark table-striped">
    <thead>
      <tr>
        <th>Nome</th>
        <th>Preço</th>
        <th>Ações</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach($opcionais as $o): ?>
      <tr>
        <td><?=htmlspecialchars($o['nome'])?></td>
        <td>R$ <?=number_format($o['preco'],2,',','.')?></td>
        <td>
          <a href="?del=<?=$o['id']?>" class="btn btn-sm btn-danger" onclick="return confirm('Excluir este opcional?')">Excluir</a>
          <button class="btn btn-sm btn-warning" onclick="editar(<?=$o['id']?>,'<?=htmlspecialchars($o['nome'],ENT_QUOTES)?>',<?=$o['preco']?>)">Editar</button>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<script>
function editar(id,nome,preco){
  document.getElementById('edit-id').value = id;
  document.getElementById('edit-nome').value = nome;
  document.getElementById('edit-preco').value = preco;
  window.scrollTo({top:0,behavior:'smooth'});
}
</script>
</body>
</html>
