<?php
session_start();
require_once __DIR__ . '/../backend/conexao.php';

/* ================================
   DEBUG — MOSTRAR ERROS NA TELA
=================================*/
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
error_reporting(E_ALL);
ini_set('display_errors', 1);

/* ================================
   VERIFICAÇÃO DE LOGIN
=================================*/
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit;
}

/* ================================
   CONSULTA PRINCIPAL (CORRIGIDA)
=================================*/

$sql = "
SELECT 
    p.id,
    p.cliente_nome,
    p.cliente_endereco,
    p.forma_pagamento,
    p.total,
    p.custo_total,
    p.status,
    p.data,

    (
        SELECT GROUP_CONCAT(
            CONCAT(
                '🧾 <b>Item</b><br>',
                ip.quantidade, 'x ', pr.nome, '<br>',

                IF(ip.adicionais IS NOT NULL AND ip.adicionais <> '',
                    CONCAT('<span style=\"color:#065f46;\">➕ Acrescentar: ', ip.adicionais, '</span><br>'),
                    ''
                ),

                IF(ip.remocoes IS NOT NULL AND ip.remocoes <> '',
                    CONCAT('<span style=\"color:#b91c1c;\">❌ Sem: ', ip.remocoes, '</span><br>'),
                    ''
                ),

                IF(ip.observacao IS NOT NULL AND ip.observacao <> '',
                    CONCAT('<span style=\"color:#1e3a8a;\">📝 Obs: ', ip.observacao, '</span><br>'),
                    ''
                ),

                '<b>💵 Total do item: R$ ', FORMAT(ip.preco * ip.quantidade, 2, 'pt_BR'), '</b><br>',
                '<div class=\"item-sep\"></div>'
            )
            SEPARATOR ''
        )
        FROM itens_pedido ip
        JOIN produtos pr ON pr.id = ip.produto_id
        WHERE ip.pedido_id = p.id
    ) AS itens

FROM pedidos p
ORDER BY p.id DESC
LIMIT 100
";

$res = $conn->query($sql);
$pedidos = $res->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Painel de Pedidos - Mimoso Lanches</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

body {
    background: #f4f6f8;
}

/* tabela */
.table td {
    vertical-align: top !important;
}

/* separador entre os itens */
.item-sep {
    border-bottom: 1px dashed #bbb;
    margin: 8px 0 12px 0;
}

/* cards internos dos itens */
.item-card {
    padding: 8px;
    background: #fff;
    border-radius: 8px;
    margin-bottom: 10px;
    border: 1px solid #eee;
}

/* status */
.status-box {
    padding: 6px 10px;
    color: #fff;
    border-radius: 6px;
    font-weight: bold;
    text-align: center;
}

.Recebido      { background:#3b82f6; }
.Em\.preparo   { background:#f59e0b; }
.Saiu\.para\.entrega { background:#6366f1; }
.Entregue      { background:#10b981; }
.Cancelado     { background:#ef4444; }

.btn-group-sm .btn {
    margin-right: 4px;
}

</style>
</head>
<body>

<nav class="navbar navbar-dark bg-dark mb-4">
  <div class="container-fluid">
    <span class="navbar-brand">📦 Painel de Pedidos</span>
    <a class="btn btn-danger btn-sm" href="logout.php">Sair</a>
  </div>
</nav>

<div class="container">

<div class="card shadow-sm">
  <div class="card-header bg-dark text-white">
    Últimos Pedidos
  </div>

  <div class="card-body p-0">

    <div class="table-responsive">
      <table class="table table-striped table-hover mb-0">
        <thead class="table-dark">
        <tr>
            <th>ID</th>
            <th>Cliente</th>
            <th>Endereço</th>
            <th>Pagamento</th>
            <th>Total</th>
            <th>Custo</th>
            <th width="30%">Itens</th>
            <th>Status</th>
            <th class="text-center">Ações</th>
        </tr>
        </thead>

<tbody>

<?php foreach($pedidos as $p): ?>
<tr>

<td><b>#<?=$p['id']?></b></td>

<td><?=htmlspecialchars($p['cliente_nome'])?></td>

<td><?=htmlspecialchars($p['cliente_endereco'])?></td>

<td><?=htmlspecialchars($p['forma_pagamento'])?></td>

<td><b>R$ <?=number_format($p['total'],2,',','.')?></b></td>

<td>R$ <?=number_format($p['custo_total'] ?? 0,2,',','.')?></td>

<td><?=$p['itens']?></td>

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

    <div class="btn-group btn-group-sm">

        <a class="btn btn-primary" href="imprimir.php?id=<?=$p['id']?>" target="_blank">
            🖨 Imprimir
        </a>

        <a class="btn btn-secondary" href="pedidos_editar.php?id=<?=$p['id']?>">
            ✏️ Editar
        </a>

        <form method="post" action="update_status.php" style="display:inline;">
            <input type="hidden" name="id" value="<?=$p['id']?>">
            <input type="hidden" name="status" value="Cancelado">
            <button class="btn btn-warning" onclick="return confirm('Cancelar pedido?')">
                ❌ Cancelar
            </button>
        </form>

        <a class="btn btn-danger" href="excluir_pedido.php?id=<?=$p['id']?>"
           onclick="return confirm('Excluir pedido #<?=$p['id']?>?')">
            🗑 Excluir
        </a>

    </div>

</td>

</tr>
<?php endforeach; ?>
</tbody>

      </table>
    </div>

  </div>
</div>

<footer class="text-center mt-3 text-muted">
  Mimoso Lanches © 2025  
</footer>

</div>

</body>
</html>
