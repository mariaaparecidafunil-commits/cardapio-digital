<?php
error_reporting(E_ALL); ini_set('display_errors', 1);
session_start(); require_once __DIR__ . '/../backend/conexao.php';

if (!isset($_SESSION['admin_id'])) { header('Location: /admin/login.php'); exit; }

$id=intval($_GET['id']??0);
$stmt=$conn->prepare("SELECT * FROM categorias WHERE id=?");
$stmt->bind_param("i",$id);$stmt->execute();$res=$stmt->get_result();
$categoria=$res->fetch_assoc();$stmt->close();
if(!$categoria){header('Location: categorias.php');exit;}

$msg='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  if($_POST['confirm']==='sim'){
    $stmt=$conn->prepare("DELETE FROM categorias WHERE id=?");
    $stmt->bind_param("i",$id);
    if($stmt->execute()){header('Location: categorias.php?msg=deleted');exit;}
    else{$msg="❌ Erro: ".$conn->error;}
    $stmt->close();
  } else {header('Location: categorias.php');exit;}
}
?>
<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><title>Excluir Categoria</title>
<style>body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#e6f0fa;font-family:Segoe UI,Tahoma;}
.panel{background:#fff;padding:30px;border-radius:12px;box-shadow:0 6px 20px rgba(0,0,0,0.15);max-width:400px;width:100%;text-align:center}
h1{color:#d93025}.msg{margin-bottom:15px;padding:10px;border-radius:6px;font-weight:bold;background:#fdecea;color:#d93025;border:1px solid #f5c6cb}
button{padding:12px 20px;margin:10px;border:0;border-radius:6px;font-weight:bold;cursor:pointer;font-size:15px}
.yes{background:#d93025;color:#fff}.yes:hover{background:#b1271e}.no{background:#6c757d;color:#fff}.no:hover{background:#555}</style>
</head><body><div class="panel"><h1>🗑️ Excluir Categoria</h1>
<?php if($msg):?><div class="msg"><?php echo $msg;?></div><?php endif;?>
<p>Tem certeza que deseja excluir <strong><?php echo htmlspecialchars($categoria['nome']);?></strong>?</p>
<form method="post"><button type="submit" name="confirm" value="sim" class="yes">Sim, excluir</button>
<button type="submit" name="confirm" value="nao" class="no">Cancelar</button></form></div></body></html>
