<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../backend/conexao.php';

if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit;
}

$id = intval($_GET['id'] ?? 0);
if($id<=0){
    die("Produto inválido");
}

// Carrega produto
$produto = $conn->query("SELECT * FROM produtos WHERE id=$id LIMIT 1")->fetch_assoc();
if(!$produto){
    die("Produto não encontrado");
}

// Carrega categorias
$categorias = $conn->query("SELECT * FROM categorias ORDER BY nome")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Editar Produto - <?=$produto['nome']?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <span class="navbar-brand">✏️ Editar Produto</span>
    <a href="produtos.php" class="btn btn-secondary btn-sm">⬅ Voltar</a>
  </div>
</nav>

<div class="container mt-4">
  <div class="card">
    <div class="card-header bg-dark text-white">Editar Produto</div>
    <div class="card-body">
      <form method="post" action="produto_editar_salvar.php" enctype="multipart/form-data" class="row g-3">
        <input type="hidden" name="id" value="<?=$produto['id']?>">

        <div class="col-md-6">
          <label class="form-label">Nome</label>
          <input type="text" name="nome" class="form-control" value="<?=htmlspecialchars($produto['nome'])?>" required>
        </div>

        <div class="col-md-6">
          <label class="form-label">Destaque / Badge</label>
          <select name="destaque" class="form-select">
            <option value="" <?=($produto['destaque']==''?'selected':'')?>>Nenhum</option>
            <option value="novo" <?=($produto['destaque']=='novo'?'selected':'')?>>🆕 Novo</option>
            <option value="promocao" <?=($produto['destaque']=='promocao'?'selected':'')?>>🔥 Promoção</option>
            <option value="mais_vendido" <?=($produto['destaque']=='mais_vendido'?'selected':'')?>>⭐ Mais Vendido</option>
          </select>
        </div>

        <div class="col-md-12">
          <label class="form-label">Descrição / Observações</label>
          <textarea name="descricao" class="form-control" rows="2"><?=htmlspecialchars($produto['descricao'])?></textarea>
        </div>

        <div id="precosLanche" class="row g-3">
          <div class="col-md-3">
            <label class="form-label">Preço Industrial</label>
            <input type="number" step="0.01" name="preco_industrial" class="form-control" value="<?=$produto['preco_industrial']?>">
          </div>
          <div class="col-md-3">
            <label class="form-label">Preço Frango</label>
            <input type="number" step="0.01" name="preco_frango" class="form-control" value="<?=$produto['preco_frango']?>">
          </div>
          <div class="col-md-3">
            <label class="form-label">Preço Artesanal</label>
            <input type="number" step="0.01" name="preco_artesanal" class="form-control" value="<?=$produto['preco_artesanal']?>">
          </div>
        </div>

        <div id="precoPadrao" class="row g-3" style="display:none;">
          <div class="col-md-3">
            <label class="form-label">Preço</label>
            <input type="number" step="0.01" name="preco_padrao" class="form-control" value="<?=$produto['preco_padrao']?>">
          </div>
        </div>

        <div class="col-md-4">
          <label class="form-label">Categoria</label>
          <select name="categoria_id" class="form-select" required onchange="atualizarCamposPreco()">
            <option value="">Selecione...</option>
            <?php foreach($categorias as $c): ?>
              <option value="<?=$c['id']?>" <?=($produto['categoria_id']==$c['id']?'selected':'')?> >
                <?=htmlspecialchars($c['nome'])?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-4">
          <label class="form-label">Imagem (enviar apenas se quiser trocar)</label>
          <input type="file" name="imagem" class="form-control">
          <?php if($produto['imagem']): ?>
            <small>Atual: <img src="../uploads/produtos/<?=htmlspecialchars($produto['imagem'])?>" width="60"></small>
          <?php endif; ?>
        </div>

        <div class="col-12">
          <button class="btn btn-success">💾 Salvar Alterações</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function atualizarCamposPreco(){
    const select = document.querySelector('select[name="categoria_id"]');
    const nomeCat = select.options[select.selectedIndex]?.text?.toLowerCase() || '';
    const blocoLanche = document.getElementById('precosLanche');
    const blocoPadrao = document.getElementById('precoPadrao');
    if(nomeCat.includes('lanche')){
        blocoLanche.style.display = 'flex';
        blocoPadrao.style.display = 'none';
    } else {
        blocoLanche.style.display = 'none';
        blocoPadrao.style.display = 'flex';
    }
}
window.addEventListener('DOMContentLoaded', atualizarCamposPreco);
</script>

</body>
</html>
