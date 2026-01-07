<?php
// finalizar.php
// Processa o pedido do cliente: salva no banco, abre WhatsApp e gera link de acompanhamento

require_once __DIR__ . '/backend/conexao.php';

// Verifica se é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("⚠️ Acesso inválido");
}

// Sanitização
function esc($s) {
    return htmlspecialchars(trim((string)$s), ENT_QUOTES, 'UTF-8');
}

$nome      = esc($_POST['nome'] ?? '');
$endereco  = esc($_POST['endereco'] ?? '');
$obs       = esc($_POST['obs'] ?? '');
$pagamento = esc($_POST['pagamento'] ?? '');
$carrinho  = json_decode($_POST['carrinho'] ?? '[]', true);

if (!$nome || !$endereco || !$pagamento || empty($carrinho)) {
    die("⚠️ Dados do pedido incompletos.");
}

// Calcula total
$total = 0;
foreach ($carrinho as $item) {
    $total += $item['preco'] * $item['qtd'];
}

// Define status inicial
$status = 'recebido';
$tempoEstimado = "30 minutos";

// Salva pedido
$stmt = $conn->prepare("INSERT INTO pedidos 
    (cliente_nome, cliente_end, obs, pagamento, total, status, tempo_estimado, data) 
    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
$stmt->bind_param("ssssdss", $nome, $endereco, $obs, $pagamento, $total, $status, $tempoEstimado);
$stmt->execute();
$pedidoId = $stmt->insert_id;
$stmt->close();

// Salva itens do pedido
$stmt = $conn->prepare("INSERT INTO itens_pedido (pedido_id, produto_id, qtd, preco) VALUES (?, ?, ?, ?)");
foreach ($carrinho as $item) {
    $id = intval($item['id']);
    $qtd = intval($item['qtd']);
    $preco = floatval($item['preco']);
    $stmt->bind_param("iiid", $pedidoId, $id, $qtd, $preco);
    $stmt->execute();
}
$stmt->close();

// Monta mensagem para WhatsApp
$msg = "*Novo Pedido - Mimoso Lanches*%0A";
$msg .= "📌 Pedido #$pedidoId%0A";
$msg .= "👤 Cliente: $nome%0A";
$msg .= "🏠 Endereço: $endereco%0A";
if ($obs) $msg .= "📝 Obs: $obs%0A";
$msg .= "💳 Pagamento: $pagamento%0A%0A";
$msg .= "*Itens:*%0A";

foreach ($carrinho as $item) {
    $linha = $item['qtd']."x ".$item['nome']." - R$ ".number_format($item['preco']*$item['qtd'],2,",",".");
    $msg .= $linha."%0A";
}

$msg .= "%0A*Total: R$ ".number_format($total,2,",",".")."*%0A%0A";
$msg .= "📲 Acompanhe seu pedido: https://mimosolanches.com.br/meu-pedido.php?id=$pedidoId";

// Número do WhatsApp (definido no config.php)
$whats = defined('LOJA_WHATSAPP') ? LOJA_WHATSAPP : "5528999652545";

// Redireciona para WhatsApp
header("Location: https://wa.me/$whats?text=$msg");
exit;
?>
