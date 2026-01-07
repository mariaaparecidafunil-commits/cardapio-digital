<?php
// admin/update_status.php
session_start();
require_once __DIR__ . '/../backend/conexao.php';

// Garantir login
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit;
}

// Caso venha via formulário normal (POST simples)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['status'])) {
    $pedido_id = (int)$_POST['id'];
    $status = trim($_POST['status']);

    $permitidos = ['Recebido','Em preparo','Saiu para entrega','Entregue','Cancelado'];
    if (!in_array($status, $permitidos)) {
        $status = 'Recebido';
    }

    $stmt = $conn->prepare("UPDATE pedidos SET status=? WHERE id=?");
    $stmt->bind_param("si", $status, $pedido_id);
    $stmt->execute();
    $stmt->close();

    // Redireciona de volta para pedidos.php
    header("Location: pedidos.php");
    exit;
}

// Caso venha via JSON (requisição fetch)
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if ($data && isset($data['pedido_id'], $data['tipo'], $data['valor'])) {
    $pedido_id = (int)$data['pedido_id'];
    $tipo = $data['tipo'];
    $valor = $data['valor'];

    if ($tipo === 'pedido') {
        $stmt = $conn->prepare("UPDATE pedidos SET status=? WHERE id=?");
        $stmt->bind_param("si", $valor, $pedido_id);
        $ok = $stmt->execute();
        $stmt->close();

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => $ok]);
        exit;
    }

    if ($tipo === 'pagamento') {
        $stmtCheck = $conn->prepare("SELECT id FROM pagamentos WHERE pedido_id=? LIMIT 1");
        $stmtCheck->bind_param("i", $pedido_id);
        $stmtCheck->execute();
        $row = $stmtCheck->get_result()->fetch_assoc();
        $stmtCheck->close();

        if ($row) {
            $stmt = $conn->prepare("UPDATE pagamentos SET status=? WHERE id=?");
            $stmt->bind_param("si", $valor, $row['id']);
            $ok = $stmt->execute();
            $stmt->close();
        } else {
            $stmtInsert = $conn->prepare("INSERT INTO pagamentos (pedido_id, metodo, status, valor) VALUES (?, 'Desconhecido', ?, 0)");
            $stmtInsert->bind_param("is", $pedido_id, $valor);
            $ok = $stmtInsert->execute();
            $stmtInsert->close();
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => $ok]);
        exit;
    }
}

// Se nada foi tratado
header("HTTP/1.1 400 Bad Request");
echo "⚠️ Requisição inválida.";
exit;
