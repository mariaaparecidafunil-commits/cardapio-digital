<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../backend/conexao.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// =======================
// Validar ID do produto
// =======================
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    die("<div style='padding:40px;color:#ff6b6b;'>⚠️ Produto inválido.</div>");
}

// =======================
// Buscar produto
// =======================
$stmt = $conn->prepare("SELECT * FROM produtos WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$produto = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$produto) {
    die("<div style='padding:40px;color:#ff6b6b;'>❌ Produto não encontrado.</div>");
}

// =======================
// SALVAR ALTERAÇÕES
// =======================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome = trim($_POST['nome']);
    $categoria_id = intval($_POST['categoria_id']);

    // --------- PREÇOS POR CATEGORIA ----------
    if ($categoria_id == 20) { // LANCHES
        $preco_industrial = floatval($_POST['preco_industrial']);
        $preco_frango = floatval($_POST['preco_frango']);
        $preco_artesanal = floatval($_POST['preco_artesanal']);
    } else { // BEBIDAS OU SUCOS
        $preco_unico = floatval($_POST['preco_unico']);
        $preco_industrial = $preco_unico;
        $preco_frango = 0;
        $preco_artesanal = 0;
    }

    // Atualizar produto
    $stmt = $conn->prepare("
        UPDATE produtos 
        SET nome=?, categoria_id=?, 
            preco_industrial=?, preco_frango=?, preco_artesanal=?
        WHERE id=?
    ");
    $stmt->bind_param("siddii", 
        $nome, $categoria_id, 
        $preco_industrial, $preco_frango, $preco_artesanal, 
        $id
    );
    $stmt->execute();
    $stmt->close();

    // ---------- IMAGEM NOVA ----------
    if (!empty($_FILES['imagem']['name'])) {

        $nome_img = "produto_" . $id . "_" . time() . ".jpg";
        $destino = __DIR__ . "/../uploads/produtos/" . $nome_img;

        move_uploaded_file($_FILES['imagem']['tmp_name'], $destino);

        $conn->query("UPDATE produtos SET imagem='$nome_img' WHERE id=$id");
    }

    // ---------- INSUMOS ----------
    $conn->query("DELETE FROM produto_insumo WHERE produto_id = $id");

    if (!empty($_POST['insumos'])) {
        foreach ($_POST['insumos'] as $ins) {

            $iid = intval($ins['id']);
            $qtd = floatval($ins['quantidade']);

            if ($iid > 0 && $qtd > 0) {
                $conn->query("
                    INSERT INTO produto_insumo (produto_id, insumo_id, quantidade)
                    VALUES ($id, $iid, $qtd)
                ");
            }
        }
    }

    // ---------- OPCIONAIS ----------
    $conn->query("DELETE FROM produto_opcional WHERE produto_id = $id");

    if (!empty($_POST['opcionais'])) {
        foreach ($_POST['opcionais'] as $op) {
            $op = intval($op);
            $conn->query("
                INSERT INTO produto_opcional (produto_id, opcional_id)
                VALUES ($id, $op)
            ");
        }
    }

    header("Location: produtos.php");
    exit;
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Editar Produto</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body{background:#111827;color:#f9fafb;font-family:'Segoe UI',sans-serif;}
.card-dark{background:#1f2937;border-radius:12px;padding:24px;}
.btn-primary{background:#ff6b00;border:none;}
.btn-primary:hover{background:#e65c00;}
</style>
</head>
<body class="p-4">

<div class="container">
    <div class="card-dark">

        <h3 class="mb-4">✏️ Editar Produto</h3>

        <form method="post" enctype="multipart/form-data" class="row g-3">

            <!-- Nome -->
            <div class="col-md-6">
                <label class="form-label">Nome</label>
                <input type="text" name="nome" value="<?= htmlspecialchars($produto['nome']) ?>" class="form-control" required>
            </div>

            <!-- Categoria -->
            <div class="col-md-6">
                <label class="form-label">Categoria</label>
                <select name="categoria_id" id="categoriaSelect" class="form-select" required>
                    <?php
                    $cats = $conn->query("SELECT id, nome FROM categorias ORDER BY nome");
                    while ($c = $cats->fetch_assoc()):
                    ?>
                    <option value="<?= $c['id'] ?>" <?= $c['id'] == $produto['categoria_id'] ? 'selected' : '' ?>>
                        <?= $c['nome'] ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <?php
            $isLanche = ($produto['categoria_id'] == 20);
            ?>

            <!-- PREÇO ÚNICO -->
            <div class="col-md-6 preco-unico" style="display: <?= $isLanche ? 'none' : 'block' ?>;">
                <label class="form-label">Preço (R$)</label>
                <input type="number" step="0.01" name="preco_unico"
                    value="<?= $isLanche ? '' : number_format($produto['preco_industrial'], 2, '.', '') ?>"
                    class="form-control">
            </div>

            <!-- Preço Industrial -->
            <div class="col-md-4 preco-lanche" style="display: <?= $isLanche ? 'block' : 'none' ?>;">
                <label class="form-label">Preço Industrial</label>
                <input type="number" step="0.01" name="preco_industrial"
                    value="<?= number_format($produto['preco_industrial'], 2, '.', '') ?>" class="form-control">
            </div>

            <!-- Preço Frango -->
            <div class="col-md-4 preco-lanche" style="display: <?= $isLanche ? 'block' : 'none' ?>;">
                <label class="form-label">Preço Frango</label>
                <input type="number" step="0.01" name="preco_frango"
                    value="<?= number_format($produto['preco_frango'], 2, '.', '') ?>" class="form-control">
            </div>

            <!-- Preço Artesanal -->
            <div class="col-md-4 preco-lanche" style="display: <?= $isLanche ? 'block' : 'none' ?>;">
                <label class="form-label">Preço Artesanal</label>
                <input type="number" step="0.01" name="preco_artesanal"
                    value="<?= number_format($produto['preco_artesanal'], 2, '.', '') ?>" class="form-control">
            </div>

<script>
// Alternar exibição de preços ao mudar categoria
document.getElementById('categoriaSelect').addEventListener('change', function() {
    let cat = parseInt(this.value);

    if (cat === 20) {
        document.querySelectorAll('.preco-lanche').forEach(e => e.style.display = 'block');
        document.querySelector('.preco-unico').style.display = 'none';
    } else {
        document.querySelectorAll('.preco-lanche').forEach(e => e.style.display = 'none');
        document.querySelector('.preco-unico').style.display = 'block';
    }
});
</script>

            <hr class="text-secondary my-3">

            <!-- INSUMOS -->
            <h5>🧂 Insumos</h5>
            <div class="row g-2">
                <?php
                $map = [];
                $q = $conn->query("SELECT insumo_id, quantidade FROM produto_insumo WHERE produto_id=$id");
                while ($u = $q->fetch_assoc()) {
                    $map[$u['insumo_id']] = $u['quantidade'];
                }

                $insumos = $conn->query("SELECT * FROM insumos ORDER BY nome");
                while ($i = $insumos->fetch_assoc()):
                ?>
                <div class="col-md-6">
                    <div class="input-group input-group-sm mb-1">
                        <span class="input-group-text bg-secondary text-light" style="min-width:180px">
                            <?= $i['nome'] ?> (<?= $i['unidade'] ?>)
                        </span>
                        <input type="number" step="0.01" name="insumos[<?= $i['id'] ?>][quantidade]"
                            value="<?= $map[$i['id']] ?? '' ?>" class="form-control">
                        <input type="hidden" name="insumos[<?= $i['id'] ?>][id]" value="<?= $i['id'] ?>">
                    </div>
                </div>
                <?php endwhile; ?>
            </div>

            <hr class="text-secondary my-3">

            <!-- OPCIONAIS -->
            <h5>⚙️ Opcionais</h5>
            <div class="row g-2">
                <?php
                $selOps = [];
                $opsUsados = $conn->query("SELECT opcional_id FROM produto_opcional WHERE produto_id=$id");
                while ($s = $opsUsados->fetch_assoc()) $selOps[] = $s['opcional_id'];

                $ops = $conn->query("SELECT * FROM opcionais ORDER BY nome");
                while ($op = $ops->fetch_assoc()):
                ?>
                <div class="col-md-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" 
                               name="opcionais[]" value="<?= $op['id'] ?>"
                               <?= in_array($op['id'], $selOps) ? 'checked' : '' ?>>
                        <label class="form-check-label"><?= $op['nome'] ?> — R$ <?= $op['preco'] ?></label>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>

            <hr class="text-secondary my-3">

            <!-- IMAGEM -->
            <div class="col-md-12">
                <label class="form-label">Imagem</label>
                <input type="file" name="imagem" class="form-control mb-2">
                <?php if (!empty($produto['imagem'])): ?>
                    <img src="../uploads/produtos/<?= $produto['imagem'] ?>" width="140" class="img-thumbnail">
                <?php endif; ?>
            </div>

            <div class="text-end">
                <a href="produtos.php" class="btn btn-secondary">Voltar</a>
                <button class="btn btn-primary">Salvar Alterações</button>
            </div>

        </form>

    </div>
</div>

</body>
</html>
