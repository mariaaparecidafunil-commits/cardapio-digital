<?php
// admin/categorias.php
session_start();
ini_set('display_errors', 1); // DEBUG - remova em produção
error_reporting(E_ALL);

require_once __DIR__ . '/../backend/conexao.php';

// 🔒 Verifica login
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit;
}

$editando = false;
$categoriaEdit = null;

// ➕ Cadastrar categoria
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $nome  = trim($_POST['nome'] ?? '');
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    $ordem = (int)($_POST['ordem'] ?? 0);

    // 📸 Upload da imagem (opcional)
    $imagem = '';
    if (!empty($_FILES['imagem']['name'])) {
        $dir = __DIR__ . '/../uploads/categorias/';
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        $nomeArquivo = time() . "_" . preg_replace('/[^a-zA-Z0-9_\.-]/','_', basename($_FILES['imagem']['name']));
        $destino = $dir . $nomeArquivo;
        if (move_uploaded_file($_FILES['imagem']['tmp_name'], $destino)) {
            $imagem = $nomeArquivo;
        }
    }

    $stmt = $conn->prepare("INSERT INTO categorias (nome, imagem, ativo, ordem) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssii", $nome, $imagem, $ativo, $ordem);
    $stmt->execute();
    $stmt->close();

    header("Location: categorias.php");
    exit;
}

// ✏️ Buscar categoria para edição
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $res = $conn->prepare("SELECT * FROM categorias WHERE id=?");
    $res->bind_param("i", $id);
    $res->execute();
    $categoriaEdit = $res->get_result()->fetch_assoc();
    $res->close();
    $editando = (bool)$categoriaEdit;
}

// 🔄 Atualizar categoria
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $id    = (int)($_POST['id'] ?? 0);
    $nome  = trim($_POST['nome'] ?? '');
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    $ordem = (int)($_POST['ordem'] ?? 0);
    $imagem = $_POST['imagem_atual'] ?? '';

    // 📸 Nova imagem (opcional)
    if (!empty($_FILES['imagem']['name'])) {
        $dir = __DIR__ . '/../uploads/categorias/';
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        $nomeArquivo = time() . "_" . preg_replace('/[^a-zA-Z0-9_\.-]/','_', basename($_FILES['imagem']['name']));
        $destino = $dir . $nomeArquivo;
        if (move_uploaded_file($_FILES['imagem']['tmp_name'], $destino)) {
            $imagem = $nomeArquivo;
        }
    }

    $stmt = $conn->prepare("UPDATE categorias SET nome=?, imagem=?, ativo=?, ordem=? WHERE id=?");
    $stmt->bind_param("ssiii", $nome, $imagem, $ativo, $ordem, $id);
    $stmt->execute();
    $stmt->close();

    header("Location: categorias.php");
    exit;
}

// ❌ Excluir categoria
if (isset($_GET['del'])) {
    $id = (int)$_GET['del'];
    $stmt = $conn->prepare("DELETE FROM categorias WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: categorias.php");
    exit;
}

// 📋 Buscar categorias (com ordem)
$categorias = [];
$sqlLista = "SELECT id, nome, imagem, ativo, ordem FROM categorias ORDER BY ordem ASC, nome ASC";
if ($res = $conn->query($sqlLista)) {
    while($row = $res->fetch_assoc()) $categorias[] = $row;
    $res->close();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Gerenciar Categorias - Painel Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body{font-family:Arial,sans-serif;background:#f9fafb;margin:0}
    header{background:#1f2937;color:#fff;padding:16px;text-align:center;position:relative}
    header h1{margin:0;font-size:22px}
    header a{position:absolute;left:16px;top:16px;color:#fff;text-decoration:none;font-weight:bold}
    .wrap{max-width:900px;margin:20px auto;padding:0 16px}

    .tabs{display:flex;gap:10px;margin-bottom:20px}
    .tab-btn{flex:1;padding:12px;border:none;border-radius:6px 6px 0 0;background:#e5e7eb;cursor:pointer;font-weight:bold}
    .tab-btn.active{background:#2563eb;color:#fff}
    .tab-content{display:none;background:#fff;padding:16px;border-radius:0 6px 6px 6px;box-shadow:0 2px 6px rgba(0,0,0,.1)}
    .tab-content.active{display:block}

    label{display:block;margin-top:10px;font-size:14px}
    input,select{width:100%;padding:8px;margin-top:4px;border:1px solid #ccc;border-radius:6px}
    button{margin-top:12px;padding:10px 14px;border:0;border-radius:6px;background:#2563eb;color:#fff;cursor:pointer}
    button:hover{background:#1e40af}

    table{width:100%;border-collapse:collapse;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 6px rgba(0,0,0,.1)}
    th,td{padding:10px;border-bottom:1px solid #ddd;text-align:left;font-size:14px;vertical-align:top}
    th{background:#374151;color:#fff}
    tr:hover{background:#f3f4f6}
    img{max-width:50px;border-radius:6px}
    .del{color:#dc2626;text-decoration:none;font-weight:bold}
    footer{text-align:center;margin:20px 0;font-size:13px;color:#555}
  </style>
</head>
<body>
<header>
  <a href="index.php">⬅ Voltar</a>
  <h1>📂 Gerenciar Categorias</h1>
</header>

<div class="wrap">
  <!-- Abas -->
  <div class="tabs">
    <button class="tab-btn active" onclick="openTab('cadastro')"><?php echo $editando ? "✏️ Editar Categoria" : "➕ Nova Categoria"; ?></button>
    <button class="tab-btn" onclick="openTab('lista')">📋 Lista de Categorias</button>
  </div>

  <!-- Aba: Cadastro/Editar -->
  <div id="cadastro" class="tab-content active">
    <form method="post" enctype="multipart/form-data">
      <h2><?php echo $editando ? "Editar Categoria" : "Cadastrar Categoria"; ?></h2>
      <input type="hidden" name="id" value="<?php echo htmlspecialchars($categoriaEdit['id'] ?? ''); ?>">
      <input type="hidden" name="imagem_atual" value="<?php echo htmlspecialchars($categoriaEdit['imagem'] ?? ''); ?>">

      <label>Nome da categoria</label>
      <input type="text" name="nome" value="<?php echo htmlspecialchars($categoriaEdit['nome'] ?? ''); ?>" required>

      <label>Ordem de exibição</label>
      <input type="number" name="ordem" value="<?php echo htmlspecialchars($categoriaEdit['ordem'] ?? 0); ?>" min="0">

      <label>Imagem (opcional)</label>
      <?php if($categoriaEdit && !empty($categoriaEdit['imagem'])): ?>
        <p>Imagem atual: <img src="../uploads/categorias/<?php echo htmlspecialchars($categoriaEdit['imagem']); ?>" alt=""></p>
      <?php endif; ?>
      <input type="file" name="imagem" accept="image/*">

      <label>
        <input type="checkbox" name="ativo" value="1" <?php echo ($categoriaEdit && !$categoriaEdit['ativo']) ? "" : "checked"; ?>>
        Categoria ativa
      </label>

      <button type="submit" name="<?php echo $editando ? "update" : "add"; ?>">
        <?php echo $editando ? "Salvar Alterações" : "Cadastrar"; ?>
      </button>
    </form>
  </div>

  <!-- Aba: Lista -->
  <div id="lista" class="tab-content">
    <h2>Lista de Categorias</h2>
    <table>
      <tr>
        <th>ID</th>
        <th>Imagem</th>
        <th>Nome</th>
        <th>Ordem</th>
        <th>Status</th>
        <th>Ações</th>
      </tr>
      <?php foreach($categorias as $c): ?>
      <tr>
        <td><?php echo (int)$c['id']; ?></td>
        <td><?php if(!empty($c['imagem'])): ?><img src="../uploads/categorias/<?php echo htmlspecialchars($c['imagem']); ?>" alt=""><?php endif; ?></td>
        <td><?php echo htmlspecialchars($c['nome']); ?></td>
        <td><?php echo (int)$c['ordem']; ?></td>
        <td><?php echo $c['ativo'] ? "<span style='color:green;font-weight:bold'>Ativa</span>" : "<span style='color:red;font-weight:bold'>Inativa</span>"; ?></td>
        <td>
          <a href="categorias.php?edit=<?php echo (int)$c['id']; ?>">✏️ Editar</a> |
          <a href="categorias.php?del=<?php echo (int)$c['id']; ?>" class="del" onclick="return confirm('Excluir esta categoria?')">Excluir</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>
</div>

<footer>
  2025 ® Mimoso Lanches – Todos os direitos reservados<br>
  Webdesigner José Luiz - Amo Mimoso do Sul
</footer>

<script>
  function openTab(tabId) {
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
    document.getElementById(tabId).classList.add('active');
    if (event && event.target) event.target.classList.add('active');
  }
</script>
</body>
</html>
