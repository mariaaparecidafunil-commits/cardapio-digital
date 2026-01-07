<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/../backend/conexao.php';

// 🔒 Segurança
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login.php");
    exit;
}

// =================== MÉTRICAS ===================
function contar($conn, $tabela, $where = "1=1") {
    $res = $conn->query("SELECT COUNT(*) AS t FROM $tabela WHERE $where");
    $row = $res ? $res->fetch_assoc() : ['t'=>0];
    return $row['t'];
}
$totalProdutos   = contar($conn, "produtos");
$totalCategorias = contar($conn, "categorias");
$totalPedidos    = contar($conn, "pedidos");
$pedidosHoje     = contar($conn, "pedidos", "DATE(data)=CURDATE()");

// =================== GRÁFICO ===================
$sql = "
SELECT DATE(data) AS dia, COUNT(*) AS qtd, SUM(total) AS valor
FROM pedidos
WHERE data >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
GROUP BY DATE(data)
ORDER BY dia ASC";
$res = $conn->query($sql);

$labels = $qtds = $valores = [];
if($res){
  while($r = $res->fetch_assoc()){
    $labels[]  = $r['dia'];
    $qtds[]    = (int)$r['qtd'];
    $valores[] = (float)$r['valor'];
  }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Painel Administrativo — Mimoso Lanches</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
:root {
  --azul:#1E3A8A;
  --azul-claro:#2563EB;
  --cinza-claro:#f4f6f9;
  --escuro:#0f172a;
  --texto:#1f2937;
}
body {
  margin:0;
  font-family:'Segoe UI',sans-serif;
  background:var(--cinza-claro);
  transition:background .3s,color .3s;
  display:flex;
  height:100vh;
  overflow:hidden;
}
.dark-mode {
  background:#0f172a;
  color:#f3f4f6;
}
.sidebar {
  width:240px;
  background:#111827;
  color:#fff;
  display:flex;
  flex-direction:column;
  padding-top:20px;
  transition:all .3s;
  position:fixed;
  top:0;
  bottom:0;
  left:0;
  z-index:1000;
}
.sidebar.collapsed {
  width:70px;
}
.sidebar h2 {
  font-size:18px;
  margin:0 0 20px 20px;
}
.sidebar a {
  color:#fff;
  text-decoration:none;
  padding:12px 20px;
  display:flex;
  align-items:center;
  gap:12px;
  font-size:15px;
  transition:.2s;
}
.sidebar a:hover {
  background:var(--azul);
}
.sidebar.collapsed a span {display:none;}
.sidebar.collapsed a {justify-content:center;}
.topbar {
  height:60px;
  background:var(--azul);
  color:#fff;
  position:fixed;
  left:240px;
  right:0;
  display:flex;
  align-items:center;
  justify-content:space-between;
  padding:0 20px;
  transition:left .3s;
}
.sidebar.collapsed ~ .topbar {
  left:70px;
}
.topbar .icons {
  display:flex;
  align-items:center;
  gap:15px;
}
.topbar button {
  background:none;
  border:none;
  color:#fff;
  font-size:20px;
}
.main {
  margin-left:240px;
  margin-top:60px;
  padding:30px;
  width:100%;
  overflow-y:auto;
  transition:margin-left .3s;
}
.sidebar.collapsed ~ .main {
  margin-left:70px;
}
.cards {
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(230px,1fr));
  gap:20px;
  margin-bottom:30px;
}
.card-box {
  background:#fff;
  border-radius:10px;
  padding:25px;
  text-align:center;
  box-shadow:0 2px 6px rgba(0,0,0,.1);
  transition:.3s;
}
.card-box:hover {
  transform:translateY(-3px);
  box-shadow:0 4px 10px rgba(0,0,0,.15);
}
.card-box h2 {font-size:32px;color:var(--azul-claro);margin:0;}
.card-box p {margin-top:8px;color:#555;}
.dark-mode .card-box {background:#1e293b;color:#e2e8f0;}
canvas {
  background:#fff;
  border-radius:10px;
  box-shadow:0 2px 6px rgba(0,0,0,.1);
}
.dark-mode canvas {background:#1e293b;}
footer {
  margin-top:40px;
  text-align:center;
  font-size:14px;
  color:#666;
}
.dark-mode footer {color:#9ca3af;}
</style>
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
  <h2><i class="bi bi-shop"></i> <span>Mimoso Lanches</span></h2>
  <a href="produtos.php"><i class="bi bi-box-seam"></i><span>Produtos</span></a>
  <a href="categorias.php"><i class="bi bi-folder2-open"></i><span>Categorias</span></a>
  <a href="pedidos.php"><i class="bi bi-receipt"></i><span>Pedidos</span></a>
  <a href="relatorios.php"><i class="bi bi-bar-chart"></i><span>Relatórios</span></a>
  <a href="financeiro.php"><i class="bi bi-cash-coin"></i><span>Financeiro</span></a>
  <a href="insumos.php"><i class="bi bi-basket"></i><span>Insumos</span></a>
  <a href="variacoes.php"><i class="bi bi-sliders"></i><span>Variações</span></a>
  <a href="opcionais.php"><i class="bi bi-plus-circle"></i><span>Opcionais</span></a>
  <a href="composicao.php"><i class="bi bi-diagram-3"></i><span>Custos</span></a>
  <a href="../financeiro/relatorio_financeiro.php" target="_blank"><i class="bi bi-clipboard-data"></i><span>Relatório Financeiro</span></a>
</aside>

<!-- Topbar -->
<header class="topbar" id="topbar">
  <button id="toggleSidebar"><i class="bi bi-list"></i></button>
  <h5 class="m-0">Painel Administrativo</h5>
  <div class="icons">
    <button id="toggleTheme" title="Alternar tema"><i class="bi bi-moon-stars"></i></button>
    <a href="logout.php" class="text-white" title="Sair"><i class="bi bi-box-arrow-right"></i></a>
  </div>
</header>

<!-- Conteúdo -->
<main class="main" id="main">
  <div class="cards">
    <div class="card-box"><h2><?= $totalProdutos ?></h2><p>Produtos</p></div>
    <div class="card-box"><h2><?= $totalCategorias ?></h2><p>Categorias</p></div>
    <div class="card-box"><h2><?= $totalPedidos ?></h2><p>Pedidos Totais</p></div>
    <div class="card-box"><h2><?= $pedidosHoje ?></h2><p>Pedidos Hoje</p></div>
  </div>

  <h5 class="mb-3">📈 Pedidos e Vendas — Últimos 7 dias</h5>
  <canvas id="grafico"></canvas>

  <footer class="mt-4">Mimoso Lanches © 2025 — Painel Profissional</footer>
</main>

<script>
const sidebar = document.getElementById('sidebar');
const main = document.getElementById('main');
const topbar = document.getElementById('topbar');
document.getElementById('toggleSidebar').onclick = () => {
  sidebar.classList.toggle('collapsed');
};

const themeBtn = document.getElementById('toggleTheme');
themeBtn.onclick = () => {
  document.body.classList.toggle('dark-mode');
  themeBtn.innerHTML = document.body.classList.contains('dark-mode')
    ? '<i class="bi bi-brightness-high"></i>'
    : '<i class="bi bi-moon-stars"></i>';
};

new Chart(document.getElementById('grafico'), {
  type: 'line',
  data: {
    labels: <?= json_encode($labels) ?>,
    datasets: [
      {label:'Pedidos', data:<?= json_encode($qtds) ?>, borderColor:'#2563eb', backgroundColor:'rgba(37,99,235,0.25)', tension:0.3, fill:true},
      {label:'Vendas (R$)', data:<?= json_encode($valores) ?>, borderColor:'#f59e0b', backgroundColor:'rgba(245,158,11,0.25)', tension:0.3, fill:true, yAxisID:'y1'}
    ]
  },
  options: {
    responsive:true,
    scales:{
      y:{beginAtZero:true, title:{display:true,text:'Pedidos'}},
      y1:{beginAtZero:true, position:'right', title:{display:true,text:'Vendas (R$)'}}
    }
  }
});
</script>
</body>
</html>
