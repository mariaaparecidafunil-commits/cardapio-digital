<?php
// admin/variacoes.php
session_start();
require_once __DIR__ . '/../backend/conexao.php';

// 🔒 Verifica login
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit;
}

// ======================
// FUNÇÕES AUXILIARES
// ======================
function calcularCusto($conn, $variacao_id){
    $sql = "SELECT vi.quantidade, i.preco_unit
            FROM variacao_insumo vi
            JOIN insumos i ON i.id=vi.insumo_id
            WHERE vi.variacao_id=".intval($variacao_id);
    $res = $conn->query($sql);
    $custo = 0;
    if($res){
        while($r = $res->fetch_assoc()){
            $custo += $r['quantidade'] * $r['preco_unit'];
        }
    }
    return $custo;
}

// ======================
// CRUD SIMPLES
// ======================
if(isset($_POST['acao']) && $_POST['acao']=='nova'){
    $produto = intval($_POST['produto']);
    $nome = $conn->real_escape_string($_POST['nome_variacao']);
    $preco = floatval($_POST['preco_venda']);
    $conn->query("INSERT INTO produtos_variacoes (produto_id,nome_variacao,preco_venda) VALUES ($produto,'$nome',$preco)");
    header("Location: variacoes.php");
    exit;
}

if(isset($_POST['acao']) && $_POST['acao']=='editar'){
    $id = intval($_POST['id']);
    $nome = $conn->real_escape_string($_POST['nome_variacao']);
    $preco = floatval($_POST['preco_venda']);
    $conn->query("UPDATE produtos_variacoes SET nome_variacao='$nome',preco_venda=$preco WHERE id=$id");
    header("Location: variacoes.php");
    exit;
}

if(isset($_GET['del']) && is_numeric($_GET['del'])){
    $id = intval($_GET['del']);
    $conn->query("DELETE FROM produtos_variacoes WHERE id=$id");
    header("Location: variacoes.php");
    exit;
}

// ======================
// BUSCAR DADOS
// ======================
$produtos = $conn->query("SELECT id,nome FROM produtos ORDER BY nome ASC");

$sql = "SELECT v.id, v.produto_id, p.nome AS produto, v.nome_variacao, v.preco_venda
        FROM produtos_variacoes v
        JOIN produtos p ON p.id=v.produto_id
        ORDER BY p.nome, v.nome_variacao";
$variacoes = $conn->query($sql);
$lista = array();
$totalVendas = 0; $totalCusto = 0;

if($variacoes){
  while($v = $variacoes->fetch_assoc()){
      $custo = calcularCusto($conn,$v['id']);
      $lucro = $v['preco_venda'] - $custo;
      $margem = $v['preco_venda']>0 ? ($lucro/$v['preco_venda']*100) : 0;
      $v['custo']=$custo; $v['lucro']=$lucro; $v['margem']=$margem;
      $lista[]=$v;
      $totalVendas += $v['preco_venda'];
      $totalCusto += $custo;
  }
}
$ticketMedio = count($lista)>0 ? $totalVendas/count($lista):0;
$lucroMedio = count($lista)>0 ? ($totalVendas-$totalCusto)/count($lista):0;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Variações de Produtos</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body{background:#111827;color:#f9fafb;}
.table thead th{background:#1f2937;color:#fff;}
.badge-lucro-alto{background:#22c55e;}
.badge-lucro-medio{background:#f59e0b;}
.badge-lucro-baixo{background:#dc2626;}
.card-resumo{background:#1f2937;padding:12px;border-radius:8px;text-align:center;box-shadow:0 2px 6px rgba(0,0,0,.3);}
</style>
</head>
<body class="p-3">
<h1 class="mb-4">⚙️ Variações de Produtos</h1>

<div class="row g-3 mb-4">
  <div class="col-md-3"><div class="card-resumo"><h5>Total Variações</h5><h3><?=count($lista)?></h3></div></div>
  <div class="col-md-3"><div class="card-resumo"><h5>Ticket Médio</h5><h3>R$ <?=number_format($ticketMedio,2,',','.')?></h3></div></div>
  <div class="col-md-3"><div class="card-resumo"><h5>Lucro Médio</h5><h3>R$ <?=number_format($lucroMedio,2,',','.')?></h3></div></div>
  <div class="col-md-3"><div class="card-resumo"><h5>Margem Média</h5><h3><?=number_format($ticketMedio>0?($lucroMedio/$ticketMedio*100):0,1,",",".")?>%</h3></div></div>
</div>

<!-- FORM NOVA VARIAÇÃO -->
<div class="card mb-4 p-3 bg-dark">
  <h5>➕ Adicionar Nova Variação</h5>
  <form method="post" class="row g-2">
    <input type="hidden" name="acao" value="nova">
    <div class="col-md-4">
      <select name="produto" class="form-select" required>
        <option value="">Selecione Produto</option>
        <?php if($produtos){ while($p=$produtos->fetch_assoc()){ ?>
        <option value="<?=$p['id']?>"><?=$p['nome']?></option>
        <?php }} ?>
      </select>
    </div>
    <div class="col-md-4">
      <input type="text" name="nome_variacao" class="form-control" placeholder="Nome da variação" required>
    </div>
    <div class="col-md-2">
      <input type="number" step="0.01" name="preco_venda" class="form-control" placeholder="Preço" required>
    </div>
    <div class="col-md-2">
      <button class="btn btn-success w-100">Salvar</button>
    </div>
  </form>
</div>
<!-- LISTAGEM -->
<div class="table-responsive">
<table class="table table-dark table-striped table-hover align-middle">
<thead>
<tr>
  <th>ID</th><th>Produto</th><th>Variação</th><th>Preço Venda</th><th>Custo Insumos</th><th>Lucro</th><th>%</th><th>Ações</th>
</tr>
</thead>
<tbody>
<?php foreach($lista as $v): 
  $badge = $v['margem']>50?'badge-lucro-alto':($v['margem']>30?'badge-lucro-medio':'badge-lucro-baixo');
?>
<tr>
  <td><?=$v['id']?></td>
  <td><?=$v['produto']?></td>
  <td><?=$v['nome_variacao']?></td>
  <td>R$ <?=number_format($v['preco_venda'],2,',','.')?></td>
  <td>R$ <?=number_format($v['custo'],2,',','.')?></td>
  <td>R$ <?=number_format($v['lucro'],2,',','.')?></td>
  <td><span class="badge <?=$badge?>"><?=number_format($v['margem'],1,',','.')?>%</span></td>
  <td>
    <button class="btn btn-sm btn-warning" onclick="editar(<?=$v['id']?>,'<?=$v['nome_variacao']?>',<?=$v['preco_venda']?>)">✏️</button>
    <a class="btn btn-sm btn-danger" href="?del=<?=$v['id']?>" onclick="return confirm('Excluir esta variação?')">🗑</a>
    <a class="btn btn-sm btn-info" href="composicao.php?pv=<?=$v['id']?>">🧩 Insumos</a>
  </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<!-- MODAL EDITAR -->
<div class="modal fade" id="modalEditar" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content bg-dark text-light">
      <form method="post">
        <input type="hidden" name="acao" value="editar">
        <input type="hidden" name="id" id="edit-id">
        <div class="modal-header"><h5 class="modal-title">Editar Variação</h5></div>
        <div class="modal-body">
          <div class="mb-3">
            <label>Nome</label>
            <input type="text" name="nome_variacao" id="edit-nome" class="form-control" required>
          </div>
          <div class="mb-3">
            <label>Preço de Venda</label>
            <input type="number" step="0.01" name="preco_venda" id="edit-preco" class="form-control" required>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-success">Salvar</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function editar(id,nome,preco){
  document.getElementById('edit-id').value=id;
  document.getElementById('edit-nome').value=nome;
  document.getElementById('edit-preco').value=preco;
  var modal = new bootstrap.Modal(document.getElementById('modalEditar'));
  modal.show();
}
</script>
</body>
</html>
