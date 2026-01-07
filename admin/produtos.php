<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../backend/conexao.php';

// Proteção: só admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// -----------------------------
// EXCLUSÃO DE PRODUTO
// -----------------------------
if (isset($_GET['del'])) {
    $id = intval($_GET['del']);

    // Apaga relacionamentos primeiro
    $conn->query("DELETE FROM produto_insumo WHERE produto_id = $id");
    $conn->query("DELETE FROM produto_opcional WHERE produto_id = $id");

    // Apaga imagem se existir
    $foto = $conn->query("SELECT imagem FROM produtos WHERE id = $id")->fetch_assoc();
    if ($foto && !empty($foto['imagem'])) {
        $path = __DIR__ . "/../uploads/produtos/" . $foto['imagem'];
        if (file_exists($path)) unlink($path);
    }

    // Apaga produto
    $conn->query("DELETE FROM produtos WHERE id = $id");

    header("Location: produtos.php");
    exit;
}

// -----------------------------
// LISTA DE PRODUTOS
// -----------------------------
$produtos = $conn->query("
    SELECT p.*, c.nome AS categoria
    FROM produtos p
    LEFT JOIN categorias c ON c.id = p.categoria_id
    ORDER BY p.id DESC
");
?>
<!DOCTYPE html>
<html lang='pt-BR'>
<head>
<meta charset='UTF-8'>
<meta name='viewport' content='width=device-width,initial-scale=1'>
<title>Produtos — Mimoso Lanches</title>

<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>
body{background:#111827;color:#f9fafb;font-family:'Segoe UI',sans-serif;}
.table-dark th,.table-dark td{vertical-align:middle;}
.card-dark{background:#1f2937;border-radius:10px;padding:20px;box-shadow:0 2px 6px rgba(0,0,0,.3);}
.btn-primary{background:#ff6b00;border:none;}
.btn-primary:hover{background:#e65c00;}
</style>
</head>
<body class='p-3'>

<h1 class='mb-4'>🍔 Produtos Cadastrados</h1>

<div class='text-end mb-3'>
  <button class='btn btn-primary' data-bs-toggle='modal' data-bs-target='#modalProduto'>
    ➕ Novo Produto
  </button>
</div>

<div class='table-responsive'>
<table class='table table-dark table-striped align-middle'>
<thead>
<tr>
<th>ID</th>
<th>Produto</th>
<th>Categoria</th>
<th>Preço Industrial</th>
<th>Preço Frango</th>
<th>Preço Artesanal</th>
<th>Ações</th>
</tr>
</thead>
<tbody>

<?php while ($p = $produtos->fetch_assoc()): ?>
<tr>
  <td><?= $p['id'] ?></td>
  <td><?= htmlspecialchars($p['nome']) ?></td>
  <td><?= htmlspecialchars($p['categoria']) ?></td>

  <td>R$ <?= number_format($p['preco_industrial'], 2, ',', '.') ?></td>
  <td>R$ <?= number_format($p['preco_frango'], 2, ',', '.') ?></td>
  <td>R$ <?= number_format($p['preco_artesanal'], 2, ',', '.') ?></td>

  <td>
    <a href='insumos.php?p=<?= $p['id'] ?>' class='btn btn-sm btn-info'>🧂 Insumos</a>
    <a href='variacoes.php?pid=<?= $p['id'] ?>' class='btn btn-sm btn-warning'>⚙️ Variações</a>
    <a href='produto_opcionais.php?id=<?= $p['id'] ?>' class='btn btn-sm btn-secondary'>
      <i class="bi bi-plus-circle"></i> Opcionais
    </a>
    <a href='editar_produto.php?id=<?= $p['id'] ?>' class='btn btn-sm btn-success'>
      <i class="bi bi-pencil"></i> Editar
    </a>
    <a href='produtos.php?del=<?= $p['id'] ?>'
       class='btn btn-sm btn-danger'
       onclick="return confirm('Tem certeza que deseja excluir este produto?')">
      <i class="bi bi-trash"></i> Excluir
    </a>
  </td>
</tr>
<?php endwhile; ?>

</tbody>
</table>
</div>

<!-- =============================== -->
<!-- MODAL CADASTRAR NOVO PRODUTO -->
<!-- =============================== -->
<div class="modal fade" id="modalProduto" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content bg-dark text-light">
      <form method="post" action="produto_salvar.php" enctype="multipart/form-data">

        <div class="modal-header">
          <h5 class="modal-title">➕ Novo Produto</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">

          <div class="row g-3">

            <!-- Nome -->
            <div class="col-md-6">
              <label class="form-label">Nome do Produto</label>
              <input type="text" name="nome" class="form-control" required>
            </div>

            <!-- Categoria -->
            <div class="col-md-6">
              <label class="form-label">Categoria</label>
              <select name="categoria_id" class="form-select" id="categoriaSelect" required>
                <option value="">Selecione...</option>

                <?php
                $cats = $conn->query("SELECT id, nome FROM categorias ORDER BY nome");
                while ($c = $cats->fetch_assoc()):
                ?>
                <option value="<?= $c['id'] ?>"><?= $c['nome'] ?></option>
                <?php endwhile; ?>

              </select>
            </div>

            <!-- Preço Único (para bebidas e sucos) -->
            <div class="col-md-6 preco-unico" style="display:none;">
              <label class="form-label">Preço (R$)</label>
              <input type="number" step="0.01" name="preco_unico" class="form-control">
            </div>

            <!-- Preço Industrial -->
            <div class="col-md-4 preco-lanche">
              <label class="form-label">Preço Industrial (R$)</label>
              <input type="number" step="0.01" name="preco_industrial" class="form-control">
            </div>

            <!-- Preço Frango -->
            <div class="col-md-4 preco-lanche">
              <label class="form-label">Preço Frango (R$)</label>
              <input type="number" step="0.01" name="preco_frango" class="form-control">
            </div>

            <!-- Preço Artesanal -->
            <div class="col-md-4 preco-lanche">
              <label class="form-label">Preço Artesanal (R$)</label>
              <input type="number" step="0.01" name="preco_artesanal" class="form-control">
            </div>

          </div>

<script>
// ----- LÓGICA DE EXIBIÇÃO DE PREÇOS -----
document.getElementById('categoriaSelect').addEventListener('change', function () {
    let cat = parseInt(this.value);

    if (cat === 20) {
        // LANCHES → 3 preços
        document.querySelectorAll('.preco-lanche').forEach(e => e.style.display = 'block');
        document.querySelector('.preco-unico').style.display = 'none';
    } else {
        // BEBIDAS E SUCOS → 1 preço
        document.querySelectorAll('.preco-lanche').forEach(e => e.style.display = 'none');
        document.querySelector('.preco-unico').style.display = 'block';
    }
});
</script>


          <hr class="text-secondary my-3">

          <!-- Insumos -->
          <h5>🧂 Insumos</h5>
          <div class="row g-2">
            <?php
            $insumos = $conn->query("SELECT * FROM insumos ORDER BY nome");
            while ($i = $insumos->fetch_assoc()):
            ?>
            <div class="col-md-6">
              <div class="input-group input-group-sm mb-1">
                <span class="input-group-text bg-secondary text-light" style="min-width:160px">
                  <?= $i['nome'] ?> (<?= $i['unidade'] ?>)
                </span>
                <input type="number" step="0.01" name="insumos[<?= $i['id'] ?>][quantidade]" class="form-control">
                <input type="hidden" name="insumos[<?= $i['id'] ?>][id]" value="<?= $i['id'] ?>">
              </div>
            </div>
            <?php endwhile; ?>
          </div>

          <hr class="text-secondary my-3">

          <!-- Opcionais -->
          <h5>⚙️ Opcionais</h5>
          <div class="row g-2">
            <?php
            $ops = $conn->query("SELECT * FROM opcionais ORDER BY nome");
            while ($o = $ops->fetch_assoc()):
            ?>
            <div class="col-md-4">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="opcionais[]" value="<?= $o['id'] ?>" id="op<?= $o['id'] ?>">
                <label class="form-check-label" for="op<?= $o['id'] ?>">
                  <?= $o['nome'] ?> — R$ <?= number_format($o['preco'], 2, ',', '.') ?>
                </label>
              </div>
            </div>
            <?php endwhile; ?>
          </div>

          <hr class="text-secondary my-3">

          <!-- Imagem -->
          <div class="mb-3">
            <label class="form-label">Imagem do Produto</label>
            <input type="file" name="imagem" class="form-control">
          </div>

        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Salvar Produto</button>
        </div>

      </form>
    </div>
  </div>
</div>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
