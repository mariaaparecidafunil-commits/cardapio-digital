<?php
session_start();

// ===================================================
// CONFIGURAÇÃO E CONEXÃO
// ===================================================
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../backend/conexao.php';

// ===================================================
// VERIFICA LOGIN
// ===================================================
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit;
}

// ===================================================
// OBTÉM PRODUTO
// ===================================================
$idProduto = intval($_GET['id'] ?? 0);
if ($idProduto <= 0) {
    die("<div class='alert alert-warning text-center mt-5'>⚠️ Produto inválido.</div>");
}

$produto = $conn->query("SELECT nome FROM produtos WHERE id = $idProduto")->fetch_assoc();
if (!$produto) {
    die("<div class='alert alert-danger text-center mt-5'>❌ Produto não encontrado.</div>");
}

// ===================================================
// SALVAR OPCIONAIS MARCADOS
// ===================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Remove todos os opcionais antigos
    $conn->query("DELETE FROM produto_opcional WHERE produto_id = $idProduto");

    // Adiciona os novos marcados
    if (!empty($_POST['opcionais'])) {
        foreach ($_POST['opcionais'] as $opcional_id) {
            $opcional_id = intval($opcional_id);
            $conn->query("INSERT INTO produto_opcional (produto_id, opcional_id) VALUES ($idProduto, $opcional_id)");
        }
    }

    header("Location: produto_opcionais.php?id=$idProduto&ok=1");
    exit;
}

// ===================================================
// BUSCA LISTA DE OPCIONAIS
// ===================================================
$opcionais = $conn->query("SELECT id, nome, preco FROM opcionais ORDER BY nome");

// Opcionais já vinculados
$jaVinculados = [];
$res = $conn->query("SELECT opcional_id FROM produto_opcional WHERE produto_id = $idProduto");
while ($r = $res->fetch_assoc()) {
    $jaVinculados[] = $r['opcional_id'];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Opcionais do Produto — Mimoso Lanches</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
body{background:#1e1e2f;color:#f0f0f0;font-family:'Segoe UI',sans-serif;}
.container{max-width:800px;margin-top:40px;}
.card{background:#2b2b3c;border:none;border-radius:10px;box-shadow:0 0 10px rgba(0,0,0,.4);}
.btn-primary{background:#ff6b00;border:none;}
.btn-primary:hover{background:#e65c00;}
h2 span{color:#ff6b00;}
.table-dark th,.table-dark td{color:#f0f0f0;}
</style>
</head>
<body>

<div class="container">
  <div class="card p-4">
    <h2 class="mb-4">⚙️ Opcionais para: <span><?=htmlspecialchars($produto['nome'])?></span></h2>

    <?php if(isset($_GET['ok'])): ?>
      <div class="alert alert-success">✅ Opcionais atualizados com sucesso!</div>
    <?php endif; ?>

    <form method="post">
      <div class="row g-3">
        <?php while($op = $opcionais->fetch_assoc()): ?>
          <?php $checked = in_array($op['id'], $jaVinculados) ? 'checked' : ''; ?>
          <div class="col-md-6">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="opcionais[]" value="<?=$op['id']?>" id="op<?=$op['id']?>" <?=$checked?>>
              <label class="form-check-label" for="op<?=$op['id']?>">
                <?=htmlspecialchars($op['nome'])?> — 
                <span class="text-warning">R$ <?=number_format($op['preco'],2,',','.')?></span>
              </label>
            </div>
          </div>
        <?php endwhile; ?>
      </div>

      <div class="mt-4 d-flex justify-content-between">
        <a href="produtos.php" class="btn btn-secondary">
          <i class="bi bi-arrow-left"></i> Voltar
        </a>
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-save"></i> Salvar Opcionais
        </button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
