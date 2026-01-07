<?php
require_once __DIR__ . '/backend/conexao.php';

// ========================
// BUSCA TODOS OS PEDIDOS
// ========================
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
                    ip.quantidade, 'x ', pr.nome,

                    -- ➕ ACRESCENTAR
                    IF(ip.adicionais IS NOT NULL AND ip.adicionais <> '',
                        CONCAT('<br><span style=\"color:#065f46;\">➕ Acrescentar ', ip.adicionais, '</span>'),
                        ''
                    ),

                    -- ❌ SEM
                    IF(ip.remocoes IS NOT NULL AND ip.remocoes <> '',
                        CONCAT('<br><span style=\"color:#b91c1c;\">❌ Sem ', ip.remocoes, '</span>'),
                        ''
                    ),

                    -- 📝 OBSERVAÇÃO
                    IF(ip.observacao IS NOT NULL AND ip.observacao <> '',
                        CONCAT('<br><span style=\"color:#1e3a8a;\">📝 ', ip.observacao, '</span>'),
                        ''
                    ),

                    CONCAT('<br>— R$ ', FORMAT(ip.preco * ip.quantidade, 2, 'pt_BR'))
                )
                SEPARATOR '<hr>'
            )
            FROM itens_pedido ip
            JOIN produtos pr ON pr.id = ip.produto_id
            WHERE ip.pedido_id = p.id
        ) AS itens_formatados
        
    FROM pedidos p
    ORDER BY p.id DESC
";

$pedidos = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Painel de Pedidos - Mimoso Lanches</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
body {
    font-family: Arial, Helvetica, sans-serif;
    background: #f3f4f6;
    margin: 0;
    padding: 20px;
}

h1 {
    margin-bottom: 20px;
}

table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 3px 10px rgba(0,0,0,0.07);
}

th {
    background: #374151;
    color: white;
    padding: 12px;
    text-align: left;
    font-size: 15px;
}

td {
    padding: 10px;
    border-bottom: 1px solid #e5e7eb;
    vertical-align: top;
}

.status {
    padding: 6px 10px;
    color: white;
    font-weight: bold;
    border-radius: 6px;
}

.Recebido { background: #6366f1; }
.Em\.preparo { background: #f59e0b; }
.Saiu\.para\.entrega { background: #2563eb; }
.Entregue { background: #10b981; }
.Cancelado { background: #b91c1c; }

hr {
    border: 0;
    border-top: 1px dashed #d1d5db;
    margin: 8px 0;
}
</style>

</head>
<body>

<h1>📦 Painel de Pedidos</h1>

<table>
<tr>
    <th>ID</th>
    <th>Cliente</th>
    <th>Endereço</th>
    <th>Pagamento</th>
    <th>Itens</th>
    <th>Total</th>
    <th>Status</th>
    <th>Data</th>
</tr>

<?php while($p = $pedidos->fetch_assoc()): ?>
<?php $statusClass = str_replace(" ", ".", $p['status']); ?>
<tr>

    <td><b>#<?= $p['id'] ?></b></td>

    <td><?= htmlspecialchars($p['cliente_nome']) ?></td>

    <td><?= htmlspecialchars($p['cliente_endereco']) ?></td>

    <td><?= htmlspecialchars($p['forma_pagamento']) ?></td>

    <td><?= $p['itens_formatados'] ?></td>

    <td><b>R$ <?= number_format($p['total'], 2, ',', '.') ?></b></td>

    <td>
        <span class="status <?= $statusClass ?>">
            <?= $p['status'] ?>
        </span>
    </td>

    <td><?= date("d/m/Y H:i", strtotime($p['data'])) ?></td>

</tr>
<?php endwhile; ?>

</table>

</body>
</html>
