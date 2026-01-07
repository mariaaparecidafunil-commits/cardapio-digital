<?php
session_start();
require_once __DIR__ . '/../backend/conexao.php';

if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    http_response_code(403);
    exit('Não autorizado');
}

$ultimo = isset($_GET['ultimo']) ? (int)$_GET['ultimo'] : 0;

$sql = "SELECT * FROM pedidos WHERE id > ? ORDER BY id DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $ultimo);
$stmt->execute();
$res = $stmt->get_result();

while ($p = $res->fetch_assoc()) {
    echo '<tr>';
    echo '<td>#'.$p['id'].'</td>';
    echo '<td>'.htmlspecialchars($p['cliente_nome']).'</td>';
    echo '<td>'.htmlspecialchars($p['cliente_endereco']).'</td>';
    echo '<td>'.htmlspecialchars($p['forma_pagamento']).'</td>';
    echo '<td>R$ '.number_format($p['total'],2,",",".").'</td>';
    echo '<td>
            <form method="post" action="update_status.php">
              <input type="hidden" name="id" value="'.$p['id'].'">
              <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                <option '.($p['status']=='Recebido'?'selected':'').'>Recebido</option>
                <option '.($p['status']=='Em preparo'?'selected':'').'>Em preparo</option>
                <option '.($p['status']=='Saiu para entrega'?'selected':'').'>Saiu para entrega</option>
                <option '.($p['status']=='Entregue'?'selected':'').'>Entregue</option>
                <option '.($p['status']=='Cancelado'?'selected':'').'>Cancelado</option>
              </select>
            </form>
          </td>';
    echo '<td class="text-center">
            <div class="btn-group btn-group-sm" role="group">
              <a href="imprimir_termica.php?id='.$p['id'].'" target="_blank" class="btn btn-primary">🖨 Imprimir</a>
              <a href="pedidos_editar.php?id='.$p['id'].'" class="btn btn-secondary">✏️ Editar</a>
              <form method="post" action="update_status.php" style="display:inline">
                <input type="hidden" name="id" value="'.$p['id'].'">
                <input type="hidden" name="status" value="Cancelado">
                <button type="submit" class="btn btn-warning">❌ Cancelar</button>
              </form>
              <a href="excluir_pedido.php?id='.$p['id'].'" class="btn btn-danger"
                 onclick="return confirm(\'Excluir pedido #'.$p['id'].'?\')">🗑 Excluir</a>
            </div>
          </td>';
    echo '</tr>';
}

$stmt->close();
