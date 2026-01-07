<?php
// admin/pedidos_lista.php
session_start();
require_once __DIR__ . '/../backend/conexao.php';

// só acessa se for admin
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    http_response_code(403);
    exit('Acesso negado');
}

$ultimo = isset($_GET['ultimo']) ? (int)$_GET['ultimo'] : 0;

// Busca pedidos mais novos que o último ID
$sql = "SELECT * FROM pedidos WHERE id > $ultimo ORDER BY id DESC";
$res = $conn->query($sql);

if(!$res || $res->num_rows==0){
    // nada novo
    exit('');
}

// Gera somente <table> (sem o cabeçalho inteiro)
?>
<table class="table table-hover table-striped mb-0 align-middle">
  <thead class="table-dark">
    <tr>
      <th>ID</th>
      <th>Cliente</th>
      <th>Endereço</th>
      <th>Pagamento</th>
      <th>Total</th>
      <th>Status</th>
      <th class="text-center">Ações</th>
    </tr>
  </thead>
  <tbody>
    <?php while($p = $res->fetch_assoc()): ?>
      <tr class="novo-pedido">
        <td>#<?=$p['id']?></td>
        <td><?=htmlspecialchars($p['cliente_nome'])?></td>
        <td><?=htmlspecialchars($p['cliente_endereco'])?></td>
        <td><?=htmlspecialchars($p['forma_pagamento'])?></td>
        <td>R$ <?=number_format($p['total'],2,",",".")?></td>
        <td>
          <form method="post" action="update_status.php">
            <input type="hidden" name="id" value="<?=$p['id']?>">
            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
              <option <?=$p['status']=='Recebido'?'selected':''?>>Recebido</option>
              <option <?=$p['status']=='Em preparo'?'selected':''?>>Em preparo</option>
              <option <?=$p['status']=='Saiu para entrega'?'selected':''?>>Saiu para entrega</option>
              <option <?=$p['status']=='Entregue'?'selected':''?>>Entregue</option>
              <option <?=$p['status']=='Cancelado'?'selected':''?>>Cancelado</option>
            </select>
          </form>
        </td>
        <td class="text-center">
          <div class="btn-group btn-group-sm" role="group">
            <a href="imprimir.php?id=<?=$p['id']?>" target="_blank" class="btn btn-primary">🖨 Imprimir</a>
            <a href="pedidos_editar.php?id=<?=$p['id']?>" class="btn btn-secondary">✏️ Editar</a>
            <form method="post" action="update_status.php" style="display:inline">
              <input type="hidden" name="id" value="<?=$p['id']?>">
              <input type="hidden" name="status" value="Cancelado">
              <button type="submit" class="btn btn-warning">❌ Cancelar</button>
            </form>
            <a href="excluir_pedido.php?id=<?=$p['id']?>" class="btn btn-danger"
               onclick="return confirm('Excluir pedido #<?=$p['id']?>?')">🗑 Excluir</a>
          </div>
        </td>
      </tr>
    <?php endwhile; ?>
  </tbody>
</table>
