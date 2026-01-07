<?php
error_reporting(E_ALL); ini_set('display_errors', 1);
session_start(); require_once __DIR__ . '/../backend/conexao.php';

if (!isset($_SESSION['admin_id'])) { header('Location: /admin/login.php'); exit; }

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    if ($nome !== '') {
        $stmt = $conn->prepare("INSERT INTO categorias (nome) VALUES (?)");
        $stmt->bind_param("s", $nome);
        if ($stmt->execute()) $msg = "✅ Categoria adicionada!";
        else $msg = "❌ Erro: " . $conn->error;
        $stmt->close();
    } else $msg = "⚠️ Informe um nome.";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="UTF-8"><title>Nova Categoria</title>
<style>
body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#e6f0fa;font-family:Segoe UI,Tahoma;}
.panel{background:#fff;padding:30px;border-radius:12px;box-shadow:0 6px 20px rgba(0,0,0,0.15);max-width:400px;width:100%}
h1{text-align:center;color:#1a73e8}
.msg{margin-bottom:15px;padding:10px;border-radius:6px;font-weight:bold;text-align:center}
.success{background:#e6f4ea;color:#188038}
.error{background:#fdecea;color:#d93025}
label{display:block;margin-top:12px;font-weight:500}
input{width:100%;padding:12px;margin-top:6px;border:1px solid #ddd;border-radius:6px}
button{width:100%;padding:12px;margin-top:20px;border:0;border-radius:6px;background:#1a73e8;color:#fff;font-weight:bold;cursor:pointer}
.back{display:inline-block;margin-top:15px;padding:8px 14px;border-radius:6px;background:#6c757d;color:#fff;text-decoration:none}
</style></head>
<body>
<div class="panel">
<h1>➕ Nova Categoria</h1>
<?php if ($msg): ?><div class="msg <?php echo strpos($msg,"✅")!==false?"success":"error"; ?>"><?php echo $msg; ?></div><?php endif; ?>
<form method="post"><label>Nome</label><input type="text" name="nome" required><button type="submit">Salvar</button></form>
<a href="categorias.php" class="back">⬅ Voltar</a>
</div></body></html>
