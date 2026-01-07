<?php
require_once __DIR__ . '/backend/conexao.php';

// Verifica ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Pedido inválido.");
}
$id = (int)$_GET['id'];

// Busca pedido
$stmt = $conn->prepare("SELECT cliente_nome, total, status, data FROM pedidos WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$pedido = $stmt->get_result()->fetch_assoc();
$stmt->close();

if(!$pedido){ die("Pedido não encontrado."); }
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Status do Pedido #<?=$id?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body{font-family:Arial,sans-serif;background:#f9fafb;margin:0;padding:20px;text-align:center}
    h1{color:#111}
    .status{font-size:20px;margin:20px 0;font-weight:bold}
    .etapa{padding:10px;margin:10px auto;max-width:300px;border-radius:8px}
    .ativo{background:#2563eb;color:#fff}
    .inativo{background:#e5e7eb;color:#111}
  </style>
</head>
<body>
  <h1>📦 Pedido #<?=$id?></h1>
  <p><b>Cliente:</b> <?=htmlspecialchars($pedido['cliente_nome'])?></p>
  <p><b>Total:</b> R$ <?=number_format($pedido['total'],2,",",".")?></p>
  <p><b>Data:</b> <?=date("d/m/Y H:i", strtotime($pedido['data']))?></p>

  <div class="status">Status atual: <?=htmlspecialchars($pedido['status'])?></div>

  <div class="etapa <?=($pedido['status']=="Em preparo"?"ativo":"inativo")?>">🍳 Em preparo</div>
  <div class="etapa <?=($pedido['status']=="Saiu para entrega"?"ativo":"inativo")?>">🛵 Saiu para entrega</div>
  <div class="etapa <?=($pedido['status']=="Concluído"?"ativo":"inativo")?>">✅ Concluído</div>

  <script>
    // Atualiza a cada 15 segundos
    setInterval(()=>{
      fetch("pedido_status_api.php?id=<?=$id?>")
      .then(r=>r.json())
      .then(d=>{
        document.querySelector(".status").innerText = "Status atual: "+d.status;
        document.querySelectorAll(".etapa").forEach(e=>e.classList.remove("ativo"));
        if(d.status=="Em preparo") document.querySelectorAll(".etapa")[0].classList.add("ativo");
        if(d.status=="Saiu para entrega") document.querySelectorAll(".etapa")[1].classList.add("ativo");
        if(d.status=="Concluído") document.querySelectorAll(".etapa")[2].classList.add("ativo");
      });
    },15000);
  </script>
</body>
</html>
