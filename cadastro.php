<?php
require_once __DIR__ . '/../app/conexao.php';
require_once __DIR__ . '/../app/helpers.php';

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check($_POST['csrf_token'] ?? '');

    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $telefone = trim($_POST['telefone'] ?? '');
    $cidade = trim($_POST['cidade'] ?? '');
    $aceite = isset($_POST['aceite_termos']) ? 1 : 0;

    if ($nome && $email && $senha) {
        try {
            // Verificar duplicidade de e-mail
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $erro = "E-mail já cadastrado. Tente outro.";
            } else {
                $hash = password_hash($senha, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, telefone, cidade, aceite_termos) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$nome, $email, $hash, $telefone, $cidade, $aceite]);
                $sucesso = "Cadastro realizado com sucesso! Você já pode fazer login.";
                logger("Novo cadastro: $email");
            }
        } catch (PDOException $e) {
            $erro = "Erro ao cadastrar: " . $e->getMessage();
        }
    } else {
        $erro = "Preencha todos os campos obrigatórios.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta
