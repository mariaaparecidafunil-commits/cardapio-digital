<?php
// status.php
require_once __DIR__ . '/backend/conexao.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Pedido inválido.");
}
$pedido_id = (int)$_GET['id'];

// Busca pedido
$stmt = $conn->prepare("SELECT * FROM pedidos WHERE id=? LIMIT 1");
$stmt->bind_param("i", $pedido_id);
$stmt->execute();
$pedido = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pedido) {
    die("Pedido não encontrado.");
}

// Status atual
$statusAtual = strtolower($pedido['status']);

// Lista de status em ordem
$statusEtapas = [
    'recebido' => '✅ Pedido recebido',
    'em preparo' => '🍳 Em preparo',
    'saiu para entrega' => '🚴 Saiu para entrega',
    'entregue' => '🍔 Entregue',
    'cancelado' => '❌ Cancelado'
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Acompanhe seu pedido - Mimoso Lanches</title>
  <style>
    body { font-family: Arial, sans-serif; background:#f4f4f9; margin:0; padding:20px; }
    .box { max-width:600px; margin:auto; background:#fff; padding:20px; border-radius:10px; box-shadow:0 0 15px rgba(0,0,0,0.1); }
    h1 { text-align:center; color:#d35400; margin-bottom:10px; }
    .info { text-align:center; margin-bottom:20px; }
    .status { text-align:center; font-size:20px; margin:20px 0; font-weight:bold; }
    .timeline { list-style:none; padding:0; margin:20px 0; }
    .timeline li { padding:12px; border-left:5px solid #ccc; margin-bottom:10px; background:#fafafa; border-radius:5px; }
    .done { border-color:green; color:green; font-weight:bold; background:#e9f9e9; }
    .active { border-color:#d35400; color:#d35400; font-weight:bold; background:#fff5ec; }
    footer { text-align:center; font-size:13px; color:#888; margin-top:30px; }
  </style>
  <script>
    // Atualiza status a cada 10 segundos
    setInterval(() => {
      fetch("status_api.php?id=<?= $pedido_id ?>")
        .then(r => r.json())
        .then(data => {
          if(data.status){
            document.getElementById("status").innerText = "Status atual: " + data.status;
          }
        });
    }, 10000);
  </script>
</head>
<body>
  <div class="box">
    <h1>🍔 Mimoso Lanches</h1>
    <div class="info">
      <p><strong>Pedido nº:</strong> <?= $pedido['id'] ?></p>
      <p><strong>Cliente:</strong> <?= htmlspecialchars($pedido['cliente_nome']) ?></p>
      <p><strong>Total:</strong> R$ <?= number_format($pedido['total'],2,",",".") ?></p>
    </div>

    <div class="status" id="status">
      Status atual: <?= ucfirst($statusAtual) ?>
    </div>

    <ul class="timeline">
      <?php
      $marcar = true;
      foreach ($statusEtapas as $chave => $rotulo) {
          $classe = "";
          if ($chave === $statusAtual) {
              $classe = "active";
              $marcar = false;
          } elseif ($marcar) {
              $classe = "done";
          }
          echo "<li class='$classe'>$rotulo</li>";
          if ($chave === $statusAtual) { $marcar = false; }
      }
      ?>
    </ul>
  </div>
  <footer>
    © <?= date("Y") ?> Mimoso Lanches - Todos os direitos reservados
  </footer>
</body>
</html>
