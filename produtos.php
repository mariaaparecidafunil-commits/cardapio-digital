<?php
session_start();
require_once __DIR__ . '/../backend/conexao.php';

// 🔒 Verifica login
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
  header("Location: login.php");
  exit;
}

// =========================
// CADASTRAR PRODUTO
// =========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nome'])) {
  $nome = $conn->real_escape_string($_POST['nome']);
  $categoria = intval($_POST['categoria_id']);
  $industrial = floatval($_POST['preco_industrial']);
  $frango = floatval($_POST['preco_frango']);
  $artesanal = floatval($_POST['preco_artesanal']);

  $conn->query("INSERT INTO produtos (nome,categoria_id,preco_industrial,preco_frango,preco_artesanal,ativo) 
                VALUES ('$nome',$categoria,$industrial,$frango,$artesanal,1)");
  $produto_id = $conn->insert_id;

  // INSUMOS
  if (!empty($_POST['insumos'])) {
    foreach ($_POST['insumos'] as $ins) {
      $iid = intval($ins['id']);
      $qtd = floatval($ins['quantidade']);
      if ($iid && $qtd > 0)
        $conn->query("INSERT INTO produto_insumo (produto_id,insumo_id,quantidade) VALUES ($produto_id,$iid,$qtd)");
    }
  }

  // OPCIONAIS
  if (!empty($_POST['opcionais'])) {
    foreach ($_POST['opcionais'] as $oid) {
      $oid = intval($oid);
      if ($oid)
        $conn->query("INSERT INTO produto_opcional (produto_id,opcional_id) VALUES ($produto_id,$oid)");
    }
  }

  header("Location: produtos.php?ok=1");
  exit;
}

// =========================
// BUSCA PRODUTOS
// =========================
$sql = "SELECT p.id,p.nome,p.categoria_id,c.nome AS categoria,
               p.preco_industrial,p.preco_frango,p.preco_artesanal
        FROM produtos p
        LEFT JOIN categorias c ON c.id=p.categoria_id
        ORDER BY c.nome,p.nome";
$res = $conn->query($sql);
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

<?php if(isset($_GET['ok'])): ?>
<div class='alert alert-success'>✅ Produto cadastrado com sucesso!</div>
<?php endif; ?>

<div class='text-end mb-3'>
  <button class='btn btn-primary' data-bs-toggle='modal' data-bs-target='#modalProduto'>➕ Novo Produto</button>
</div>

<div class='table-responsive'>
<table class='table table-dark table-striped align-middle'>
<thead><tr>
<th>ID</th><th>Produto</th><th>Categoria</th>
<th>Preço Industrial</th><th>Preço Frango</th><th>Preço Artesanal</th>
<th>Ações</th>
</tr></thead>
<tbody>
<?php while($p=$res->fetch_assoc()): ?>
<tr>
  <td><?=$p['id']?></td>
  <td><?=htmlspecialchars($p['nome'])?></td>
  <td><?=htmlspecialchars($p['categoria']??'–')?></td>
  <td>R$ <?=number_format($p['preco_industrial'],2,',','.')?></td>
  <td>R$ <?=number_format($p['preco_frango'],2,',','.')?></td>
  <td>R$ <?=number_format($p['preco_artesanal'],2,',','.')?></td>
  <td>
    <a href='insumos.php?p=<?=$p['id']?>' class='btn btn-sm btn-info'>
      🧂 Insumos
    </a>
    <a href='variacoes.php?pid=<?=$p['id']?>' class='btn btn-sm btn-warning'>
      ⚙️ Variações
    </a>
    <!-- ✅ NOVO LINK FUNCIONAL -->
    <a href='produto_opcionais.php?id=<?=$p['id']?>' class='btn btn-sm btn-secondary'>
      <i class="bi bi-plus-circle"></i> Opcionais
    </a>
  </td>
</tr>
<?php endwhile; ?>
</tbody></table></div>

<!-- =============================== -->
<!-- MODAL CADASTRAR NOVO PRODUTO -->
<!-- =============================== -->
<div class="modal fade" id="modalProduto" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content bg-dark text-light">
      <form method="post">
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
              <select name="categoria_id" class="form-select" required>
                <option value="">Selecione...</option>
                <?php
                $cats = $conn->query("SELECT id, nome FROM categorias WHERE ativo=1 ORDER BY nome");
                while($c = $cats->fetch_assoc()):
                ?>
                  <option value="<?=$c['id']?>"><?=htmlspecialchars($c['nome'])?></option>
                <?php endwhile; ?>
              </select>
            </div>

            <!-- Preços -->
            <div class="col-md-4">
              <label class="form-label">Preço Industrial (R$)</label>
              <input type="number" step="0.01" name="preco_industrial" class="form-control" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Preço Frango (R$)</label>
              <input type="number" step="0.01" name="preco_frango" class="form-control" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Preço Artesanal (R$)</label>
              <input type="number" step="0.01" name="preco_artesanal" class="form-control" required>
            </div>
          </div>

          <hr class="text-secondary my-3">

          <!-- Insumos -->
          <h5>🧂 Insumos (ingredientes e quantidades)</h5>
          <div class="row g-2">
            <?php
            $insumos = $conn->query("SELECT id,nome,unidade FROM insumos ORDER BY nome");
            while($i = $insumos->fetch_assoc()):
            ?>
              <div class="col-md-6">
                <div class="input-group input-group-sm mb-1">
                  <span class="input-group-text bg-secondary text-light" style="min-width:160px">
                    <?=htmlspecialchars($i['nome'])?> (<?=$i['unidade']?>)
                  </span>
                  <input type="number" step="0.01" name="insumos[<?=$i['id']?>][quantidade]" class="form-control" placeholder="Qtd.">
                  <input type="hidden" name="insumos[<?=$i['id']?>][id]" value="<?=$i['id']?>">
                </div>
              </div>
            <?php endwhile; ?>
          </div>

          <hr class="text-secondary my-3">

          <!-- Opcionais -->
          <h5>⚙️ Opcionais (extras disponíveis)</h5>
          <div class="row g-2">
            <?php
            $opc = $conn->query("SELECT id,nome,preco FROM opcionais ORDER BY nome");
            while($o = $opc->fetch_assoc()):
            ?>
              <div class="col-md-4">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="opcionais[]" value="<?=$o['id']?>" id="op<?=$o['id']?>">
                  <label class="form-check-label" for="op<?=$o['id']?>">
                    <?=htmlspecialchars($o['nome'])?> — R$ <?=number_format($o['preco'],2,',','.')?>
                  </label>
                </div>
              </div>
            <?php endwhile; ?>
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
