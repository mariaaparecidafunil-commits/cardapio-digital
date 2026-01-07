<?php
// backend/salvar_pedido.php
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . '/conexao.php';

// Garante que não tenha saída inesperada
if (ob_get_length()) ob_clean();

// Lê JSON enviado pelo front
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(["sucesso" => false, "erro" => "Nenhum dado recebido"]);
    exit;
}

// Campos básicos do pedido
$nome      = trim($data['nome']      ?? '');
$telefone  = trim($data['telefone']  ?? '');
$endereco  = trim($data['endereco']  ?? '');
$pagamento = trim($data['pagamento'] ?? '');
$obs       = trim($data['obs']       ?? ''); // usado só para WhatsApp
$troco     = trim($data['troco']     ?? '');
$total     = floatval($data['total'] ?? 0);
$itens     = $data['itens']          ?? [];

// Validação básica
if (!$nome || !$telefone || !$endereco || !$pagamento || !is_array($itens) || count($itens) === 0) {
    http_response_code(422);
    echo json_encode(["sucesso"=>false, "erro"=>"Dados incompletos"]);
    exit;
}

// 🔹 Inserir pedido
$stmt = $conn->prepare("
    INSERT INTO pedidos (cliente_nome, telefone, cliente_endereco, forma_pagamento, total, status, data)
    VALUES (?, ?, ?, ?, ?, 'Recebido', NOW())
");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(["sucesso"=>false, "erro"=>"Erro prepare pedido: ".$conn->error]);
    exit;
}
$stmt->bind_param("ssssd", $nome, $telefone, $endereco, $pagamento, $total);
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(["sucesso"=>false, "erro"=>"Erro inserir pedido: ".$stmt->error]);
    exit;
}
$pedidoId = $stmt->insert_id;
$stmt->close();

// 🔹 Inserir itens do pedido
$stmt = $conn->prepare("
    INSERT INTO itens_pedido
    (pedido_id, produto_id, quantidade, preco, adicionais, remocoes, observacao)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(["sucesso"=>false, "erro"=>"Erro prepare itens: ".$conn->error]);
    exit;
}

foreach ($itens as $item) {
    $pid        = intval($item['id']          ?? 0);
    $qtd        = intval($item['qtd']         ?? 0);
    $preco      = floatval($item['preco']     ?? 0);
    $adicionais = is_array($item['adicionais']) ? implode(", ", $item['adicionais']) : trim($item['adicionais'] ?? '');
    $remocoes   = is_array($item['remocoes'])   ? implode(", ", $item['remocoes'])   : trim($item['remocoes']   ?? '');
    $observacao = trim($item['observacao']     ?? '');

    if ($pid <= 0 || $qtd <= 0 || $preco < 0) {
        http_response_code(422);
        echo json_encode(["sucesso"=>false, "erro"=>"Item inválido"]);
        exit;
    }

    $stmt->bind_param("iiidsss", $pedidoId, $pid, $qtd, $preco, $adicionais, $remocoes, $observacao);
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(["sucesso"=>false, "erro"=>"Erro salvar item: ".$stmt->error]);
        exit;
    }
}
$stmt->close();

// === 🔹 CALCULA CUSTO TOTAL DO PEDIDO ===
$custo_total = 0;

foreach ($itens as $item) {
    $pid = intval($item['id'] ?? 0);
    $qtd = intval($item['qtd'] ?? 0);
    if($pid>0 && $qtd>0){
        $sqlCusto = "
            SELECT SUM(pi.quantidade * ins.preco_unit) AS custo_item
            FROM produto_insumo pi
            JOIN insumos ins ON ins.id = pi.insumo_id
            WHERE pi.produto_id = $pid
        ";
        $resCusto = $conn->query($sqlCusto);
        if($resCusto){
            $rowC = $resCusto->fetch_assoc();
            $custo_total += floatval($rowC['custo_item'] ?? 0) * $qtd;
        }
    }
}

// Atualiza o pedido com o custo_total
$conn->query("UPDATE pedidos SET custo_total = $custo_total WHERE id = $pedidoId");

// === 🔗 INTEGRAÇÃO COM FINANCEIRO ===
$check = $conn->query("SHOW TABLES LIKE 'movimentos'");
if($check && $check->num_rows > 0){
    $categoriaId = 9; // id da categoria 'Vendas Delivery'
    $dataVenda = date('Y-m-d');
    $descricao = "Venda pedido #$pedidoId";

    $stmtFin = $conn->prepare("
        INSERT INTO movimentos (data, tipo, categoria_id, descricao, valor)
        VALUES (?, 'entrada', ?, ?, ?)
    ");
    if($stmtFin){
        $stmtFin->bind_param("sisd", $dataVenda, $categoriaId, $descricao, $total);
        $stmtFin->execute();
        $stmtFin->close();
    }
}

// 🔹 Retorno final
http_response_code(200);
echo json_encode([
    "sucesso" => true,
    "id"      => $pedidoId
], JSON_UNESCAPED_UNICODE);
exit;
