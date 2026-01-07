<?php
session_start();
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);
require_once __DIR__ . '/../backend/conexao.php';

// 🔒 Verifica login
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit;
}

// ======================
// FILTROS DE DATA
// ======================
$inicio = $_GET['inicio'] ?? date('Y-m-01');
$fim    = $_GET['fim'] ?? date('Y-m-t');

// Vendas totais e pedidos
$sql = "SELECT 
            COUNT(*) as qtd_pedidos,
            SUM(total) as total_vendas,
            SUM(CASE WHEN forma_pagamento='Pix' THEN total ELSE 0 END) as total_pix,
            SUM(CASE WHEN forma_pagamento='Cartão' THEN total ELSE 0 END) as total_cartao,
            SUM(CASE WHEN forma_pagamento='Dinheiro' THEN total ELSE 0 END) as total_dinheiro
        FROM pedidos 
        WHERE DATE(data) BETWEEN ? AND ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $inicio, $fim);
$stmt->execute();
$resumo = $stmt->get_result()->fetch_assoc();

$totalPedidos = $resumo['qtd_pedidos'] ?? 0;
$totalVendas  = $resumo['total_vendas'] ?? 0;
$totalPix     = $resumo['total_pix'] ?? 0;
$totalCartao  = $resumo['total_cartao'] ?? 0;
$totalDinheiro= $resumo['total_dinheiro'] ?? 0;

$ticketMedio  = $totalPedidos>0 ? $totalVendas/$totalPedidos : 0;

// === Cálculo de lucro
$custoVariavel = $totalVendas * 0.55; // se lucro bruto é 45% então custo variável é 55%
$custosFixos   = 3050.00;             // informado por você
$lucroBruto    = $totalVendas - $custoVariavel;
$lucroLiquido  = $lucroBruto - $custosFixos;
$margemLucro   = $totalVendas>0 ? ($lucroLiquido/$totalVendas*100) : 0;

// === Faturamento diário para gráfico
$sqlDias = "SELECT DATE(data) as dia, SUM(total) as total FROM pedidos 
            WHERE DATE(data) BETWEEN ? AND ?
            GROUP BY DATE(data) ORDER BY dia";
$stmt = $conn->prepare($sqlDias);
$stmt->bind_param('ss',$inicio,$fim);
$stmt->execute();
$res = $stmt->get_result();
$faturamentoDias = [];
while($row = $res->fetch_assoc()){
    $faturamentoDias[$row['dia']] = (float)$row['total'];
}

// === Produtos mais rentáveis (aqui só considera total vendido porque não temos custo por produto ainda)
$sqlProd = "SELECT p.nome, SUM(i.quantidade) as qtd, SUM(i.preco*i.quantidade) as total 
            FROM itens_pedido i
            JOIN produtos p ON p.id=i.produto_id
            JOIN pedidos pe ON pe.id=i.pedido_id
            WHERE DATE(pe.data) BETWEEN ? AND ?
            GROUP BY p.id ORDER BY total DESC LIMIT 5";
$stmt = $conn->prepare($sqlProd);
$stmt->bind_param('ss',$inicio,$fim);
$stmt->execute();
$resTop = $stmt->get_result();
$topProdutos = $resTop->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Financeiro - Mimoso Lanches</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
<style>
body{background:#111827;color:#f9fafb;display:flex;}
aside{width:220px;background:#1f2937;min-height:100vh;padding-top:20px;}
aside a{display:block;padding:10px 16px;color:#f9fafb;text-decoration:none;}
aside a:hover{background:#374151;}
aside .active{background:#ff6b00;}
main{flex:1;padding:20px;}
.card-resumo{background:#1f2937;padding:16px;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,.3);text-align:center;}
.card-resumo h3{margin:0;font-size:1.8rem;}
header h1{font-size:1.5rem;margin-bottom:20px;}
</style>
</head>
<body>

<aside>
  <div class="px-3 mb-4"><strong>Mimoso Lanches</strong></div>
  <a href="index.php">🏠 Dashboard</a>
  <a href="pedidos.php">📝 Pedidos</a>
  <a href="produtos.php">🍔 Produtos</a>
  <a href="categorias.php">📂 Categorias</a>
  <a href="opcionais.php">⚙️ Opcionais</a>
  <a href="relatorios.php">📊 Relatórios</a>
  <a class="active" href="financeiro.php">💰 Financeiro</a>
</aside>

<main>
<header>
  <h1>💰 Financeiro</h1>
</header>

<form method="get" class="row g-2 mb-4">
  <div class="col-md-3">
    <label class="form-label">De</label>
    <input type="date" name="inicio" value="<?= htmlspecialchars($inicio) ?>" class="form-control">
  </div>
  <div class="col-md-3">
    <label class="form-label">Até</label>
    <input type="date" name="fim" value="<?= htmlspecialchars($fim) ?>" class="form-control">
  </div>
  <div class="col-md-3 d-flex align-items-end">
    <button class="btn btn-primary w-100">Filtrar</button>
  </div>
</form>

<div class="row g-3 mb-4">
  <div class="col-md-3"><div class="card-resumo"><h5>Vendas</h5><h3>R$ <?= number_format($totalVendas,2,',','.') ?></h3></div></div>
  <div class="col-md-3"><div class="card-resumo"><h5>Pedidos</h5><h3><?= $totalPedidos ?></h3></div></div>
  <div class="col-md-3"><div class="card-resumo"><h5>Ticket Médio</h5><h3>R$ <?= number_format($ticketMedio,2,',','.') ?></h3></div></div>
  <div class="col-md-3"><div class="card-resumo"><h5>Lucro Líquido</h5><h3>R$ <?= number_format($lucroLiquido,2,',','.') ?></h3></div></div>
</div>

<div class="row g-4 mb-4">
  <div class="col-md-6"><canvas id="graficoFaturamento"></canvas></div>
  <div class="col-md-6"><canvas id="graficoPagamento"></canvas></div>
</div>

<h3 class="mt-4 mb-3">🥇 Top Produtos</h3>
<table id="tabela" class="table table-hover table-bordered table-dark">
  <thead><tr><th>Produto</th><th>Quantidade</th><th>Total R$</th></tr></thead>
  <tbody>
  <?php foreach($topProdutos as $p): ?>
    <tr>
      <td><?= htmlspecialchars($p['nome']) ?></td>
      <td><?= $p['qtd'] ?></td>
      <td>R$ <?= number_format($p['total'],2,',','.') ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

</main>

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
// ======== Gráficos ========
const faturamentoDias = <?= json_encode($faturamentoDias) ?>;
const labels = Object.keys(faturamentoDias);
const data = Object.values(faturamentoDias);

new Chart(document.getElementById('graficoFaturamento'), {
  type: 'line',
  data: {labels: labels, datasets: [{label:'Faturamento Diário (R$)',data:data,borderColor:'#ff6b00',backgroundColor:'rgba(255,107,0,0.2)',fill:true,tension:0.3}]},
  options:{scales:{y:{beginAtZero:true}}}
});

new Chart(document.getElementById('graficoPagamento'), {
  type: 'pie',
  data:{
    labels: ["Pix","Cartão","Dinheiro"],
    datasets:[{data:[<?= $totalPix ?>,<?= $totalCartao ?>,<?= $totalDinheiro ?>],backgroundColor:['#22c55e','#3b82f6','#facc15']}]
  }
});

// ======== DataTable ========
$('#tabela').DataTable({
  language:{url:"https://cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json"},
  dom:'Bfrtip',
  buttons:[
    { extend:'pdfHtml5', text:'📄 PDF', title:'Relatório Financeiro' },
    { extend:'excelHtml5', text:'📊 Excel', title:'Relatório Financeiro' },
    { extend:'print', text:'🖨️ Imprimir' }
  ]
});
</script>
</body>
</html>
