<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../app/config.php';
require_once '../app/guard.php';

// Apenas admin pode acessar
if ($_SESSION['papel'] !== 'superadmin') {
  die("<div style='margin:40px;font-family:Arial'>Acesso restrito ao administrador.</div>");
}

// ====== EXCLUSÃO ======
if (isset($_GET['excluir'])) {
  $id = intval($_GET['excluir']);
  if ($id != $_SESSION['usuario_id']) {
    $del = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
    $del->execute([$id]);
  }
  header("Location: painel_usuarios.php");
  exit;
}

// ====== ATUALIZAÇÃO ======
if (isset($_POST['editar'])) {
  $id = intval($_POST['id']);
  $nome = trim($_POST['nome']);
  $telefone = trim($_POST['telefone']);
  $cidade = trim($_POST['cidade']);
  $bairro = trim($_POST['bairro']);
  $tipo_conta = trim($_POST['tipo_conta']);
  $papel = trim($_POST['papel']);

  $upd = $pdo->prepare("UPDATE usuarios SET nome=?, telefone=?, cidade=?, bairro=?, tipo_conta=?, papel=? WHERE id=?");
  $upd->execute([$nome, $telefone, $cidade, $bairro, $tipo_conta, $papel, $id]);
  header("Location: painel_usuarios.php");
  exit;
}

// ====== MENSAGEM DIRETA ======
if (isset($_POST['enviar_mensagem'])) {
  $remetente = $_SESSION['usuario_id'];
  $destinatario = intval($_POST['destinatario_id']);
  $mensagem = trim($_POST['mensagem']);
  if ($mensagem != '') {
    $ins = $pdo->prepare("INSERT INTO mensagens (remetente_id, destinatario_id, mensagem) VALUES (?,?,?)");
    $ins->execute([$remetente, $destinatario, $mensagem]);
    $msg = "Mensagem enviada com sucesso!";
  }
}

// ====== BUSCA E LISTAGEM ======
$busca = '';
if (!empty($_GET['q'])) {
  $busca = trim($_GET['q']);
  $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE nome LIKE ? OR email LIKE ? ORDER BY id DESC");
  $stmt->execute(["%$busca%", "%$busca%"]);
} else {
  $stmt = $pdo->query("SELECT * FROM usuarios ORDER BY id DESC");
}
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Gerenciar Usuários - Mimoso Local</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
body{background:#f8f9fa;font-family:'Segoe UI',sans-serif;color:#333;}
.container{max-width:1300px;}
h4{font-weight:700;color:#222;}
.table thead{background:#FFE600;}
.foto-perfil{width:40px;height:40px;border-radius:50%;object-fit:cover;}
.btn-ml{background:#2968C8;color:#fff;}
.btn-ml:hover{background:#3483FA;color:#fff;}
</style>
</head>
<body>
<div class="container mt-4 mb-5">
  <h4 class="mb-3">👥 Gerenciar Usuários</h4>

  <?php if (!empty($msg)): ?>
    <div class="alert alert-success"><?=$msg?></div>
  <?php endif; ?>

  <!-- Busca -->
  <form class="mb-3" method="get">
    <div class="input-group">
      <input type="text" name="q" class="form-control" placeholder="Buscar por nome ou e-mail" value="<?=htmlspecialchars($busca)?>">
      <button class="btn btn-ml"><i class="bi bi-search"></i></button>
    </div>
  </form>

  <!-- Tabela -->
  <div class="table-responsive">
    <table class="table table-striped align-middle">
      <thead>
        <tr>
          <th>ID</th>
          <th>Foto</th>
          <th>Nome</th>
          <th>E-mail</th>
          <th>Telefone</th>
          <th>Cidade / Bairro</th>
          <th>Tipo</th>
          <th>Papel</th>
          <th>Criado em</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($usuarios as $u): ?>
        <tr>
          <td><?=$u['id']?></td>
          <td>
            <?php if(!empty($u['foto_perfil'])): ?>
              <img src="../uploads/usuarios/<?=$u['foto_perfil']?>" class="foto-perfil">
            <?php else: ?>
              <img src="https://via.placeholder.com/40x40.png?text=U" class="foto-perfil">
            <?php endif; ?>
          </td>
          <td><?=htmlspecialchars($u['nome'])?></td>
          <td><?=htmlspecialchars($u['email'])?></td>
          <td><?=htmlspecialchars($u['telefone'])?></td>
          <td><?=htmlspecialchars($u['cidade'].' / '.$u['bairro'])?></td>
          <td><?=htmlspecialchars(ucfirst($u['tipo_conta']))?></td>
          <td><?=htmlspecialchars($u['papel'])?></td>
          <td><?=date('d/m/Y H:i', strtotime($u['criado_em']))?></td>
          <td>
            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editarModal<?=$u['id']?>"><i class="bi bi-pencil"></i></button>
            <a href="?excluir=<?=$u['id']?>" class="btn btn-danger btn-sm" onclick="return confirm('Excluir este usuário?')"><i class="bi bi-trash"></i></a>
            <button class="btn btn-ml btn-sm" data-bs-toggle="modal" data-bs-target="#msgModal<?=$u['id']?>"><i class="bi bi-chat-dots"></i></button>
          </td>
        </tr>

        <!-- Modal Editar -->
        <div class="modal fade" id="editarModal<?=$u['id']?>" tabindex="-1">
          <div class="modal-dialog">
            <form class="modal-content" method="post">
              <div class="modal-header bg-warning">
                <h5 class="modal-title">Editar Usuário #<?=$u['id']?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                <input type="hidden" name="id" value="<?=$u['id']?>">
                <div class="mb-2">
                  <label>Nome:</label>
                  <input type="text" name="nome" class="form-control" value="<?=htmlspecialchars($u['nome'])?>">
                </div>
                <div class="mb-2">
                  <label>Telefone:</label>
                  <input type="text" name="telefone" class="form-control" value="<?=htmlspecialchars($u['telefone'])?>">
                </div>
                <div class="mb-2">
                  <label>Cidade:</label>
                  <input type="text" name="cidade" class="form-control" value="<?=htmlspecialchars($u['cidade'])?>">
                </div>
                <div class="mb-2">
                  <label>Bairro:</label>
                  <input type="text" name="bairro" class="form-control" value="<?=htmlspecialchars($u['bairro'])?>">
                </div>
                <div class="mb-2">
                  <label>Tipo de conta:</label>
                  <select name="tipo_conta" class="form-select">
                    <option value="fisica" <?=$u['tipo_conta']=='fisica'?'selected':''?>>Física</option>
                    <option value="comercial" <?=$u['tipo_conta']=='comercial'?'selected':''?>>Comercial</option>
                  </select>
                </div>
                <div class="mb-2">
                  <label>Papel:</label>
                  <select name="papel" class="form-select">
                    <option value="usuario" <?=$u['papel']=='usuario'?'selected':''?>>Usuário</option>
                    <option value="superadmin" <?=$u['papel']=='superadmin'?'selected':''?>>Superadmin</option>
                  </select>
                </div>
              </div>
              <div class="modal-footer">
                <button type="submit" name="editar" class="btn btn-ml">Salvar</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
              </div>
            </form>
          </div>
        </div>

        <!-- Modal Mensagem -->
        <div class="modal fade" id="msgModal<?=$u['id']?>" tabindex="-1">
          <div class="modal-dialog">
            <form class="modal-content" method="post">
              <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Enviar mensagem para <?=htmlspecialchars($u['nome'])?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                <input type="hidden" name="destinatario_id" value="<?=$u['id']?>">
                <textarea name="mensagem" class="form-control" rows="4" placeholder="Digite sua mensagem"></textarea>
              </div>
              <div class="modal-footer">
                <button type="submit" name="enviar_mensagem" class="btn btn-ml">Enviar</button>
              </div>
            </form>
          </div>
        </div>

        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
