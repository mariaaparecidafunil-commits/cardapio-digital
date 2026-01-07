<?php
session_start();
require_once __DIR__ . '/../backend/conexao.php';

// =====================================================
// 🔒 SEGURANÇA: Apenas admin
// =====================================================
if(!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']){
    header('Location: login.php');
    exit;
}

// =====================================================
// 📦 RECEBE PRODUTO E PREÇO
// =====================================================
$produtoId = isset($_GET['p']) ? intval($_GET['p']) : 0;
$precoVenda = isset($_GET['pv']) ? floatval(str_replace(',', '.', $_GET['pv'])) : 0;

// =====================================================
// 📋 LISTA DE PRODUTOS PARA SELEÇÃO
// =====================================================
$produtos = [];
$res = $conn->query("SELECT id, nome FROM produtos ORDER BY nome ASC");
if($res && $res->num_rows > 0){
    $produtos = $res->fetch_all(MYSQLI_ASSOC);
}

// =====================================================
// 🔧 AÇÕES DE CRUD (adicionar / editar / excluir)
// =====================================================
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $acao = $_POST['acao'] ?? '';
    $id = intval($_POST['id'] ?? 0);
    $insumo_id = intval($_POST['insumo_id'] ?? 0);
    $quantidade = floatval(str_replace(',', '.', $_POST['quantidade'] ?? 0));

    if($acao === 'add' && $produtoId > 0 && $insumo_id > 0){
        $stmt = $conn->prepare("INSERT INTO produto_insumo (produto_id, insumo_id, quantidade) VALUES (?,?,?)");
        $stmt->bind_param("iid", $produtoId, $insumo_id, $quantidade);
        $stmt->execute();
    }

    if($acao === 'edit' && $id > 0){
        $stmt = $conn->prepare("UPDATE produto_insumo SET insumo_id=?, quantidade=? WHERE id=?");
        $stmt->bind_param("idi", $insumo_id, $quantidade, $id);
        $stmt->execute();
    }

    if($acao === 'del' && $id > 0){
        $stmt = $conn->prepare("DELETE FROM produto_insumo WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }

    header("Location: composicao_crud.php?p=$produtoId&pv=$precoVenda");
    exit;
}

// =====================================================
// 📦 LISTA INSUMOS DISPONÍVEIS
// =====================================================
$insumos = [];
$res = $conn->query("SELECT id, nome, unidade, preco_unit FROM insumos ORDER BY nome ASC");
if($res && $res->num_rows > 0){
    $insumos = $res->fetch_all(MYSQLI_ASSOC);
}

// =====================================================
// 🧾 BUSCA INSUMOS DO PRODUTO
// =====================================================
$custoTotal = 0;
$lista = [];

if($produtoId > 0){
    $sql = "SELECT pi.id as link_id, i.nome, i.unidade, i.preco_unit, pi.quantidade
            FROM produto_insumo pi
            JOIN insumos i ON i.id = pi.insumo_id
            WHERE pi.produto_id = $produtoId";
    $res = $conn->query($sql);
    if($res && $res->num_rows > 0){
        while($r = $res->fetch_assoc()){
            $subtotal = $r['quantidade'] * $r['preco_unit'];
            $r['subtotal'] = $subtotal;
            $custoTotal += $subtotal;
            $lista[] = $r;
        }
    }
}

$lucro = ($precoVenda > 0) ? $precoVenda - $custoTotal : 0;
$margem = ($precoVenda > 0) ? round(($lucro / $precoVenda) * 100, 2) : 0;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Composição de Produto — Hamburgueria Mimoso</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4">
    <h3 class="mb-4">🧩 Composição de Produto</h3>

    <form method="get" class="row g-3 mb-4">
        <div class="col-md-5">
            <label class="form-label">Produto</label>
            <select name="p" class="form-select" onchange="this.form.submit()">
                <option value="">-- selecione --</option>
                <?php foreach($produtos as $p): ?>
                    <option value="<?=$p['id']?>" <?=$produtoId==$p['id']?'selected':''?>><?=$p['nome']?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Preço de venda</label>
            <input type="text" name="pv" class="form-control" value="<?=htmlspecialchars($precoVenda)?>" placeholder="Ex: 22.00" onchange="this.form.submit()">
        </div>
    </form>

    <?php if($produtoId > 0): ?>
    <div class="card mb-4">
        <div class="card-header bg-dark text-white">Adicionar Insumo</div>
        <div class="card-body">
            <form method="post" class="row g-3">
                <input type="hidden" name="acao" value="add">
                <div class="col-md-6">
                    <label class="form-label">Insumo</label>
                    <select name="insumo_id" class="form-select" required>
                        <option value="">-- selecione --</option>
                        <?php foreach($insumos as $i): ?>
                            <option value="<?=$i['id']?>"><?=$i['nome']?> (<?=$i['unidade']?> - R$<?=number_format($i['preco_unit'],2,',','.')?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Quantidade</label>
                    <input type="text" name="quantidade" class="form-control" required placeholder="Ex: 2">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-success w-100">Adicionar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-secondary text-white">📋 Insumos do Produto</div>
        <div class="card-body">
            <?php if(empty($lista)): ?>
                <p class="text-muted">Nenhum insumo cadastrado ainda.</p>
            <?php else: ?>
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Insumo</th>
                            <th>Unidade</th>
                            <th>Qtd</th>
                            <th>Preço Unit</th>
                            <th>Subtotal</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($lista as $l): ?>
                        <tr>
                            <form method="post">
                                <input type="hidden" name="id" value="<?=$l['link_id']?>">
                                <td><?=$l['nome']?></td>
                                <td><?=$l['unidade']?></td>
                                <td><input type="text" name="quantidade" value="<?=$l['quantidade']?>" class="form-control form-control-sm" style="width:80px;display:inline;"></td>
                                <td>R$<?=number_format($l['preco_unit'],2,',','.')?></td>
                                <td>R$<?=number_format($l['subtotal'],2,',','.')?></td>
                                <td class="text-end">
                                    <select name="insumo_id" class="form-select form-select-sm d-inline" style="width:auto;">
                                        <option value="<?=$l['insumo_id']??''?>">Atual</option>
                                        <?php foreach($insumos as $i): ?>
                                            <option value="<?=$i['id']?>"><?=$i['nome']?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button name="acao" value="edit" class="btn btn-warning btn-sm">✏️</button>
                                    <button name="acao" value="del" class="btn btn-danger btn-sm" onclick="return confirm('Excluir este insumo?')">🗑</button>
                                </td>
                            </form>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="mt-4">
        <h5>💰 Custo total: R$ <?=number_format($custoTotal,2,',','.')?></h5>
        <h5>📈 Lucro bruto: R$ <?=number_format($lucro,2,',','.')?></h5>
        <h5>🏁 Margem bruta: <?=$margem?>%</h5>
    </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
