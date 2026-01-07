<?php
// admin/composicao.php
session_start();
require_once __DIR__ . '/../backend/conexao.php';

// 🔒 Verifica login
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit;
}

$produtoAtual = isset($_GET['p']) ? intval($_GET['p']) : 0;
$precoVenda = isset($_GET['pv']) ? floatval($_GET['pv']) : null;

$produtos = $conn->query("SELECT id, nome FROM produtos ORDER BY nome ASC")->fetch_all(MYSQLI_ASSOC);

$custo = 0;
$insumosProduto = [];
if($produtoAtual){
    $res = $conn->query("
        SELECT pi.id as link_id, pi.quantidade, i.nome, i.unidade, i.preco_unit
        FROM produto_insumo pi
        LEFT JOIN insumos i ON i.id = pi.insumo_id
        WHERE pi.produto_id = $produtoAtual
    ");
    while($r = $res->fetch_assoc()){
        $subtotal = $r['quantidade'] * floatval($r['preco_unit']);
        $r['subtotal'] = $subtotal;
        $custo += $subtotal;
        $insumosProduto[] = $r;
    }
}

$lucroBruto = ($precoVenda!==null) ? $precoVenda - $custo : null;
$margemBruta = ($precoVenda>0 && $lucroBruto!==null) ? ($lucroBruto/$precoVenda)*100 : null;

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Composição de Produtos</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body{background:#111827;color:#f9fafb;}
header{background:#1f2937;padding:16px;color:#fff;text-align:center;}
.card{background:#1f2937;color:#f9fafb;box-shadow:0 2px 6px rgba(0,0,0,.3);}
</style>
</head>
<body>
<header><h1>⚙️ Composição de Produtos</h1></header>
<div class="container py-4">

<form class="row mb-3" method="get">
  <div class="col-md-5">
    <label class="form-label">Produto</label>
    <select name="p" class="form-select" onchange="this.form.submit()">
      <option value="">Selecione...</option>
      <?php foreach($produtos as $pr): ?>
        <option value="<?=$pr['id']?>" <?=$pr['id']==$produtoAtual?'selected':''?>><?=$pr['nome']?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-3">
    <label class="form-label">Preço de venda p/ cálculo</label>
    <input type="number" step="0.01" name="pv" value="<?= $precoVenda!==null?$precoVenda:''?>" class="form-control">
  </div>
  <div class="col-md-2 d-flex align-items-end">
    <button class="btn btn-primary w-100">Atualizar</button>
  </div>
  <div class="col-md-2 d-flex align-items-end">
    <a href="composicao_crud.php" class="btn btn-secondary w-100">+ Gerenciar Insumos</a>
  </div>
</form>

<?php if($produtoAtual): ?>
  <h4>📦 Insumos do Produto</h4>
  <table class="table table-dark table-striped">
    <thead><tr><th>Insumo</th><th>Qtd</th><th>Unid</th><th>Preço Unit</th><th>Subtotal</th></tr></thead>
    <tbody>
      <?php foreach($insumosProduto as $i): ?>
        <tr>
          <td><?=htmlspecialchars($i['nome'])?></td>
          <td><?=$i['quantidade']?></td>
          <td><?=$i['unidade']?></td>
          <td>R$ <?=number_format($i['preco_unit'],2,',','.')?></td>
          <td>R$ <?=number_format($i['subtotal'],2,',','.')?></td>
        </tr>
      <?php endforeach; ?>
      <?php if(!$insumosProduto): ?>
        <tr><td colspan="5" class="text-center text-muted">Nenhum insumo vinculado ainda.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>

  <div class="row g-3 mt-3">
    <div class="col-md-4"><div class="card p-3 text-center"><h5>Custo Total</h5><h3>R$ <?=number_format($custo,2,',','.')?></h3></div></div>
    <div class="col-md-4"><div class="card p-3 text-center"><h5>Preço Venda</h5><h3><?= $precoVenda!==null?'R$ '.number_format($precoVenda,2,',','.'): '—'?></h3></div></div>
    <div class="col-md-4"><div class="card p-3 text-center"><h5>Lucro Bruto</h5><h3><?= $lucroBruto!==null?'R$ '.number_format($lucroBruto,2,',','.'): '—'?></h3>
        <?php if($margemBruta!==null): ?><p class="text-muted">Margem: <?=number_format($margemBruta,1,',','.')?>%</p><?php endif; ?>
    </div></div>
  </div>
<?php endif; ?>

</div>
</body>
</html>
