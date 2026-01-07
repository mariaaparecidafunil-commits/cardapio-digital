<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/../backend/conexao.php';

// Se não estiver logado, volta para login
if (!isset($_SESSION['admin_id'])) {
    header('Location: /admin/login.php');
    exit;
}

// Buscar categorias para o select
$sql = "SELECT id, nome FROM categorias ORDER BY nome ASC";
$res = $conn->query($sql);
$categorias = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

// Processar envio do formulário
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $preco = floatval($_POST['preco'] ?? 0);
    $categoria_id = intval($_POST['categoria_id'] ?? 0);

    if ($nome !== '' && $preco > 0) {
        $stmt = $conn->prepare("INSERT INTO produtos (nome, preco, categoria_id) VALUES (?, ?, ?)");
        $stmt->bind_param("sdi", $nome, $preco, $categoria_id);
        if ($stmt->execute()) {
            $msg = "✅ Produto cadastrado com sucesso!";
        } else {
            $msg = "❌ Erro ao cadastrar produto: " . $conn->error;
        }
        $stmt->close();
    } else {
        $msg = "⚠️ Preencha todos os campos corretamente.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Novo Produto - Mimoso Lanches</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <style>
    body {
      margin: 0;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #e6f0fa; /* fundo azul clarinho */
      font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
    }
    .panel {
      background: #fff;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 6px 20px rgba(0,0,0,0.15);
      width: 100%;
      max-width: 500px;
    }
    h1 {
      margin-top: 0;
      text-align: center;
      color: #1a73e8;
    }
    .msg {
      margin-bottom: 15px;
      padding: 10px;
      border-radius: 6px;
      font-weight: bold;
      text-align: center;
    }
    .msg.success { background: #e6f4ea; color: #188038; border: 1px solid #c3e6cb; }
    .msg.error   { background: #fdecea; color: #d93025; border: 1px solid #f5c6cb; }
    label {
      display: block;
      margin-top: 12px;
      font-weight: 500;
      color: #333;
    }
    input, select {
      width: 100%;
      padding: 12px;
      margin-top: 6px;
      border: 1px solid #ddd;
      border-radius: 6px;
      font-size: 14px;
    }
    button {
      width: 100%;
      padding: 12px;
      margin-top: 20px;
      border: 0;
      border-radius: 6px;
      background: #1a73e8;
      color: #fff;
      font-weight: bold;
      font-size: 15px;
      cursor: pointer;
      transition: background 0.3s;
    }
    button:hover { background: #1669c1; }
    .back {
      display: inline-block;
      margin-top: 15px;
      padding: 8px 14px;
      border-radius: 6px;
      text-decoration: none;
      background: #6c757d;
      color: #fff;
      font-size: 14px;
    }
  </style>
</head>
<body>
  <div class="panel">
    <h1>➕ Novo Produto</h1>

    <?php if ($msg): ?>
      <div class="msg <?php echo strpos($msg, '✅') !== false ? 'success' : 'error'; ?>">
        <?php echo htmlspecialchars($msg); ?>
      </div>
    <?php endif; ?>

    <form method="post" autocomplete="off">
      <label for="nome">Nome do Produto</label>
      <input type="text" id="nome" name="nome" required>

      <label for="preco">Preço (R$)</label>
      <input type="number" id="preco" name="preco" step="0.01" min="0" required>

      <label for="categoria">Categoria</label>
      <select id="categoria" name="categoria_id">
        <option value="0">Sem categoria</option>
        <?php foreach ($categorias as $c): ?>
          <option value="<?php echo $c['id']; ?>">
            <?php echo htmlspecialchars($c['nome']); ?>
          </option>
        <?php endforeach; ?>
      </select>

      <button type="submit">Salvar Produto</button>
    </form>

    <a href="produtos.php" class="back">⬅ Voltar</a>
  </div>
</body>
</html>
