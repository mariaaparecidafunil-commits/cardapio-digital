<?php
// --- Processa POST do formulário de login e redireciona para o painel ---

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/../backend/conexao.php';

// ================== CONFIGURAÇÃO ==================
$DEBUG_MODE = false; // 👈 Troque para true se quiser ver detalhes do login
// ==================================================

// só aceita POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/login.php');
    exit;
}

$login = trim($_POST['login'] ?? '');
$senha = $_POST['senha'] ?? '';

if ($login === '' || $senha === '') {
    header('Location: /admin/login.php?err=1');
    exit;
}

// procurar usuário pelo campo "login"
$sql = "SELECT id, nome, login, senha_hash, perfil FROM usuarios WHERE login = ? LIMIT 1";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    error_log('[login_check] prepare failed: ' . $conn->error);
    header('Location: /admin/login.php?err=1');
    exit;
}
$stmt->bind_param('s', $login);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();
$stmt->close();

if (!$user) {
    if ($DEBUG_MODE) {
        echo "<pre>DEBUG LOGIN:\n";
        echo "Usuário não encontrado no banco.\n";
        echo "Login digitado: " . htmlspecialchars($login) . "\n";
        echo "</pre>";
        exit;
    }
    header('Location: /admin/login.php?err=1');
    exit;
}

// verificar senha usando password_verify
if (!password_verify($senha, $user['senha_hash'])) {
    if ($DEBUG_MODE) {
        echo "<pre>DEBUG LOGIN:\n";
        echo "Login digitado: " . htmlspecialchars($login) . "\n";
        echo "Senha digitada: " . htmlspecialchars($senha) . "\n";
        echo "Hash no banco: " . htmlspecialchars($user['senha_hash']) . "\n";
        echo "Resultado password_verify: FALHOU\n";
        echo "</pre>";
        exit;
    }
    header('Location: /admin/login.php?err=1');
    exit;
}

// sucesso — montar sessão
session_regenerate_id(true);
$_SESSION['admin_id'] = $user['id'];
$_SESSION['admin_name'] = $user['nome'] ?? $user['login'];
$_SESSION['perfil'] = $user['perfil'] ?? 'admin';
$_SESSION['is_admin'] = true;

// Se debug estiver ligado, mostra informações
if ($DEBUG_MODE) {
    echo "<pre>DEBUG LOGIN:\n";
    echo "Login digitado: " . htmlspecialchars($login) . "\n";
    echo "Senha digitada: " . htmlspecialchars($senha) . "\n";
    echo "Hash no banco: " . htmlspecialchars($user['senha_hash']) . "\n";
    echo "Resultado password_verify: SUCESSO\n";
    echo "Redirecionando para /admin/index.php...\n";
    echo "</pre>";
    exit;
}

// Produção → redireciona direto
header('Location: /admin/index.php');
exit;
