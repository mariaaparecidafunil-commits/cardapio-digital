<?php
require_once __DIR__ . '/../backend/conexao.php';
require_once __DIR__ . '/layout.php';

// --- Estatísticas seguras (não quebra se tabelas não existirem) ---
$totalPedidosHoje = 0;
$faturamentoHoje = 0;
$produtosVendidos = 0;

try {
    $q1 = $conn->query("SHOW TABLES LIKE 'pedidos'");
    if($q1 && $q1->num_rows>0){
        $totalPedidosHoje = $conn->query("SELECT COUNT(*) FROM pedidos WHERE DATE(data)=CURDATE()")->fetch_row()[0] ?? 0;
        $faturamentoHoje = $conn->query("SELECT SUM(total) FROM pedidos WHERE DATE(data)=CURDATE()")->fetch_row()[0] ?? 0;
    }
    $q2 = $conn->query("SHOW TABLES LIKE 'itens_pedido'");
    if($q2 && $q2->num_rows>0){
        $produtosVendidos = $conn->query("SELECT SUM(qtd) FROM itens_pedido WHERE DATE(data)=CURDATE()")->fetch_row()[0] ?? 0;
    }
} catch(Exception $e){}

// --- Dados fake para gráfico (evita erro) ---
$labels = [];
$valores = [];
for ($i=6;$i>=0;$i--){
    $dia = date('Y-m-d',strtotime("-$i days"));
    $labels[] = date('d/m',strtotime($dia));
    $valores[] = 0; // valores padrão até conectar com as tabelas reais
}
?>

<div class="main">

<div class="container mt-4">

  <div class="row g-3">
    <div class="col-md-4">
      <div class="card text-bg-primary shadow-sm">
        <div class="card-body">
          <h5 class="card-title">Pedidos de Hoje</h5>
          <h2><?=$totalPedidosHoje?></h2>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card text-bg-success shadow-sm">
        <div class="card-body">
          <h5 class="card-title">Faturamento Hoje</h5>
          <h2>R$ <?=number_format($faturamentoHoje,2,',','.')?></h2>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card text-bg-warning shadow-sm">
        <div class="card-body">
          <h5 class="card-title">Itens Vendidos Hoje</h5>
          <h2><?=$produtosVendidos?></h2>
        </div>
      </div>
    </div>
  </div>

  <div class="card mt-4 shadow-sm">
    <div class="card-body">
      <h5 class="card-title">Faturamento Últimos 7 Dias</h5>
      <canvas id="graficoVendas"></canvas>
    </div>
  </div>

</div>

<footer class="text-center text-muted mt-4">
  2025 ® Mimoso Lanches – Todos os direitos reservados<br>
  Webdesigner José Luiz - Amo Mimoso do Sul
</footer>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('graficoVendas');
new Chart(ctx, {
  type: 'line',
  data: {
    labels: <?=json_encode($labels)?>,
    datasets: [{
      label: 'Faturamento (R$)',
      data: <?=json_encode($valores)?>,
      borderColor: '#2563eb',
      backgroundColor: 'rgba(37,99,235,0.2)',
      borderWidth: 2,
      fill: true,
      tension: .3
    }]
  },
  options: {
    scales: { y: {beginAtZero: true} }
  }
});
</script>

</div> <!-- fecha .main -->
</body>
</html>
