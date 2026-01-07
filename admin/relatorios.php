<?php
session_start();
require_once __DIR__ . '/../backend/conexao.php';

if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit;
}

// ------------------- FILTROS --------------------
$inicio = $_GET['inicio'] ?? date('Y-m-01');
$fim    = $_GET['fim'] ?? date('Y-m-d');
$status = $_GET['status'] ?? '';
$pagto  = $_GET['pagto'] ?? '';

$where = [];
$where[] = "DATE(data) BETWEEN '$inicio' AND '$fim'";
if ($status) $where[] = "status = '".$conn->real_escape_string($status)."'";
if ($pagto)  $where[] = "forma_pagamento = '".$conn->real_escape_string($pagto)."'";
$whereSql = $where ? "WHERE ".implode(" AND ",$where) : "";

// ------------------- RESUMO --------------------
$qResumo = $conn->query("SELECT 
    COUNT(*) pedidos,
    IFNULL(SUM(total),0) total,
    SUM(CASE WHEN forma_pagamento='Pix' THEN total ELSE 0 END) pix,
    SUM(CASE WHEN forma_pagamento='Cartão' THEN total ELSE 0 END) cartao,
    SUM(CASE WHEN forma_pagamento='Dinheiro' THEN total ELSE 0 END) dinheiro
  FROM pedidos $whereSql");
$resumo = $qResumo->fetch_assoc();
$totalPedidos = $resumo['pedidos'] ?? 0;
$totalVendas  = $resumo['total'] ?? 0;
$pix          = $resumo['pix'] ?? 0;
$cartao       = $resumo['cartao'] ?? 0;
$dinheiro     = $resumo['dinheiro'] ?? 0;

// CUSTOS e LUCRO
$custoVariavel = $totalVendas * 0.36;
$dias = max(1, (strtotime($fim) - strtotime($inicio)) / 86400 + 1);
$custoFixo = $dias * 109.50;
$lucroBruto = $totalVendas - $custoVariavel;
$lucroLiquido = $lucroBruto - $custoFixo;
$ticketMedio = $totalPedidos ? $totalVendas/$totalPedidos : 0;

// ------------------- TOP PRODUTOS --------------------
$qTop = $conn->query("SELECT i.produto_id, p.nome, SUM(i.quantidade) qtd, SUM(i.preco*i.quantidade) total
                      FROM itens_pedido i
                      JOIN produtos p ON p.id = i.produto_id
                      JOIN pedidos d ON d.id = i.pedido_id
                      $whereSql
                      GROUP BY i.produto_id
                      ORDER BY qtd DESC
                      LIMIT 5");
$topProdutos = [];
while($r = $qTop->fetch_assoc()) $topProdutos[] = $r;

// ------------------- PEDIDOS --------------------
$qPed = $conn->query("SELECT id, cliente_nome, data, total, forma_pagamento, status 
                      FROM pedidos $whereSql ORDER BY data DESC");
$pedidos = [];
while($p = $qPed->fetch_assoc()) $pedidos[] = $p;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Relatórios Financeiros</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
body{background:#111827;color:#f9fafb;margin:0;}
.sidebar{width:220px;background:#1f2937;position:fixed;top:0;bottom:0;padding:20px;}
.sidebar h2{color:#fff;font-size:18px;margin-bottom:20px;}
.sidebar a{display:block;color:#d1d5db;text-decoration:none;margin:8px 0;padding:8px;border-radius:6px;}
.sidebar a.active, .sidebar a:hover{background:#2563eb;color:#fff;}
.content{margin-left:240px;padding:20px;}
.card-resumo{background:#1f2937;color:#f9fafb;padding:16px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.5);text-align:center;}
.card-resumo h3{margin:0;color:#fbbf24;}
.table-dark th{background:#2563eb!important;}
</style>
</head>
<body>

<div class="sidebar">
  <h2>📊 Relatórios</h2>
  <a href="#resumo" class="active">💸 Resumo Financeiro</a>
  <a href="#produtos">🥇 Top Produtos</a>
  <a href="#pedidos">📑 Pedidos</a>
</div>

<div class="content">
  <h1>📊 Relatórios Financeiros</h1>

  <!-- FILTROS -->
  <form method="get" class="row g-2 mb-4">
    <div class="col-md-3">
      <label>De</label>
      <input type="date" name="inicio" value="<?=htmlspecialchars($inicio)?>" class="form-control">
    </div>
    <div class="col-md-3">
      <label>Até</label>
      <input type="date" name="fim" value="<?=htmlspecialchars($fim)?>" class="form-control">
    </div>
    <div class="col-md-3">
      <label>Status</label>
      <select name="status" class="form-select">
        <option value="">Todos</option>
        <?php foreach(['Recebido','Em preparo','Saiu para entrega','Concluído'] as $st):?>
        <option <?=$status==$st?'selected':''?>><?=$st?></option>
        <?php endforeach;?>
      </select>
    </div>
    <div class="col-md-3">
      <label>Pagamento</label>
      <select name="pagto" class="form-select">
        <option value="">Todos</option>
        <?php foreach(['Pix','Cartão','Dinheiro'] as $pg):?>
        <option <?=$pagto==$pg?'selected':''?>><?=$pg?></option>
        <?php endforeach;?>
      </select>
    </div>
    <div class="col-12 text-end mt-2">
      <button class="btn btn-primary">Filtrar</button>
    </div>
  </form>

  <!-- RESUMO -->
  <div id="resumo" class="mb-5">
    <div class="row g-3 mb-4">
      <div class="col-md-2"><div class="card-resumo"><h6>Pedidos</h6><h3><?=$totalPedidos?></h3></div></div>
      <div class="col-md-2"><div class="card-resumo"><h6>Vendas</h6><h3>R$ <?=number_format($totalVendas,2,',','.')?></h3></div></div>
      <div class="col-md-2"><div class="card-resumo"><h6>Ticket Médio</h6><h3>R$ <?=number_format($ticketMedio,2,',','.')?></h3></div></div>
      <div class="col-md-2"><div class="card-resumo"><h6>Custo Var.(36%)</h6><h3>R$ <?=number_format($custoVariavel,2,',','.')?></h3></div></div>
      <div class="col-md-2"><div class="card-resumo"><h6>Custo Fixo</h6><h3>R$ <?=number_format($custoFixo,2,',','.')?></h3></div></div>
      <div class="col-md-2"><div class="card-resumo"><h6>Lucro Líquido</h6><h3>R$ <?=number_format($lucroLiquido,2,',','.')?></h3></div></div>
    </div>
    <div class="row">
      <div class="col-md-6"><canvas id="grafPagto"></canvas></div>
      <div class="col-md-6"><canvas id="grafVendasDia"></canvas></div>
    </div>
  </div>

  <!-- TOP PRODUTOS -->
  <div id="produtos" class="mb-5">
    <h3>🥇 Top Produtos</h3>
    <table class="table table-dark table-hover">
      <thead><tr><th>Produto</th><th>Qtd</th><th>Total R$</th></tr></thead>
      <tbody>
        <?php foreach($topProdutos as $t):?>
          <tr><td><?=$t['nome']?></td><td><?=$t['qtd']?></td><td>R$ <?=number_format($t['total'],2,',','.')?></td></tr>
        <?php endforeach;?>
      </tbody>
    </table>
  </div>

  <!-- PEDIDOS -->
  <div id="pedidos">
    <h3>📑 Pedidos</h3>
    <table id="tabela" class="table table-striped table-bordered">
      <thead class="table-dark"><tr><th>ID</th><th>Cliente</th><th>Data</th><th>Total</th><th>Pagamento</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach($pedidos as $p):?>
          <tr>
            <td>#<?=$p['id']?></td>
            <td><?=htmlspecialchars($p['cliente_nome'])?></td>
            <td><?=date('d/m/Y H:i',strtotime($p['data']))?></td>
            <td>R$ <?=number_format($p['total'],2,',','.')?></td>
            <td><?=$p['forma_pagamento']?></td>
            <td><?=$p['status']?></td>
          </tr>
        <?php endforeach;?>
      </tbody>
    </table>
  </div>

</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>

<script>
// DataTable
$('#tabela').DataTable({
  order:[[2,'desc']],
  language:{url:"https://cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json"},
  dom:'Bfrtip',
  buttons:[
    {extend:'pdfHtml5',text:'📄 PDF',title:'Relatório de Vendas'},
    {extend:'excelHtml5',text:'📊 Excel',title:'Relatório de Vendas'},
    {extend:'print',text:'🖨️ Imprimir'}
  ]
});

// Gráficos
new Chart(document.getElementById('grafPagto'),{
  type:'pie',
  data:{
    labels:['Pix','Cartão','Dinheiro'],
    datasets:[{data:[<?=$pix?>,<?=$cartao?>,<?=$dinheiro?>],backgroundColor:['#06b6d4','#fbbf24','#f87171']}]
  }
});
<?php
// montar vendas por dia
$qDias = $conn->query("SELECT DATE(data) d, SUM(total) v FROM pedidos $whereSql GROUP BY d ORDER BY d");
$labels=[];$values=[];
while($r=$qDias->fetch_assoc()){ $labels[]=$r['d']; $values[]=$r['v'];}
?>
new Chart(document.getElementById('grafVendasDia'),{
  type:'bar',
  data:{labels:<?=json_encode($labels)?>,datasets:[{label:'Vendas R$',data:<?=json_encode($values)?>,backgroundColor:'#2563eb'}]}
});
</script>
</body>
</html>
