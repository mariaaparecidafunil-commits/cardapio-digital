<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/helpers.php';
exigeLogin(); // Garante que apenas usuários logados acessem
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Painel do Usuário - Comércio Mimoso</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>

<body style="background:#F8F9FA;">
<nav class="navbar navbar-expand-lg navbar-light shadow-sm" style="background:#FF5722;">
  <div class="container">
    <a class="navbar-brand text-white fw-bold" href="<?= BASE_URL ?>public/index.php">🛍️ Comércio Mimoso</a>
    <div class="d-flex align-items-center">
      <span class="text-white me-3 fw-semibold">
        <i class="bi bi-person-circle"></i> <?= e($_SESSION['usuario_nome']) ?>
      </span>
      <a href="logout.php" class="btn btn-light btn-sm fw-bold">Sair</a>
    </div>
  </div>
</nav>

<div class="container my-5">
  <div class="card shadow border-0 rounded-4">
    <div class="card-body p-4">
      <h3 class="mb-3 text-warning fw-bold">Bem-vindo, <?= e($_SESSION['usuario_nome']) ?>!</h3>
      <p class="text-muted">
        Este é o seu <strong>Painel de Controle</strong> no Comércio Mimoso.  
        A partir daqui você poderá gerenciar seus anúncios, mensagens, perguntas e perfil pessoal.
      </p>

      <hr>

      <div class="row g-3">
        <div class="col-md-4">
          <div class="card shadow-sm border-0 h-100">
            <div class="card-body text-center">
              <i class="bi bi-bag-check display-5 text-warning"></i>
              <h5 class="mt-3 fw-bold">Meus Anúncios</h5>
              <p class="text-muted small">Crie, edite e gerencie seus produtos.</p>
              <a href="#" class="btn btn-outline-warning btn-sm w-100">Acessar</a>
            </div>
          </div>
        </div>

        <div class="col-md-4">
          <div class="card shadow-sm border-0 h-100">
            <div class="card-body text-center">
              <i class="bi bi-chat-dots display-5 text-warning"></i>
              <h5 class="mt-3 fw-bold">Mensagens</h5>
              <p class="text-muted small">Converse com compradores e vendedores.</p>
              <a href="#" class="btn btn-outline-warning btn-sm w-100">Ver mensagens</a>
            </div>
          </div>
        </div>

        <div class="col-md-4">
          <div class="card shadow-sm border-0 h-100">
            <div class="card-body text-center">
              <i class="bi bi-person-lines-fill display-5 text-warning"></i>
              <h5 class="mt-3 fw-bold">Meu Perfil</h5>
              <p class="text-muted small">Atualize suas informações e imagem.</p>
              <a href="#" class="btn btn-outline-warning btn-sm w-100">Editar perfil</a>
            </div>
          </div>
        </div>
      </div>

      <hr class="my-4">
      <p class="text-center text-muted small">
        Versão 2.0 — Comércio Mimoso © <?= date('Y') ?>.  
        Desenvolvido com 💛 em Mimoso do Sul.
      </p>
    </div>
  </div>
</div>
</body>
</html>
